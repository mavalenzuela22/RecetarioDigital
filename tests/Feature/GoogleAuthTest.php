<?php

use App\Models\AccessInvitation;
use App\Models\ApplicationBootstrap;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(ValidateCsrfToken::class);
});

function tsk016Guest(): void
{
    Auth::guard('web')->logout();
    Auth::forgetGuards();
}

function tsk016FakeGoogle(string $email, string $subject = 'google-subject-1', bool $verified = true): void
{
    Socialite::fake('google', SocialiteUser::fake([
        'id' => $subject,
        'name' => 'Patrona Google',
        'email' => $email,
        'email_verified' => $verified,
        'verified_email' => $verified,
    ]));
}

function tsk016Admin(): User
{
    return User::query()->where('email', 'test@example.com')->updateOrCreate(
        ['email' => 'test@example.com'],
        ['name' => 'Administradora', 'password' => 'test-password-123', 'active' => true, 'is_admin' => true],
    )->fresh();
}

it('keeps the Google redirect public and stateful', function (): void {
    tsk016Guest();
    Socialite::fake('google');

    $response = $this->get(route('google.redirect'))->assertRedirect();
    $providerHost = parse_url((string) $response->headers->get('Location'), PHP_URL_HOST);
    $applicationHost = parse_url(route('login'), PHP_URL_HOST);

    expect($providerHost)->not->toBeNull()->and($providerHost)->not->toBe($applicationHost);
});

it('logs in an active linked account and honors the intended URL', function (): void {
    tsk016Guest();
    $user = User::create([
        'name' => 'Patrona vinculada',
        'email' => 'patrona@example.com',
        'password' => null,
        'google_subject' => 'linked-subject',
        'email_verified_at' => now(),
        'active' => true,
    ]);
    $this->get('/recetas')->assertRedirect(route('login'));
    $before = $this->app['session']->getId();
    tsk016FakeGoogle(' PATRONA@EXAMPLE.COM ', 'linked-subject');

    $this->get(route('google.callback'))->assertRedirect('/recetas');
    expect(Auth::guard('web')->id())->toBe($user->id)
        ->and($this->app['session']->getId())->not->toBe($before);
});

it('denies unknown Google identities without creating a user', function (): void {
    tsk016Guest();
    tsk016FakeGoogle('unknown@example.com');

    $this->get(route('google.callback'))->assertRedirect(route('login'))->assertSessionHasErrors(['email' => 'Este correo necesita una invitación para entrar.']);
    expect(User::where('email', 'unknown@example.com')->exists())->toBeFalse();
});

it('does not silently link a password account by matching email', function (): void {
    tsk016Guest();
    User::create(['name' => 'Soporte', 'email' => 'support@example.com', 'password' => 'password-123456']);
    tsk016FakeGoogle('support@example.com');

    $this->get(route('google.callback'))->assertRedirect(route('login'))->assertSessionHasErrors(['email' => 'Este correo necesita una invitación para entrar.']);
    expect(User::where('email', 'support@example.com')->sole()->google_subject)->toBeNull();
});

it('denies unverified email, subject collisions, cancellation, and inactive accounts', function (): void {
    tsk016Guest();
    User::create(['name' => 'Vinculada', 'email' => 'linked@example.com', 'password' => null, 'google_subject' => 'collision-subject', 'email_verified_at' => now(), 'active' => true]);
    tsk016FakeGoogle('linked@example.com', 'different-subject', false);
    $this->get(route('google.callback'))->assertRedirect(route('login'))->assertSessionHasErrors(['email' => 'No pudimos completar el acceso con Google. Intenta de nuevo.']);

    tsk016FakeGoogle('other@example.com', 'collision-subject');
    $this->get(route('google.callback'))->assertRedirect(route('login'))->assertSessionHasErrors(['email' => 'No pudimos confirmar esta cuenta. Pide ayuda a una administradora.']);

    $inactive = User::create(['name' => 'Inactiva', 'email' => 'inactive@example.com', 'password' => null, 'google_subject' => 'inactive-subject', 'email_verified_at' => now(), 'active' => false]);
    tsk016FakeGoogle($inactive->email, 'inactive-subject');
    $this->get(route('google.callback'))->assertRedirect(route('login'))->assertSessionHasErrors(['email' => 'Tu acceso está inactivo. Pide a una administradora que lo reactive.']);

    $this->get(route('google.callback').'?error=access_denied')->assertRedirect(route('login'))->assertSessionHasErrors(['email' => 'No pudimos completar el acceso con Google. Intenta de nuevo.']);
});

it('accepts an invitation once and creates a Google-only user', function (): void {
    $admin = tsk016Admin();
    $rawToken = 'invitation-raw-token';
    $invitation = AccessInvitation::create(['email' => 'patrona@example.com', 'token_hash' => hash('sha256', $rawToken), 'created_by' => $admin->id, 'expires_at' => now()->addDays(7)]);
    tsk016Guest();
    $this->get(route('invitations.show', ['token' => $rawToken]))->assertOk()->assertInertia(fn ($page) => $page->component('Auth/Invitation')->where('email', 'patrona@example.com'));
    tsk016FakeGoogle(' PATRONA@EXAMPLE.COM ', 'new-subject');

    $this->get(route('google.callback'))->assertRedirect(route('home'));
    $user = User::where('email', 'patrona@example.com')->sole();
    expect($user->password)->toBeNull()->and($user->google_subject)->toBe('new-subject');
    expect($invitation->fresh()->accepted_by)->toBe($user->id);
    expect(User::where('email', 'patrona@example.com')->count())->toBe(1);
});

it('requires a usable exact-email invitation and safely links a password account', function (): void {
    $admin = tsk016Admin();
    $passwordUser = User::create(['name' => 'Soporte', 'email' => 'support@example.com', 'password' => 'password-123456']);
    $rawToken = 'support-invitation';
    AccessInvitation::create(['email' => 'support@example.com', 'token_hash' => hash('sha256', $rawToken), 'created_by' => $admin->id, 'expires_at' => now()->addDays(7)]);
    tsk016Guest();
    $this->get(route('invitations.show', ['token' => $rawToken]));
    tsk016FakeGoogle('other@example.com', 'support-subject');
    $this->get(route('google.callback'))->assertRedirect(route('login'))->assertSessionHasErrors(['email' => 'El correo de Google no coincide con la invitación.']);

    tsk016FakeGoogle('support@example.com', 'support-subject');
    $this->get(route('google.callback'))->assertRedirect(route('home'));
    expect($passwordUser->fresh()->google_subject)->toBe('support-subject');
    expect(Hash::check('password-123456', $passwordUser->fresh()->password))->toBeTrue();
});

it('rejects expired, revoked, accepted, and repeated invitations', function (): void {
    $admin = tsk016Admin();
    foreach ([['expired', now()->subMinute(), null], ['revoked', now()->addDay(), now()], ['accepted', now()->addDay(), null]] as [$email, $expiresAt, $revokedAt]) {
        $raw = $email.'-raw';
        $invitation = AccessInvitation::create(['email' => $email.'@example.com', 'token_hash' => hash('sha256', $raw), 'created_by' => $admin->id, 'expires_at' => $expiresAt, 'revoked_at' => $revokedAt]);
        if ($email === 'accepted') $invitation->update(['accepted_at' => now()]);
        tsk016Guest();
        $this->get(route('invitations.show', ['token' => $raw]))->assertRedirect(route('login'));
    }

    $raw = 'repeat-raw';
    $invitation = AccessInvitation::create(['email' => 'repeat@example.com', 'token_hash' => hash('sha256', $raw), 'created_by' => $admin->id, 'expires_at' => now()->addDay()]);
    tsk016Guest();
    $this->get(route('invitations.show', ['token' => $raw]));
    tsk016FakeGoogle($invitation->email, 'repeat-subject');
    $this->get(route('google.callback'))->assertRedirect(route('home'));
    $this->withSession(['invitation_token_hash' => hash('sha256', $raw)]);
    tsk016Guest();
    tsk016FakeGoogle($invitation->email, 'repeat-subject');
    $this->get(route('google.callback'))->assertRedirect(route('login'))->assertSessionHasErrors(['email' => 'Esta invitación ya no está disponible. Pide una nueva invitación.']);
});

it('keeps bootstrap persistent and consumes it only after verified Google success', function (): void {
    User::query()->delete();
    config(['access.bootstrap_secret' => 'bootstrap-test-secret']);
    tsk016Guest();

    $this->get(route('setup'))->assertOk()->assertInertia(fn ($page) => $page->component('Setup/Index'));
    $this->post(route('setup.secret'), ['secret' => 'wrong'])->assertSessionHasErrors(['secret' => 'El secreto no es correcto.']);
    expect($this->app['session']->get('bootstrap_authorized'))->toBeNull();

    $this->post(route('setup.secret'), ['secret' => 'bootstrap-test-secret'])->assertRedirect(route('google.redirect'));
    expect($this->app['session']->get('bootstrap_authorized'))->toBeTrue();
    tsk016FakeGoogle('admin@example.com', 'admin-subject', false);
    $this->get(route('google.callback'))->assertRedirect(route('login'));
    expect(ApplicationBootstrap::isComplete())->toBeFalse()->and(User::count())->toBe(0);

    $this->post(route('setup.secret'), ['secret' => 'bootstrap-test-secret'])->assertRedirect(route('google.redirect'));
    tsk016FakeGoogle('admin@example.com', 'admin-subject');
    $this->get(route('google.callback'))->assertRedirect(route('home'));
    expect(ApplicationBootstrap::isComplete())->toBeTrue();
    expect(User::where('email', 'admin@example.com')->sole()->is_admin)->toBeTrue();
    $this->get(route('setup'))->assertNotFound();
});

it('throttles bootstrap secrets and never accepts them through the URL', function (): void {
    User::query()->delete();
    config(['access.bootstrap_secret' => 'bootstrap-test-secret']);
    tsk016Guest();
    $this->get(route('setup', ['secret' => 'bootstrap-test-secret']))->assertOk();
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->post(route('setup.secret'), ['secret' => 'wrong']);
    }
    $this->post(route('setup.secret'), ['secret' => 'wrong'])->assertSessionHasErrors(['secret' => 'Demasiados intentos. Intenta de nuevo en unos segundos.']);
    expect($this->app['session']->get('bootstrap_authorized'))->toBeNull();
    RateLimiter::clear('bootstrap|'.hash('sha256', '127.0.0.1|Symfony'));
});
