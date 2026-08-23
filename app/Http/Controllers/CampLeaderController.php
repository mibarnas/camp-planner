<?php

namespace App\Http\Controllers;

use App\Models\Camp;
use App\Models\CampInvitation;
use App\Models\CampLeader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CampLeaderController extends Controller
{
    public function index(Camp $camp): Response
    {
        $this->authorize('view', $camp);

        $camp->load([
            'leaders.user:id,email',
            'invitations' => fn ($q) => $q->whereNull('accepted_at'),
        ]);

        // One list of everyone involved: leader rows first (each already carries
        // whether an account is linked), then invitations nobody has accepted yet.
        $people = $camp->leaders->map(fn (CampLeader $leader) => [
            'key' => 'leader-'.$leader->id,
            'leader_id' => $leader->id,
            'user_id' => $leader->user_id,
            'invitation_id' => null,
            'name' => $leader->name,
            'email' => $leader->user?->email,
            'color' => $leader->color,
            'status' => match (true) {
                $leader->user_id === $camp->owner_id => 'owner',
                $leader->user_id !== null => 'member',
                default => 'name_only',
            },
            'invite_link' => null,
        ])->sortBy(fn (array $p) => match ($p['status']) {
            'owner' => 0,
            'member' => 1,
            default => 2,
        })->values();

        $invited = $camp->invitations->whereNotNull('email')->map(fn (CampInvitation $i) => [
            'key' => 'invite-'.$i->id,
            'leader_id' => null,
            'user_id' => null,
            'invitation_id' => $i->id,
            'name' => null,
            'email' => $i->email,
            'color' => null,
            'status' => 'invited',
            'invite_link' => route('invitations.show', $i->token),
        ])->values();

        return Inertia::render('camps/Leaders', [
            'camp' => [
                'id' => $camp->id,
                'name' => $camp->name,
                'is_owner' => $camp->owner_id === Auth::id(),
            ],
            'people' => $people->concat($invited)->values()->all(),
            'shareLink' => ($share = $camp->invitations->firstWhere('email', null)) ? [
                'id' => $share->id,
                'link' => route('invitations.show', $share->token),
            ] : null,
        ]);
    }

    /**
     * Add a leader who has no account — a name the camp can point at.
     */
    public function store(Request $request, Camp $camp): RedirectResponse
    {
        $this->authorize('update', $camp);

        $camp->leaders()->create($this->validateData($request));

        return back()->with('toast', ['type' => 'success', 'message' => __('Leader added.')]);
    }

    public function update(Request $request, Camp $camp, CampLeader $leader): RedirectResponse
    {
        $this->authorize('update', $camp);

        $leader->update($this->validateData($request));

        return back()->with('toast', ['type' => 'success', 'message' => __('Leader updated.')]);
    }

    public function destroy(Camp $camp, CampLeader $leader): RedirectResponse
    {
        $this->authorize('update', $camp);

        // A leader with an account only leaves by removing the membership —
        // otherwise the row would come straight back on their next visit.
        if ($leader->hasAccount()) {
            throw ValidationException::withMessages([
                'leader' => __('A leader with an account is removed by removing their access.'),
            ]);
        }

        $leader->delete();

        return back()->with('toast', ['type' => 'success', 'message' => __('Leader removed.')]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);
    }
}
