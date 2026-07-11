<?php

namespace App\Http\Controllers;

use App\Models\Camp;
use App\Models\TimeSlot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TimeSlotController extends Controller
{
    public function store(Request $request, Camp $camp): RedirectResponse
    {
        $this->authorize('update', $camp);

        $data = $this->validateData($request);

        $camp->timeSlots()->create([
            ...$data,
            'position' => (int) $camp->timeSlots()->max('position') + 1,
        ]);

        return back()->with('toast', ['type' => 'success', 'message' => __('Time block added.')]);
    }

    public function update(Request $request, TimeSlot $slot): RedirectResponse
    {
        $this->authorize('update', $slot->camp);

        $slot->update($this->validateData($request));

        return back()->with('toast', ['type' => 'success', 'message' => __('Time block updated.')]);
    }

    public function destroy(TimeSlot $slot): RedirectResponse
    {
        $this->authorize('update', $slot->camp);

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
