<?php

use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeVersion;
use App\Models\User;
use App\Services\SaveRecipe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(ValidateCsrfToken::class);
});

function authGuest(): void
{
    Auth::guard('web')->logout();
    Auth::forgetGuards();
}

function authPayload(array $overrides = []): array
{
    return array_replace([
        'email' => 'test@example.com',
        'password' => 'test-password-123',
    ], $overrides);
}

it('redirects every business surface to login for guests', function (): void {
    authGuest();

    foreach (['/', '/ingredientes', '/recetas', '/productos', '/pedidos', '/produccion'] as $uri) {
        $this->get($uri)->assertRedirect(route('login'));
    }
});

it('renders the login page publicly and keeps health public', function (): void {
    authGuest();

    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Auth/Login'));
    $this->get('/up')->assertOk();
});

it('authenticates through the session guard and returns to the intended URL', function (): void {
    authGuest();
    $this->get('/recetas')->assertRedirect(route('login'));
    $before = $this->app['session']->getId();

    $this->post(route('login.store'), authPayload())
        ->assertStatus(303)
        ->assertRedirect('/recetas');

    expect(Auth::guard('web')->check())->toBeTrue()
        ->and($this->app['session']->getId())->not->toBe($before);
});

it('uses one generic failure for wrong and unknown credentials without authenticating', function (): void {
    authGuest();
    RateLimiter::clear('login|'.hash('sha256', 'test@example.com|127.0.0.1'));
    $wrong = $this->from(route('login'))->post(route('login.store'), authPayload(['password' => 'incorrect-password']));
    authGuest();
    $unknown = $this->from(route('login'))->post(route('login.store'), authPayload(['email' => 'missing@example.com']));

    $wrong->assertRedirect(route('login'))->assertSessionHasErrors(['email' => 'Los datos de acceso no son correctos.']);
    $unknown->assertRedirect(route('login'))->assertSessionHasErrors(['email' => 'Los datos de acceso no son correctos.']);
    expect(Auth::guard('web')->check())->toBeFalse();
});

it('throttles after five failures and clears the throttle after success', function (): void {
    authGuest();
    $key = 'login|'.hash('sha256', 'test@example.com|127.0.0.1');
    RateLimiter::clear($key);

    for ($attempt = 1; $attempt <= 5; $attempt++) {
        $this->post(route('login.store'), authPayload(['password' => 'incorrect-password']))
            ->assertSessionHasErrors(['email' => 'Los datos de acceso no son correctos.']);
    }
    $this->post(route('login.store'), authPayload(['password' => 'incorrect-password']))
        ->assertSessionHasErrors(['email' => 'Demasiados intentos. Intenta de nuevo en unos segundos.']);

    RateLimiter::clear($key);
    for ($attempt = 1; $attempt <= 4; $attempt++) {
        $this->post(route('login.store'), authPayload(['password' => 'incorrect-password']));
    }
    $this->post(route('login.store'), authPayload())->assertRedirect(route('home'));
    expect(RateLimiter::tooManyAttempts($key, 5))->toBeFalse();
});

it('logs out, invalidates the session, and protects the next request', function (): void {
    $privateResponse = $this->get(route('home'));
    $assertPrivateCacheHeaders = function ($response): void {
        $directives = array_map('trim', explode(',', $response->headers->get('Cache-Control', '')));

        expect($directives)->toContain('private')
            ->toContain('no-store')
            ->toContain('max-age=0')
            ->toContain('must-revalidate');
        expect($response->headers->get('Pragma'))->toBe('no-cache')
            ->and($response->headers->get('Expires'))->toBe('0');
    };

    $assertPrivateCacheHeaders($privateResponse);

    $logoutResponse = $this->post(route('logout'));
    $logoutResponse->assertStatus(303)->assertRedirect(route('login'));
    $assertPrivateCacheHeaders($logoutResponse);
    expect(Auth::guard('web')->check())->toBeFalse();
    $this->get('/')->assertRedirect(route('login'));
});

it('provisions and repeat-updates a normalized user without exposing the password', function (): void {
    $password = 'provisioned-password-123';
    putenv('TSK010_PASSWORD='.$password);

    $firstExitCode = Artisan::call('app:user-provision', ['email' => '  OPERATOR@EXAMPLE.COM ', '--name' => 'Operador', '--password-env' => 'TSK010_PASSWORD']);
    $firstOutput = Artisan::output();
    expect($firstExitCode)->toBe(0)
        ->and($firstOutput)->not->toContain($password);

    $secondExitCode = Artisan::call('app:user-provision', ['email' => 'operator@example.com', '--name' => 'Operadora', '--password-env' => 'TSK010_PASSWORD']);
    $secondOutput = Artisan::output();
    expect($secondExitCode)->toBe(0);
    putenv('TSK010_PASSWORD');

    $user = User::where('email', 'operator@example.com')->sole();
    expect(User::where('email', 'operator@example.com')->count())->toBe(1)
        ->and($user->name)->toBe('Operadora')
        ->and(Hash::check($password, $user->password))->toBeTrue()
        ->and($secondOutput)->not->toContain($password);
});

it('rejects invalid email and short provisioning passwords cleanly', function (): void {
    putenv('TSK010_SHORT=short');
    $this->artisan('app:user-provision', ['email' => 'not-an-email', '--name' => 'Operador', '--password-env' => 'TSK010_SHORT'])->assertExitCode(1);
    $this->artisan('app:user-provision', ['email' => 'new@example.com', '--name' => 'Operador', '--password-env' => 'TSK010_SHORT'])->assertExitCode(1);
    putenv('TSK010_SHORT');
    expect(User::whereIn('email', ['not-an-email', 'new@example.com'])->count())->toBe(0);
});

it('serves recipe images only from private storage through authenticated delivery', function (): void {
    Storage::fake('local');
    $ingredient = Ingredient::create(['name' => 'Harina privada', 'name_key' => hash('sha256', 'harina privada'), 'canonical_unit' => 'g']);
    $version = app(SaveRecipe::class)->save([
        'name' => 'Receta privada',
        'expected_yield' => '1',
        'instructions' => null,
        'notes' => null,
        'ingredients' => [['ingredient_id' => $ingredient->id, 'quantity' => '1', 'unit' => 'g']],
        'image' => UploadedFile::fake()->create('receta.webp', 1, 'image/webp'),
        'request_key' => (string) Str::uuid(),
    ]);
    expect($version->image_path)->not->toBeNull();
    Storage::disk('local')->assertExists($version->image_path);
    expect(Storage::disk('public')->exists($version->image_path))->toBeFalse();

    authGuest();
    $this->get(route('recipes.image', ['recipe' => $version->recipe_id, 'version' => $version->id]))
        ->assertRedirect(route('login'));

    $this->actingAs(User::where('email', 'test@example.com')->sole(), 'web');
    $this->get(route('recipes.image', ['recipe' => $version->recipe_id, 'version' => $version->id]))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/webp');

    $missing = Recipe::create(['name' => 'Sin imagen']);
    $missingVersion = RecipeVersion::create([
        'recipe_id' => $missing->id,
        'version_number' => 1,
        'name' => 'Sin imagen',
        'expected_yield' => 1,
        'request_key' => (string) Str::uuid(),
        'request_hash' => hash('sha256', 'missing'),
    ]);
    $this->get(route('recipes.image', ['recipe' => $missing->id, 'version' => $missingVersion->id]))->assertNotFound();
});
