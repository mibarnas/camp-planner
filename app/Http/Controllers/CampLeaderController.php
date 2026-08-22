<?php

namespace App\Http\Controllers;

use App\Models\Camp;
use App\Models\CampInvitation;
use App\Models\CampLeader;
use App\Models\User;
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
            'members:id,name,email',
            'invitations' => fn ($q) => $q->whereNull('accepted_at'),
        ]);

        return Inertia::render('camps/Leaders', [
            'camp' => [
                'id' => $camp->id,
                'name' => $camp->name,
                'is_owner' => $camp->owner_id === Auth::id(),
            ],
            'leaders' => $camp->leaders->map(fn (CampLeader $leader) => [
                'id' => $leader->id,
                'name' => $leader->name,
                'user_id' => $leader->user_id,
                'color' => $leader->color,
                'email' => $leader->user?->email,
            ])->values()->all(),
            'members' => $camp->members->map(fn (User $m) => [
                'id' => $m->id,
                'name' => $m->name,
                'email' => $m->email,
                'role' => $m->getAttribute('pivot')?->getAttribute('role'),
            ])->values()->all(),
            'invitations' => $camp->invitations->whereNotNull('email')->map(fn (CampInvitation $i) => [
                'id' => $i->id,
                'email' => $i->email,
                'role' => $i->role,
                'link' => route('invitations.show', $i->token),
            ])->values()->all(),
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
