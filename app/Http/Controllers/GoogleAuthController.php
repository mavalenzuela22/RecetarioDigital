<?php

namespace App\Http\Controllers;

use App\Models\AccessInvitation;
use App\Models\ApplicationBootstrap;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    private const GENERIC_ERROR = 'No pudimos completar el acceso con Google. Intenta de nuevo.';

    private const INVITATION_REQUIRED = 'Este correo necesita una invitación para entrar.';

    private const INVITATION_UNAVAILABLE = 'Esta invitación ya no está disponible. Pide una nueva invitación.';

    private const EMAIL_MISMATCH = 'El correo de Google no coincide con la invitación.';

    private const INACTIVE = 'Tu acceso está inactivo. Pide a una administradora que lo reactive.';

    private const COLLISION = 'No pudimos confirmar esta cuenta. Pide ayuda a una administradora.';

    public function redirect(Request $request): RedirectResponse
    {
        if ($request->session()->get('bootstrap_authorized') === true && ApplicationBootstrap::isComplete()) {
            $request->session()->forget('bootstrap_authorized');

            return to_route('login', [], 303)->withErrors(['email' => self::GENERIC_ERROR]);
        }

        try {
            return Socialite::driver('google')
                ->setScopes(['openid', 'profile', 'email'])
                ->redirect();
        } catch (Throwable) {
            return to_route('login', [], 303)->withErrors(['email' => self::GENERIC_ERROR]);
        }
    }

    public function callback(Request $request): RedirectResponse
    {
        if ($request->filled('error')) {
            return to_route('login', [], 303)->withErrors(['email' => self::GENERIC_ERROR]);
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable) {
            return to_route('login', [], 303)->withErrors(['email' => self::GENERIC_ERROR]);
        }

        $email = strtolower(trim((string) $googleUser->getEmail()));
        $subject = trim((string) $googleUser->getId());
        $raw = method_exists($googleUser, 'getRaw') ? $googleUser->getRaw() : [];
        $verified = $raw['verified_email'] ?? $raw['email_verified'] ?? false;
        $verified = is_string($verified) ? filter_var($verified, FILTER_VALIDATE_BOOLEAN) : $verified === true;

        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || $subject === '' || ! $verified) {
            return to_route('login', [], 303)->withErrors(['email' => self::GENERIC_ERROR]);
        }

        $bootstrap = $request->session()->get('bootstrap_authorized') === true;
        $invitationHash = $request->session()->get('invitation_token_hash');
        $name = trim((string) $googleUser->getName()) ?: $email;

        try {
            $user = DB::transaction(function () use ($bootstrap, $email, $subject, $name, $invitationHash): User {
                if ($bootstrap) {
                    return $this->completeBootstrap($email, $subject, $name);
                }

                return $this->completeInvitationOrLogin($email, $subject, $name, $invitationHash);
            });
        } catch (\LogicException $exception) {
            return to_route('login', [], 303)->withErrors(['email' => $exception->getMessage()]);
        } catch (Throwable) {
            return to_route('login', [], 303)->withErrors(['email' => self::GENERIC_ERROR]);
        }

        Auth::guard('web')->login($user);
        $request->session()->forget(['bootstrap_authorized', 'invitation_token_hash']);
        $request->session()->regenerate();

        return redirect()->intended(route('home'))->setStatusCode(303);
    }

    private function completeInvitationOrLogin(string $email, string $subject, string $name, ?string $invitationHash): User
    {
        $invitation = null;
        if (is_string($invitationHash) && preg_match('/^[a-f0-9]{64}$/', $invitationHash) === 1) {
            $invitation = AccessInvitation::query()->lockForUpdate()->where('token_hash', $invitationHash)->first();

            if ($invitation === null || ! $invitation->isUsable()) {
                throw new \LogicException(self::INVITATION_UNAVAILABLE);
            }
            if ($invitation->email !== $email) {
                throw new \LogicException(self::EMAIL_MISMATCH);
            }
        }

        $subjectUser = User::query()->where('google_subject', $subject)->first();
        if ($subjectUser !== null) {
            if ($subjectUser->email !== $email) {
                throw new \LogicException(self::COLLISION);
            }
            if (! $subjectUser->active) {
                throw new \LogicException(self::INACTIVE);
            }

            if ($invitation !== null) {
                $invitation->forceFill(['accepted_at' => now(), 'accepted_by' => $subjectUser->id])->save();
            }

            return $subjectUser;
        }

        $emailUser = User::query()->where('email', $email)->first();
        if ($emailUser !== null) {
            if (! $emailUser->active) {
                throw new \LogicException(self::INACTIVE);
            }
            if ($emailUser->google_subject !== null) {
                throw new \LogicException(self::COLLISION);
            }
            if ($invitation === null) {
                throw new \LogicException(self::INVITATION_REQUIRED);
            }

            $emailUser->forceFill([
                'google_subject' => $subject,
                'email_verified_at' => now(),
            ])->save();
            $invitation->forceFill(['accepted_at' => now(), 'accepted_by' => $emailUser->id])->save();

            return $emailUser->fresh();
        }

        if ($invitation === null) {
            throw new \LogicException(self::INVITATION_REQUIRED);
        }

        $newUser = User::query()->create([
            'name' => $name,
            'email' => $email,
            'email_verified_at' => now(),
            'password' => null,
            'google_subject' => $subject,
            'active' => true,
            'is_admin' => false,
        ]);
        $invitation->forceFill(['accepted_at' => now(), 'accepted_by' => $newUser->id])->save();

        return $newUser;
    }

    private function completeBootstrap(string $email, string $subject, string $name): User
    {
        $state = ApplicationBootstrap::query()->lockForUpdate()->find(1);
        if ($state === null || $state->completed_at !== null) {
            throw new \LogicException(self::GENERIC_ERROR);
        }
        if (User::query()->where('google_subject', $subject)->exists() || User::query()->where('email', $email)->exists()) {
            throw new \LogicException(self::COLLISION);
        }

        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'email_verified_at' => now(),
            'password' => null,
            'google_subject' => $subject,
            'active' => true,
            'is_admin' => true,
        ]);

        $state->forceFill(['completed_at' => now()])->save();

        return $user;
    }
}
