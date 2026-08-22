<?php

namespace App\Http\Controllers;

use App\Mail\CampInvitationMail;
use App\Models\Camp;
use App\Models\CampInvitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class CampMemberController extends Controller
{
    /**
     * Invite a camp leader by e-mail. If they already have an account they are
     * added immediately; otherwise a pending invitation is stored.
     */
    public function store(Request $request, Camp $camp): RedirectResponse
    {
        $this->authorize('manageMembers', $camp);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = strtolower($data['email']);
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if ($user) {
            if ($camp->hasMember($user)) {
                throw ValidationException::withMessages([
                    'email' => __('This person is already a member.'),
                ]);
            }

            $camp->addMember($user, 'leader');

            return back()->with('toast', [
                'type' => 'success',
                'message' => __(':name was added to the camp.', ['name' => $user->name]),
            ]);
        }

        $invitation = $camp->invitations()->updateOrCreate(
            ['email' => $email],
            ['role' => 'leader', 'invited_by' => $request->user()->id, 'accepted_at' => null],
        );

        // Queued, and in the inviter's current language.
        $invitation->loadMissing('camp', 'inviter');
        Mail::to($email)->locale(app()->getLocale())->send(new CampInvitationMail($invitation));

        return back()->with('toast', [
            'type' => 'success',
            'message' => __('Invitation sent to :email.', ['email' => $email]),
        ]);
    }

    public function destroy(Camp $camp, User $user): RedirectResponse
    {
        $this->authorize('manageMembers', $camp);

        if ($user->id === $camp->owner_id) {
            throw ValidationException::withMessages([
                'user' => __('The owner cannot be removed.'),
            ]);
        }

        $camp->members()->detach($user->id);

        // Their leader row stays behind as a plain name, so groups and anything
        // that named them keep working after the account is gone.
        $camp->leaders()->where('user_id', $user->id)->update(['user_id' => null]);

        return back()->with('toast', ['type' => 'success', 'message' => __('Member removed.')]);
    }

    public function destroyInvitation(Camp $camp, CampInvitation $invitation): RedirectResponse
    {
        $this->authorize('manageMembers', $camp);

        abort_unless($invitation->camp_id === $camp->id, 404);

        $invitation->delete();

        return back()->with('toast', ['type' => 'success', 'message' => __('Invitation cancelled.')]);
    }

    /**
     * Create (or return) the camp's shareable join link — an invitation without
     * an e-mail, usable by anyone, unlimited times.
     */
    public function storeShareLink(Request $request, Camp $camp): RedirectResponse
    {
        $this->authorize('manageMembers', $camp);

        $camp->invitations()->firstOrCreate(
            ['email' => null],
            ['role' => 'leader', 'invited_by' => $request->user()->id],
        );

        return back()->with('toast', ['type' => 'success', 'message' => __('Share link created.')]);
    }
}
