<?php

namespace App\Http\Controllers;

use App\Http\Requests\BootstrapSecretRequest;
use App\Models\ApplicationBootstrap;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Inertia\Response;

class SetupController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 60;

    public function create(): Response
    {
        abort_if(ApplicationBootstrap::isComplete(), 404);

        return Inertia::render('Setup/Index');
    }

    public function store(BootstrapSecretRequest $request): RedirectResponse
    {
        abort_if(ApplicationBootstrap::isComplete(), 404);

        $key = 'bootstrap|'.hash('sha256', ($request->ip() ?? '').'|'.$request->userAgent());
        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            return back(303)->withErrors([
                'secret' => 'Demasiados intentos. Intenta de nuevo en unos segundos.',
            ]);
        }

        $configuredSecret = (string) config('access.bootstrap_secret', '');
        $providedSecret = (string) $request->string('secret');

        if ($configuredSecret === '' || ! hash_equals($configuredSecret, $providedSecret)) {
            RateLimiter::hit($key, self::DECAY_SECONDS);

            return back(303)->withErrors([
                'secret' => 'El secreto no es correcto.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->session()->put('bootstrap_authorized', true);
        $request->session()->forget('invitation_token_hash');

        return to_route('google.redirect', [], 303);
    }
}
