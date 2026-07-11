<?php

namespace App\Http\Controllers;

use App\Models\CampInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    /**
     * Public landing page for a share/invite link. Prompts a guest to log in or
     * register (returning here afterwards), or lets a logged-in user join.
     */
    public function show(Request $request, string $token): Response
    {
        $invitation = CampInvitation::with(['camp:id,name,year,description,owner_id', 'inviter:id,name'])
            ->where('token', $token)
            ->first();

        if (! $invitation) {
            return Inertia::render('invitations/Join', ['valid' => false]);
        }

        $user = $request->user();

        // A guest who came via the link should return here after logging in.
        if (! $user) {
            $request->session()->put('url.intended', $request->fullUrl());
        }

        return Inertia::render('invitations/Join', [
            'valid' => true,
            'token' => $token,
            'invitedEmail' => $invitation->email,
            'inviter' => $invitation->inviter?->only(['name']),
            'camp' => [
                'id' => $invitation->camp->id,
                'name' => $invitation->camp->name,
                'year' => $invitation->camp->year,
                'description' => $invitation->camp->description,
            ],
            'alreadyMember' => $user ? $invitation->camp->hasMember($user) : false,
        ]);
    }

    /**
     * Join the camp (requires an authenticated user).
     */
    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = CampInvitation::where('token', $token)->firstOrFail();

        $user = $request->user();
        $camp = $invitation->camp;

        $camp->addMember($user, $invitation->role);

        // Personal (e-mail) invitations are one-shot; share links stay open.
        if ($invitation->email !== null) {
            $invitation->forceFill(['accepted_at' => now()])->save();
        }

        return to_route('camps.show', $camp)->with('toast', [
            'type' => 'success',
            'message' => __('You have joined :camp.', ['camp' => $camp->name]),
        ]);
    }
}
