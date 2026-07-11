<?php

namespace App\Http\Controllers;

use App\Models\Camp;
use App\Models\CampDay;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CampDayController extends Controller
{
    public function store(Request $request, Camp $camp): RedirectResponse
    {
        $this->authorize('update', $camp);

        $data = $request->validate([
            'date' => ['required', 'date'],
        ]);

        $camp->days()->firstOrCreate(
            ['date' => $data['date']],
            ['position' => $camp->days()->max('position') + 1],
        );

        return back()->with('toast', ['type' => 'success', 'message' => __('Day added.')]);
    }

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

    public function destroy(CampDay $day): RedirectResponse
    {
        $this->authorize('update', $day->camp);

        $day->delete();

        return back()->with('toast', ['type' => 'success', 'message' => __('Day removed.')]);
    }
}
