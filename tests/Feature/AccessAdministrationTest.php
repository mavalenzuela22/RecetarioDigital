<?php

use App\Http\Middleware\EnsureActiveUser;
use App\Models\AccessInvitation;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(ValidateCsrfToken::class);
});

function tsk016MakeAdmin(): User
{
    $admin = User::where('email', 'test@example.com')->sole();
    $admin->forceFill(['is_admin' => true, 'active' => true])->save();
    $admin = $admin->fresh();
    Auth::guard('web')->login($admin);

    return $admin;
}

it('returns 403 to non-administrators and lets an admin list and create invitations', function (): void {
    $this->get(route('access.index'))->assertForbidden();
    $admin = tsk016MakeAdmin();
    $this->get(route('access.index'))->assertOk()->assertInertia(fn ($page) => $page->component('Admin/Access/Index')->where('currentUserId', $admin->id));

    $createResponse = $this->post(route('access.invitations.store'), ['email' => '  PATrona@example.com ']);
    $createResponse->assertStatus(303)->assertRedirect(route('access.index'))->assertSessionHas('access_notice', 'Invitación creada. Copia el enlace y compártelo por tu canal habitual.')->assertSessionHas('invitation_link', fn (string $link): bool => str_contains($link, '/invitaciones/'));
    $this->get(route('access.index'))->assertOk()->assertInertia(fn ($page) => $page->component('Admin/Access/Index')->where('invitationLink', fn (string $link): bool => str_contains($link, '/invitaciones/')));
    $this->get(route('access.index'))->assertOk()->assertInertia(fn ($page) => $page->component('Admin/Access/Index')->where('invitationLink', null));
    $invitation = AccessInvitation::query()->sole();
    $sevenDaysAhead = now()->addDays(7);
    expect($invitation->email)->toBe('patrona@example.com')
        ->and($invitation->expires_at->between($sevenDaysAhead->copy()->subMinute(), $sevenDaysAhead->copy()->addMinute()))->toBeTrue();
    expect(session('invitation_link'))->toBeNull();
    $this->post(route('access.invitations.store'), ['email' => 'PATRONA@example.com'])->assertSessionHasErrors(['email' => 'Ya existe una invitación pendiente para este correo.']);
    $this->post(route('access.invitations.revoke', $invitation))->assertRedirect();
    expect($invitation->fresh()->revoked_at)->not->toBeNull();
});

it('protects admin lockout rules and enforces active state on existing sessions', function (): void {
    $admin = tsk016MakeAdmin();
    $otherAdmin = User::create(['name' => 'Otra admin', 'email' => 'other-admin@example.com', 'password' => null, 'is_admin' => true, 'active' => true]);
    $regular = User::create(['name' => 'Patrona', 'email' => 'regular@example.com', 'password' => null, 'active' => true]);

    $this->post(route('access.users.toggle', $admin), ['active' => false])->assertSessionHasErrors(['user' => 'No puedes desactivar tu propia cuenta administradora.']);
    $admin->forceFill(['active' => false])->save();
    $this->withoutMiddleware(EnsureActiveUser::class);
    $this->post(route('access.users.toggle', $otherAdmin), ['active' => false])->assertSessionHasErrors(['user' => 'La última administradora activa no puede desactivarse.']);
    expect($otherAdmin->fresh()->active)->toBeTrue();
    $admin->forceFill(['active' => true])->save();
    $this->withMiddleware(EnsureActiveUser::class);
    $this->post(route('access.users.toggle', $otherAdmin), ['active' => false])->assertRedirect();
    expect($otherAdmin->fresh()->active)->toBeFalse();
    $this->post(route('access.users.toggle', $otherAdmin), ['active' => true]);
    $this->post(route('access.users.toggle', $regular), ['active' => false])->assertRedirect();
    expect($regular->fresh()->active)->toBeFalse();

    Auth::guard('web')->login($regular->fresh());
    $regular->fresh()->forceFill(['active' => true])->save();
    Auth::guard('web')->login($regular->fresh());
    $regular->forceFill(['active' => false])->save();
    $sessionId = $this->app['session']->getId();
    Auth::guard('web')->forgetUser();
    expect($sessionId)->toBe($this->app['session']->getId());
    $this->get(route('home'))->assertRedirect(route('login'))->assertSessionHasErrors(['email' => 'Tu acceso está inactivo. Pide a una administradora que lo reactive.']);
    expect(Auth::guard('web')->check())->toBeFalse();
});

it('lets the current administrator set a recovery password for local fallback login', function (): void {
    $admin = tsk016MakeAdmin();
    $password = 'recovery-password-123';
    $this->post(route('access.recovery-password.update'), ['password' => $password, 'password_confirmation' => $password])->assertRedirect();
    expect(Hash::check($password, $admin->fresh()->password))->toBeTrue();

    Auth::guard('web')->logout();
    Auth::forgetGuards();
    $this->post(route('login.store'), ['email' => ' TEST@EXAMPLE.COM ', 'password' => $password])->assertRedirect(route('home'));
});

it('returns natural es-MX recovery validation and does not expose passwords', function (): void {
    tsk016MakeAdmin();

    $this->from(route('access.index'))
        ->post(route('access.recovery-password.update'), ['password' => '', 'password_confirmation' => ''])
        ->assertSessionHasErrors([
            'password' => 'Escribe una contraseña de recuperación.',
            'password_confirmation' => 'Confirma la contraseña de recuperación.',
        ]);

    $this->from(route('access.index'))
        ->post(route('access.recovery-password.update'), ['password' => ['no es texto'], 'password_confirmation' => ['no es texto']])
        ->assertSessionHasErrors([
            'password' => 'La contraseña de recuperación debe ser texto.',
            'password_confirmation' => 'La confirmación debe ser texto.',
        ]);

    $this->from(route('access.index'))
        ->post(route('access.recovery-password.update'), ['password' => 'corta', 'password_confirmation' => 'corta'])
        ->assertRedirect(route('access.index'))
        ->assertSessionHasErrors(['password' => 'La contraseña de recuperación debe tener al menos 12 caracteres.']);

    $this->from(route('access.index'))
        ->post(route('access.recovery-password.update'), ['password' => 'password-valid-123', 'password_confirmation' => 'password-different-123'])
        ->assertSessionHasErrors([
            'password' => 'Las contraseñas de recuperación no coinciden.',
            'password_confirmation' => 'Las contraseñas de recuperación no coinciden.',
        ])
        ->assertSessionDoesntHaveErrors(['password' => 'validation.min.string', 'password_confirmation' => 'validation.confirmed']);
});
