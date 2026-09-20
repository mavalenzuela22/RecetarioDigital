<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 60;

    private const INVALID_CREDENTIALS = 'Los datos de acceso no son correctos.';

    private const TOO_MANY_ATTEMPTS = 'Demasiados intentos. Intenta de nuevo en unos segundos.';

    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $email = strtolower(trim((string) $request->input('email')));
        $key = $this->throttleKey($email, $request->ip());

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            return back(303)->withErrors(['email' => self::TOO_MANY_ATTEMPTS]);
        }

        if (! Auth::attempt(['email' => $email, 'password' => $request->input('password')])) {
            RateLimiter::hit($key, self::DECAY_SECONDS);

            return back(303)->withErrors(['email' => self::INVALID_CREDENTIALS]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        return redirect()->intended(route('home'))->setStatusCode(303);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login', [], 303);
    }

    private function throttleKey(string $email, ?string $ip): string
    {
        return 'login|'.hash('sha256', $email.'|'.($ip ?? ''));
    }
}
