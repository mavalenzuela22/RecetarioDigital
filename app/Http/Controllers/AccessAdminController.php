<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAccessInvitationRequest;
use App\Http\Requests\RecoveryPasswordRequest;
use App\Http\Requests\ToggleUserAccessRequest;
use App\Models\AccessInvitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AccessAdminController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Access/Index', $this->pageProps($request));
    }

    private function pageProps(Request $request): array
    {
        return [
            'users' => User::query()->orderByDesc('is_admin')->orderBy('name')->get()->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'active' => $user->active,
                'admin' => $user->is_admin,
                'googleLinked' => $user->google_subject !== null,
            ])->values(),
            'invitations' => AccessInvitation::query()->latest()->get()->map(fn (AccessInvitation $invitation): array => [
                'id' => $invitation->id,
                'email' => $invitation->email,
                'status' => $invitation->status(),
                'expiresAt' => $invitation->expires_at?->format('d/m/Y'),
                'createdAt' => $invitation->created_at?->format('d/m/Y'),
            ])->values(),
            'invitationLink' => $request->session()->pull('invitation_link'),
            'notice' => $request->session()->pull('access_notice'),
            'currentUserId' => $request->user()->id,
        ];
    }

    public function storeInvitation(CreateAccessInvitationRequest $request): RedirectResponse
    {
        $email = (string) $request->string('email');
        $rawToken = bin2hex(random_bytes(32));
        $created = DB::transaction(function () use ($email, $rawToken, $request): bool {
            $pendingExists = AccessInvitation::query()
                ->lockForUpdate()
                ->where('email', $email)
                ->whereNull('accepted_at')
                ->whereNull('revoked_at')
                ->where('expires_at', '>', now())
                ->exists();

            if ($pendingExists) {
                return false;
            }

            AccessInvitation::query()->create([
                'email' => $email,
                'token_hash' => hash('sha256', $rawToken),
                'created_by' => $request->user()->id,
                'expires_at' => now()->addDays((int) config('access.invitation_expiry_days', 7)),
            ]);

            return true;
        });

        if (! $created) {
            return back(303)->withErrors(['email' => 'Ya existe una invitación pendiente para este correo.']);
        }

        return to_route('access.index', [], 303)
            ->with('invitation_link', route('invitations.show', ['token' => $rawToken]))
            ->with('access_notice', 'Invitación creada. Copia el enlace y compártelo por tu canal habitual.');
    }

    public function revokeInvitation(Request $request, AccessInvitation $invitation): RedirectResponse
    {
        if (! $invitation->isUsable()) {
            return back(303)->withErrors(['invitation' => 'Esta invitación ya no está pendiente.']);
        }

        $invitation->forceFill(['revoked_at' => now()])->save();

        return back(303)->with('access_notice', 'Invitación revocada.');
    }

    public function toggleUser(ToggleUserAccessRequest $request, User $user): RedirectResponse
    {
        $active = $request->boolean('active');
        $currentUser = $request->user();
        $targetUserId = $user->getKey();

        return DB::transaction(function () use ($active, $currentUser, $targetUserId): RedirectResponse {
            $administrators = collect();

            if (! $active) {
                $administrators = User::query()
                    ->where('is_admin', true)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();
            }

            $target = User::query()->whereKey($targetUserId)->lockForUpdate()->firstOrFail();

            if ($target->is($currentUser) && ! $active) {
                return back(303)->withErrors(['user' => 'No puedes desactivar tu propia cuenta administradora.']);
            }

            if (! $active && $target->active && $target->is_admin) {
                $activeAdminCount = $administrators->where('active', true)->count();
                if ($activeAdminCount <= 1) {
                    return back(303)->withErrors(['user' => 'La última administradora activa no puede desactivarse.']);
                }
            }

            $target->forceFill(['active' => $active])->save();

            return back(303)->with('access_notice', $active ? 'Acceso activado.' : 'Acceso desactivado.');
        });
    }

    public function updateRecoveryPassword(RecoveryPasswordRequest $request): RedirectResponse
    {
        $request->user()->forceFill(['password' => $request->string('password')])->save();

        return back(303)->with('access_notice', 'Contraseña de recuperación actualizada.');
    }
}
