<?php

namespace App\Http\Controllers;

use App\Models\Camp;
use App\Models\CampGroup;
use App\Models\CampLeader;
use App\Models\GroupType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GroupController extends Controller
{
    public function index(Camp $camp): Response
    {
        $this->authorize('view', $camp);

        $camp->load(['groups.leaders:id', 'groupTypes', 'leaders']);

        return Inertia::render('camps/Groups', [
            'camp' => [
                'id' => $camp->id,
                'name' => $camp->name,
            ],
            'groups' => $camp->groups->map(fn (CampGroup $group) => [
                'id' => $group->id,
                'name' => $group->name,
                'group_type_id' => $group->group_type_id,
                'competes' => $group->competes,
                'color' => $group->color,
                'position' => $group->position,
                'leader_ids' => $group->leaders->pluck('id')->all(),
            ])->values()->all(),
            'groupTypes' => $camp->groupTypes->map(fn (GroupType $type) => [
                'id' => $type->id,
                'name' => $type->name,
                'color' => $type->color,
                'position' => $type->position,
            ])->values()->all(),
            'leaders' => $camp->leaders->map(fn (CampLeader $leader) => [
                'id' => $leader->id,
                'name' => $leader->name,
                'user_id' => $leader->user_id,
                'color' => $leader->color,
            ])->values()->all(),
        ]);
    }

    public function store(Request $request, Camp $camp): RedirectResponse
    {
        $this->authorize('update', $camp);

        $data = $this->validateData($request, $camp);

        $group = $camp->groups()->create([
            ...$data,
            'position' => (int) $camp->groups()->max('position') + 1,
        ]);
        $group->leaders()->sync($this->leaderIds($request, $camp));

        return back()->with('toast', ['type' => 'success', 'message' => __('Group added.')]);
    }

    public function update(Request $request, Camp $camp, CampGroup $group): RedirectResponse
    {
        $this->authorize('update', $camp);

        $group->update($this->validateData($request, $camp));

        if ($request->has('leader_ids')) {
            $group->leaders()->sync($this->leaderIds($request, $camp));
        }

        return back()->with('toast', ['type' => 'success', 'message' => __('Group updated.')]);
    }

    public function destroy(Camp $camp, CampGroup $group): RedirectResponse
    {
        $this->authorize('update', $camp);

        $group->delete();

        return back()->with('toast', ['type' => 'success', 'message' => __('Group removed.')]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateData(Request $request, Camp $camp): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'group_type_id' => ['nullable', 'integer'],
            'competes' => ['boolean'],
            'color' => ['nullable', 'string', 'max:20'],
            'leader_ids' => ['array'],
            'leader_ids.*' => ['integer'],
        ]);

        // A type from another camp is simply no type.
        $typeId = $data['group_type_id'] ?? null;
        if ($typeId !== null && ! $camp->groupTypes()->whereKey($typeId)->exists()) {
            $typeId = null;
        }

        return [
            'name' => $data['name'],
            'group_type_id' => $typeId,
            'competes' => $data['competes'] ?? true,
            'color' => $data['color'] ?? null,
        ];
    }

    /**
     * Only this camp's leaders can be put in its groups.
     *
     * @return list<int>
     */
    protected function leaderIds(Request $request, Camp $camp): array
    {
        $requested = array_map('intval', (array) $request->input('leader_ids', []));

        $ids = [];
        foreach ($camp->leaders()->whereKey($requested)->get() as $leader) {
            $ids[] = $leader->id;
        }

        return $ids;
    }
}
