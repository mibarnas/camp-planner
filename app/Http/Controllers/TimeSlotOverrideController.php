<?php

namespace App\Http\Controllers;

use App\Models\CampDay;
use App\Models\TimeSlot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TimeSlotOverrideController extends Controller
{
    /**
     * Move, resize or hide a daily block on one day only. The camp-wide slot
     * stays the template for every other day.
     */
    public function upsert(Request $request, CampDay $day, TimeSlot $slot): RedirectResponse
    {
        $this->authorize('editSchedule', $day->camp);
        abort_unless($slot->camp_id === $day->camp_id, 404);

        $data = $request->validate([
            'start_time' => ['sometimes', 'nullable', 'date_format:H:i'],
            'end_time' => ['sometimes', 'nullable', 'date_format:H:i'],
            'is_hidden' => ['sometimes', 'boolean'],
        ]);

        $existing = $day->slotOverrides()->where('time_slot_id', $slot->id)->first();

        $start = array_key_exists('start_time', $data) ? $data['start_time'] : $existing?->start_time;
        $end = array_key_exists('end_time', $data) ? $data['end_time'] : $existing?->end_time;
        $hidden = array_key_exists('is_hidden', $data) ? (bool) $data['is_hidden'] : (bool) $existing?->is_hidden;

        $start = $this->normalizeTime($start);
        $end = $this->normalizeTime($end);

        if ($start !== null && $end !== null && $end <= $start) {
            return back()->withErrors(['end_time' => __('The end must be after the start.')]);
        }

        // Times back at the template's are not a deviation — drop them so the
        // block stops being flagged as changed for this day.
        if ($start === $this->normalizeTime($slot->start_time)) {
            $start = null;
        }

        if ($end === $this->normalizeTime($slot->end_time)) {
            $end = null;
        }

        // No deviation left -> drop the row so the day follows the template again.
        if ($start === null && $end === null && ! $hidden) {
            $existing?->delete();

            return back();
        }

        $day->slotOverrides()->updateOrCreate(
            ['time_slot_id' => $slot->id],
            ['start_time' => $start, 'end_time' => $end, 'is_hidden' => $hidden],
        );

        return back();
    }

    /**
     * Drop the day's deviation — the block follows the camp template again.
     */
    public function destroy(CampDay $day, TimeSlot $slot): RedirectResponse
    {
        $this->authorize('editSchedule', $day->camp);
        abort_unless($slot->camp_id === $day->camp_id, 404);

        $day->slotOverrides()->where('time_slot_id', $slot->id)->delete();

        return back()->with('toast', [
            'type' => 'success',
            'message' => __('Block restored to the template.'),
        ]);
    }

    /**
     * Times come back from the DB as 'HH:MM:SS' but go out as 'HH:MM'.
     */
    protected function normalizeTime(?string $time): ?string
    {
        return $time === null ? null : substr($time, 0, 5);
    }
}
