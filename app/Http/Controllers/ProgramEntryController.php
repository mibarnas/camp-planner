<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\CampDay;
use App\Models\ProgramEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ProgramEntryController extends Controller
{
    /**
     * Place a new activity on a day at a given start time + duration.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'camp_day_id' => ['required', 'exists:camp_days,id'],
            'activity_id' => ['nullable', 'exists:activities,id'],
            'kind' => ['sometimes', 'in:detailed,simple'],
            'start_time' => ['required', 'date_format:H:i'],
            'duration' => ['required', 'integer', 'min:5', 'max:1440'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'responsible' => ['nullable', 'string', 'max:255'],
            'materials' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $day = CampDay::findOrFail((int) $data['camp_day_id']);
        $this->authorize('editSchedule', $day->camp);

        $kind = $data['kind'] ?? 'detailed';

        // A simple block is just a label on the timeline — it never links to the
        // activity library, whatever the client sent.
        if ($kind === 'simple') {
            $data['activity_id'] = null;
        }

        // Prefill title/description/materials from the chosen activity if empty.
        if (! empty($data['activity_id'])) {
            $activity = Activity::find((int) $data['activity_id']);
            if ($activity) {
                $data['title'] = $data['title'] ?: $activity->name;
                $data['description'] ??= $activity->description;
                $data['materials'] ??= $activity->materials;
            }
        }

        $day->entries()->create([
            'activity_id' => $data['activity_id'] ?? null,
            'kind' => $kind,
            'start_time' => $data['start_time'],
            'duration' => $data['duration'],
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'responsible' => $data['responsible'] ?? null,
            'materials' => $data['materials'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('toast', ['type' => 'success', 'message' => __('Activity added.')]);
    }

    /**
     * Update an entry: move/resize (start_time, duration) and/or its content.
     */
    public function update(Request $request, ProgramEntry $entry): RedirectResponse
    {
        $this->authorize('editSchedule', $entry->day->camp);

        $data = $request->validate([
            'activity_id' => ['sometimes', 'nullable', 'exists:activities,id'],
            'kind' => ['sometimes', 'in:detailed,simple'],
            'start_time' => ['sometimes', 'required', 'date_format:H:i'],
            'duration' => ['sometimes', 'required', 'integer', 'min:5', 'max:1440'],
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'responsible' => ['sometimes', 'nullable', 'string', 'max:255'],
            'materials' => ['sometimes', 'nullable', 'string'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'in:todo,none,done'],
        ]);

        // Turning an activity into a simple block drops its library link; the
        // scenario and materials stay on the row so the change is reversible.
        if (($data['kind'] ?? $entry->kind) === 'simple') {
            $data['activity_id'] = null;
        }

        $entry->update($data);

        return back()->with('toast', ['type' => 'success', 'message' => __('Program saved.')]);
    }

    /**
     * Bulk-update a set of entries: per-entry start_time/duration (multi-drag)
     * and/or a shared responsible person.
     */
    public function bulkUpdate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'entries' => ['required', 'array', 'min:1', 'max:200'],
            'entries.*.id' => ['required', 'integer', 'exists:program_entries,id'],
            'entries.*.start_time' => ['sometimes', 'required', 'date_format:H:i'],
            'entries.*.duration' => ['sometimes', 'required', 'integer', 'min:5', 'max:1440'],
            'entries.*.responsible' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $entries = ProgramEntry::with('day.camp')->findMany(array_column($data['entries'], 'id'))->keyBy('id');

        foreach ($data['entries'] as $item) {
            $entry = $entries[$item['id']] ?? null;
            if (! $entry) {
                continue;
            }
            $this->authorize('editSchedule', $entry->day->camp);
            $entry->update(Arr::only($item, ['start_time', 'duration', 'responsible']));
        }

        return back()->with('toast', ['type' => 'success', 'message' => __('Saved.')]);
    }

    /**
     * Bulk-delete entries.
     */
    public function bulkDestroy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['required', 'integer', 'exists:program_entries,id'],
        ]);

        $entries = ProgramEntry::with('day.camp')->findMany($data['ids']);

        foreach ($entries as $entry) {
            $this->authorize('editSchedule', $entry->day->camp);
            $entry->delete();
        }

        return back()->with('toast', [
            'type' => 'success',
            'message' => __(':count activities removed.', ['count' => $entries->count()]),
        ]);
    }

    /**
     * Set an entry's progress state. Idempotent (rather than cycling) so a
     * double tap can't overshoot; the UI decides what the next state is.
     */
    public function setStatus(Request $request, ProgramEntry $entry): RedirectResponse
    {
        $this->authorize('editSchedule', $entry->day->camp);

        $data = $request->validate([
            'status' => ['required', 'in:todo,none,done'],
        ]);

        $entry->update($data);

        return back();
    }

    public function destroy(ProgramEntry $entry): RedirectResponse
    {
        $this->authorize('editSchedule', $entry->day->camp);

        $entry->delete();

        return back()->with('toast', ['type' => 'success', 'message' => __('Removed.')]);
    }
}
