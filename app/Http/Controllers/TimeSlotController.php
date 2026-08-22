<?php

namespace App\Http\Controllers;

use App\Models\Camp;
use App\Models\TimeSlot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TimeSlotController extends Controller
{
    /**
     * The camp's daily time skeleton — the template every day starts from.
     */
    public function index(Camp $camp): Response
    {
        $this->authorize('view', $camp);

        return Inertia::render('camps/Blocks', [
            'camp' => [
                'id' => $camp->id,
                'name' => $camp->name,
                'schedule_locked' => $camp->isScheduleLocked(),
            ],
            'slots' => $camp->timeSlots()->get()->map(fn (TimeSlot $slot) => [
                'id' => $slot->id,
                'name' => $slot->name,
                'start_time' => substr((string) $slot->start_time, 0, 5),
                'end_time' => substr((string) $slot->end_time, 0, 5),
                'kind' => $slot->kind,
                'color' => $slot->color,
                'position' => $slot->position,
            ])->values(),
        ]);
    }

    public function store(Request $request, Camp $camp): RedirectResponse
    {
        $this->authorize('editSchedule', $camp);

        $data = $this->validateData($request);

        $camp->timeSlots()->create([
            ...$data,
            'position' => (int) $camp->timeSlots()->max('position') + 1,
        ]);

        return back()->with('toast', ['type' => 'success', 'message' => __('Time block added.')]);
    }

    public function update(Request $request, TimeSlot $slot): RedirectResponse
    {
        $this->authorize('editSchedule', $slot->camp);

        $slot->update($this->validateData($request));

        return back()->with('toast', ['type' => 'success', 'message' => __('Time block updated.')]);
    }

    public function destroy(TimeSlot $slot): RedirectResponse
    {
        $this->authorize('editSchedule', $slot->camp);

        $slot->delete();

        return back()->with('toast', ['type' => 'success', 'message' => __('Time block removed.')]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'kind' => ['required', 'in:fixed,activity'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);
    }
}
