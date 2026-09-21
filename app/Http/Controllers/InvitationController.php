<?php

namespace App\Http\Controllers;

use App\Models\AccessInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    public function show(Request $request, string $token): Response|RedirectResponse
    {
        $invitation = AccessInvitation::query()
            ->where('token_hash', hash('sha256', $token))
            ->first();

        if ($invitation === null || ! $invitation->isUsable()) {
            return to_route('login', [], 303)->withErrors([
                'email' => 'Esta invitación ya no está disponible. Pide una nueva invitación.',
            ]);
        }

        $request->session()->put('invitation_token_hash', hash('sha256', $token));

        return Inertia::render('Auth/Invitation', [
            'email' => $invitation->email,
            'googleUrl' => route('google.redirect'),
            'expiresAt' => $invitation->expires_at?->isoFormat('D [de] MMMM [de] YYYY'),
        ]);
    }
}
