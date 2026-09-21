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

    $this->post(route('access.invitations.store'), ['email' => '  PATrona@example.com '])->assertOk()->assertInertia(fn ($page) => $page->component('Admin/Access/Index')->where('invitationLink', fn (string $link): bool => str_contains($link, '/invitaciones/')));
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
