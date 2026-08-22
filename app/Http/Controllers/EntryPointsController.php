<?php

namespace App\Http\Controllers;

use App\Models\ProgramEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EntryPointsController extends Controller
{
    /**
     * Record what each group scored in one activity. Any member may fill this
     * in for any group — and, like reviews, it keeps working while the schedule
     * is frozen, since recording results is not editing the plan.
     */
    public function update(Request $request, ProgramEntry $entry): RedirectResponse
    {
        $camp = $entry->day->camp;

        $this->authorize('update', $camp);

        if (! $entry->isForPoints()) {
            throw ValidationException::withMessages([
                'points' => __('This activity is not scored.'),
            ]);
        }

        $data = $request->validate([
            'points' => ['required', 'array'],
            'points.*.camp_group_id' => ['required', 'integer'],
            'points.*.value' => ['nullable', 'numeric', 'min:-99999', 'max:99999'],
        ]);

        // Only groups of this camp that actually compete can score.
        $allowed = $camp->groups()->where('competes', true)->pluck('id')->all();

        foreach ($data['points'] as $row) {
            $groupId = (int) $row['camp_group_id'];

            if (! in_array($groupId, $allowed, true)) {
                continue;
            }

            // A cleared field means "no result", not a zero.
            if (($row['value'] ?? null) === null) {
                $entry->groupPoints()->where('camp_group_id', $groupId)->delete();

                continue;
            }

            $entry->groupPoints()->updateOrCreate(
                ['camp_group_id' => $groupId],
                ['value' => $row['value'], 'recorded_by' => $request->user()->id],
            );
        }

        return back()->with('toast', ['type' => 'success', 'message' => __('Points saved.')]);
    }
}
