<?php

namespace App\Http\Controllers;

use App\Models\CampDay;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CampDayController extends Controller
{
    /**
     * Days are derived from the camp's date range, so only their meta is editable
     * (trip flag, name days, materials, notes) — they can't be added or removed.
     */
    public function update(Request $request, CampDay $day): RedirectResponse
    {
        $this->authorize('update', $day->camp);

        $data = $request->validate([
            'is_trip' => ['boolean'],
            'trip_name' => ['nullable', 'string', 'max:255'],
            'name_days' => ['nullable', 'string', 'max:255'],
            'birthdays' => ['nullable', 'string', 'max:255'],
            'materials' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $day->update($data);

        return back()->with('toast', ['type' => 'success', 'message' => __('Day updated.')]);
    }
}
