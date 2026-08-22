<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Camp;
use App\Models\CampDay;
use App\Models\PlanVersion;
use App\Models\ProgramEntry;
use App\Models\TimeSlot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlanVersionController extends Controller
{
    /**
     * Save the camp's whole schedule under a name so it can be restored later.
     */
    public function store(Request $request, Camp $camp): RedirectResponse
    {
        $this->authorize('manageMembers', $camp);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        PlanVersion::create([
            'camp_id' => $camp->id,
            'user_id' => $request->user()->id,
            'name' => $data['name'],
            'payload' => $this->snapshot($camp),
        ]);

        return back()->with('toast', ['type' => 'success', 'message' => __('Version saved.')]);
    }

    /**
     * Replace the current schedule with a saved one. The state being replaced is
     * kept as an automatic backup version first, so a restore is never final.
     */
    public function restore(Request $request, Camp $camp, PlanVersion $planVersion): RedirectResponse
    {
        $this->authorize('manageMembers', $camp);

        if ($camp->isScheduleLocked()) {
            return back()->with('toast', [
                'type' => 'error',
                'message' => __('Unlock the schedule first.'),
            ]);
        }

        $payload = $planVersion->payload;

        if (($payload['version'] ?? null) !== PlanVersion::VERSION) {
            return back()->with('toast', [
                'type' => 'error',
                'message' => __('This version was saved in an unsupported format.'),
            ]);
        }

        DB::transaction(function () use ($camp, $planVersion, $payload, $request) {
            PlanVersion::create([
                'camp_id' => $camp->id,
                'user_id' => $request->user()->id,
                'name' => __('Before restoring: :name', ['name' => $planVersion->name]),
                'payload' => $this->snapshot($camp),
            ]);

            $days = $camp->days()->get();

            // Slots go first: their cascade takes every per-day override with them.
            TimeSlot::where('camp_id', $camp->id)->delete();
            ProgramEntry::whereIn('camp_day_id', $days->pluck('id'))->delete();

            $slotIdByKey = [];
            foreach ($payload['slots'] ?? [] as $slot) {
                $created = $camp->timeSlots()->create([
                    'name' => $slot['name'],
                    'start_time' => $slot['start_time'],
                    'end_time' => $slot['end_time'],
                    'kind' => $slot['kind'],
                    'color' => $slot['color'] ?? null,
                    'position' => $slot['position'] ?? 0,
                ]);
                $slotIdByKey[$slot['key']] = $created->id;
            }

            // Days are matched by date — the camp's range may have moved since.
            $daysByDate = $days->keyBy(fn (CampDay $d) => $d->date->toDateString());

            // Activities can have been deleted from the library in the meantime.
            $activityIds = [];
            foreach ($payload['days'] ?? [] as $dayData) {
                foreach ($dayData['entries'] ?? [] as $entry) {
                    if (! empty($entry['activity_id'])) {
                        $activityIds[] = (int) $entry['activity_id'];
                    }
                }
            }
            $liveActivityIds = $activityIds === []
                ? []
                : Activity::whereIn('id', array_unique($activityIds))->pluck('id')->all();

            foreach ($payload['days'] ?? [] as $dayData) {
                $day = $daysByDate->get($dayData['date']);

                if (! $day) {
                    continue;
                }

                $day->update([
                    'is_trip' => $dayData['is_trip'] ?? false,
                    'trip_name' => $dayData['trip_name'] ?? null,
                ]);

                foreach ($dayData['entries'] ?? [] as $entry) {
                    $activityId = $entry['activity_id'] ?? null;

                    $day->entries()->create([
                        'activity_id' => in_array((int) $activityId, $liveActivityIds, true) ? $activityId : null,
                        'kind' => $entry['kind'] ?? 'detailed',
                        'start_time' => $entry['start_time'],
                        'duration' => $entry['duration'],
                        'title' => $entry['title'] ?? null,
                        'description' => $entry['description'] ?? null,
                        'responsible' => $entry['responsible'] ?? null,
                        'points_mode' => $entry['points_mode'] ?? ProgramEntry::POINTS_NONE,
                        'materials' => $entry['materials'] ?? null,
                        'notes' => $entry['notes'] ?? null,
                        'status' => $entry['status'] ?? 'none',
                    ]);
                }

                foreach ($dayData['overrides'] ?? [] as $override) {
                    $slotId = $slotIdByKey[$override['slot_key']] ?? null;

                    if (! $slotId) {
                        continue;
                    }

                    $day->slotOverrides()->create([
                        'time_slot_id' => $slotId,
                        'start_time' => $override['start_time'] ?? null,
                        'end_time' => $override['end_time'] ?? null,
                        'is_hidden' => $override['is_hidden'] ?? false,
                    ]);
                }
            }
        });

        return back()->with('toast', [
            'type' => 'success',
            'message' => __('Plan restored. The previous state was saved as a backup.'),
        ]);
    }

    public function destroy(Camp $camp, PlanVersion $planVersion): RedirectResponse
    {
        $this->authorize('manageMembers', $camp);

        $planVersion->delete();

        return back()->with('toast', ['type' => 'success', 'message' => __('Version deleted.')]);
    }

    /**
     * The camp's schedule as a portable payload: slots keyed by their position
     * in the list, days by date, so nothing depends on database ids surviving.
     *
     * @return array<string, mixed>
     */
    protected function snapshot(Camp $camp): array
    {
        $slots = $camp->timeSlots()->get()->values();

        $slotKeyById = [];
        $slotPayload = [];

        foreach ($slots as $key => $slot) {
            $slotKeyById[$slot->id] = $key;
            $slotPayload[] = [
                'key' => $key,
                'name' => $slot->name,
                'start_time' => substr((string) $slot->start_time, 0, 5),
                'end_time' => substr((string) $slot->end_time, 0, 5),
                'kind' => $slot->kind,
                'color' => $slot->color,
                'position' => $slot->position,
            ];
        }

        $days = [];

        foreach ($camp->days()->with(['entries', 'slotOverrides'])->get() as $day) {
            $entries = [];

            foreach ($day->entries as $entry) {
                $entries[] = [
                    'activity_id' => $entry->activity_id,
                    'kind' => $entry->kind,
                    'start_time' => substr((string) $entry->start_time, 0, 5),
                    'duration' => $entry->duration,
                    'title' => $entry->title,
                    'description' => $entry->description,
                    'responsible' => $entry->responsible,
                    'points_mode' => $entry->points_mode,
                    'materials' => $entry->materials,
                    'notes' => $entry->notes,
                    'status' => $entry->status,
                ];
            }

            $overrides = [];

            foreach ($day->slotOverrides as $override) {
                if (! isset($slotKeyById[$override->time_slot_id])) {
                    continue;
                }

                $overrides[] = [
                    'slot_key' => $slotKeyById[$override->time_slot_id],
                    'start_time' => $override->start_time === null ? null : substr((string) $override->start_time, 0, 5),
                    'end_time' => $override->end_time === null ? null : substr((string) $override->end_time, 0, 5),
                    'is_hidden' => $override->is_hidden,
                ];
            }

            $days[] = [
                'date' => $day->date->toDateString(),
                'is_trip' => $day->is_trip,
                'trip_name' => $day->trip_name,
                'entries' => $entries,
                'overrides' => $overrides,
            ];
        }

        return [
            'format' => PlanVersion::FORMAT,
            'version' => PlanVersion::VERSION,
            'slots' => $slotPayload,
            'days' => $days,
        ];
    }
}
