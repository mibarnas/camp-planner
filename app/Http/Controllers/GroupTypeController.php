<?php

namespace App\Http\Controllers;

use App\Models\Camp;
use App\Models\GroupType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GroupTypeController extends Controller
{
    public function store(Request $request, Camp $camp): RedirectResponse
    {
        $this->authorize('update', $camp);

        $camp->groupTypes()->create([
            ...$this->validateData($request),
            'position' => (int) $camp->groupTypes()->max('position') + 1,
        ]);

        return back()->with('toast', ['type' => 'success', 'message' => __('Group type added.')]);
    }

    public function update(Request $request, Camp $camp, GroupType $groupType): RedirectResponse
    {
        $this->authorize('update', $camp);

        $groupType->update($this->validateData($request));

        return back()->with('toast', ['type' => 'success', 'message' => __('Group type updated.')]);
    }

    /**
     * Groups of this type keep existing — they just lose the label.
     */
    public function destroy(Camp $camp, GroupType $groupType): RedirectResponse
    {
        $this->authorize('update', $camp);

        $groupType->delete();

        return back()->with('toast', ['type' => 'success', 'message' => __('Group type removed.')]);
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
