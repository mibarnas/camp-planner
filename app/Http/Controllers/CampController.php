<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityLibrary;
use App\Models\Camp;
use App\Models\CampDay;
use App\Models\ProgramEntry;
use App\Models\TimeSlot;
use App\Models\User;
use App\Support\NameDays;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CampController extends Controller
{
    public function index(Request $request): Response
    {
        $camps = $request->user()->camps()
            ->with('owner:id,name')
            ->withCount(['days'])
            ->orderByDesc('year')
            ->orderBy('name')
            ->get()
            ->map(fn (Camp $camp) => [
                'id' => $camp->id,
                'name' => $camp->name,
                'year' => $camp->year,
                'description' => $camp->description,
                'start_date' => $camp->start_date->toDateString(),
                'end_date' => $camp->end_date->toDateString(),
                'days_count' => $camp->days_count,
                'role' => $camp->pivot?->getAttribute('role'),
                'owner' => $camp->owner?->only(['id', 'name']),
            ]);

        return Inertia::render('camps/Index', [
            'camps' => $camps,
            'libraries' => $request->user()->activityLibraries()
                ->orderBy('name')->get()
                ->map(fn (ActivityLibrary $l) => $l->only(['id', 'name']))
                ->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'description' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'activity_library_id' => ['nullable', 'exists:activity_libraries,id'],
        ]);

        $user = $request->user();

        // Link an existing library the user belongs to, or create a fresh one.
        $library = null;
        if (! empty($data['activity_library_id'])) {
            $library = ActivityLibrary::findOrFail((int) $data['activity_library_id']);
            abort_unless($library->hasMember($user), 403);
        }

        $camp = DB::transaction(function () use ($data, $user, $library) {
            if (! $library) {
                $library = ActivityLibrary::create([
                    'name' => $data['name'],
                    'owner_id' => $user->id,
                ]);
                $library->members()->attach($user->id, ['role' => 'owner']);
                $library->seedDefaultCategories();
            }

            $camp = Camp::create([
                ...Arr::except($data, 'activity_library_id'),
                'owner_id' => $user->id,
                'activity_library_id' => $library->id,
            ]);

            $camp->addMember($user, 'owner');

            $this->seedDefaultSlots($camp);
            $this->generateDays($camp);

            return $camp;
        });

        return to_route('camps.show', $camp)->with('toast', [
            'type' => 'success',
            'message' => __('Camp created.'),
        ]);
    }

    public function show(Camp $camp): Response
    {
        $this->authorize('view', $camp);

        $camp->load([
            'timeSlots',
            'days.entries' => fn ($q) => $q->orderBy('start_time'),
            'days.entries.activity:id,name,color',
            'members:id,name,email',
            'invitations' => fn ($q) => $q->whereNull('accepted_at'),
        ]);

        $slots = $camp->timeSlots->map(fn (TimeSlot $slot) => [
            'id' => $slot->id,
            'name' => $slot->name,
            'start_time' => substr((string) $slot->start_time, 0, 5),
            'end_time' => substr((string) $slot->end_time, 0, 5),
            'kind' => $slot->kind,
            'color' => $slot->color,
            'position' => $slot->position,
        ])->values();

        $days = $camp->days->map(function (CampDay $day) {
            $entries = $day->entries->map(fn (ProgramEntry $entry) => [
                'id' => $entry->id,
                'activity_id' => $entry->activity_id,
                'activity' => $entry->activity?->only(['id', 'name', 'color']),
                'start_time' => substr((string) $entry->start_time, 0, 5),
                'duration' => $entry->duration,
                'title' => $entry->title,
                'description' => $entry->description,
                'responsible' => $entry->responsible,
                'materials' => $entry->materials,
                'notes' => $entry->notes,
                'is_done' => $entry->is_done,
            ])->values()->all();

            $weekdays = [1 => 'pondelok', 2 => 'utorok', 3 => 'streda', 4 => 'štvrtok', 5 => 'piatok', 6 => 'sobota', 7 => 'nedeľa'];

            return [
                'id' => $day->id,
                'date' => $day->date->toDateString(),
                'weekday' => $weekdays[$day->date->dayOfWeekIso],
                'label' => $day->date->format('j.n.'),
                'is_trip' => $day->is_trip,
                'trip_name' => $day->trip_name,
                'name_days' => $day->name_days,
                'birthdays' => $day->birthdays,
                'materials' => $day->materials,
                'notes' => $day->notes,
                'entries' => $entries,
            ];
        })->values();

        return Inertia::render('camps/Show', [
            'camp' => [
                'id' => $camp->id,
                'name' => $camp->name,
                'year' => $camp->year,
                'description' => $camp->description,
                'start_date' => $camp->start_date->toDateString(),
                'end_date' => $camp->end_date->toDateString(),
                'owner_id' => $camp->owner_id,
                'is_owner' => $camp->owner_id === Auth::id(),
            ],
            'slots' => $slots,
            'days' => $days,
            'members' => $camp->members->map(fn (User $m) => [
                'id' => $m->id,
                'name' => $m->name,
                'email' => $m->email,
                'role' => $m->getAttribute('pivot')?->getAttribute('role'),
            ])->values(),
            'invitations' => $camp->invitations->whereNotNull('email')->map(fn ($i) => [
                'id' => $i->id,
                'email' => $i->email,
                'role' => $i->role,
                'link' => route('invitations.show', $i->token),
            ])->values(),
            'shareLink' => ($share = $camp->invitations->firstWhere('email', null)) ? [
                'id' => $share->id,
                'link' => route('invitations.show', $share->token),
            ] : null,
            'activities' => ($camp->activityLibrary?->activities()->get() ?? collect())
                ->map(fn (Activity $a) => [
                    'id' => $a->id,
                    'name' => $a->name,
                    'category_id' => $a->activity_category_id,
                    'description' => $a->description,
                    'default_duration' => $a->default_duration,
                    'color' => $a->color,
                    'materials' => $a->materials,
                ])->values(),
            'categories' => ($camp->activityLibrary?->categories()->get() ?? collect())
                ->map(fn ($c) => $c->only(['id', 'name', 'color']))->values(),
            'library' => $camp->activityLibrary?->only(['id', 'name']),
        ]);
    }

    public function update(Request $request, Camp $camp): RedirectResponse
    {
        $this->authorize('update', $camp);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'description' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $camp->update($data);

        return back()->with('toast', ['type' => 'success', 'message' => __('Camp updated.')]);
    }

    public function destroy(Camp $camp): RedirectResponse
    {
        $this->authorize('delete', $camp);

        $camp->delete();

        return to_route('camps.index')->with('toast', [
            'type' => 'success',
            'message' => __('Camp deleted.'),
        ]);
    }

    /**
     * Duplicate a camp's structure (slots + activities + program) into a new session.
     */
    public function duplicate(Request $request, Camp $camp): RedirectResponse
    {
        $this->authorize('view', $camp);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'copy_program' => ['boolean'],
        ]);

        $copyProgram = (bool) ($data['copy_program'] ?? true);

        $new = DB::transaction(function () use ($camp, $data, $copyProgram, $request) {
            $new = Camp::create([
                'owner_id' => $request->user()->id,
                // The duplicate shares the source camp's activity library.
                'activity_library_id' => $camp->activity_library_id,
                'name' => $data['name'],
                'year' => $data['year'],
                'description' => $camp->description,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
            ]);
            $new->addMember($request->user(), 'owner');

            // Copy the time-block guides.
            foreach ($camp->timeSlots()->get() as $slot) {
                $new->timeSlots()->create($slot->only([
                    'name', 'start_time', 'end_time', 'kind', 'color', 'position',
                ]));
            }

            $this->generateDays($new);

            // Optionally copy the program content onto matching days by position.
            if ($copyProgram) {
                $oldDays = $camp->days()->with('entries')->get()->values();
                $newDays = $new->days()->get()->values();

                foreach ($oldDays as $i => $oldDay) {
                    $target = $newDays[$i] ?? null;
                    if (! $target) {
                        break;
                    }
                    $target->update([
                        'is_trip' => $oldDay->is_trip,
                        'trip_name' => $oldDay->trip_name,
                    ]);
                    foreach ($oldDay->entries as $entry) {
                        $target->entries()->create([
                            'activity_id' => $entry->activity_id,
                            'start_time' => $entry->start_time,
                            'duration' => $entry->duration,
                            'title' => $entry->title,
                            'description' => $entry->description,
                            'responsible' => $entry->responsible,
                            'materials' => $entry->materials,
                            'notes' => $entry->notes,
                        ]);
                    }
                }
            }

            return $new;
        });

        return to_route('camps.show', $new)->with('toast', [
            'type' => 'success',
            'message' => __('Camp duplicated.'),
        ]);
    }

    /**
     * Create the default daily time skeleton for a new camp.
     */
    protected function seedDefaultSlots(Camp $camp): void
    {
        $defaults = [
            ['name' => 'Ranné chvály', 'start_time' => '08:00', 'end_time' => '08:30', 'kind' => 'activity', 'color' => 'sky'],
            ['name' => 'Scénka', 'start_time' => '08:30', 'end_time' => '09:00', 'kind' => 'activity', 'color' => 'violet'],
            ['name' => 'BLOK I.', 'start_time' => '09:00', 'end_time' => '12:00', 'kind' => 'activity', 'color' => 'emerald'],
            ['name' => 'Obed', 'start_time' => '12:00', 'end_time' => '12:30', 'kind' => 'fixed', 'color' => 'amber'],
            ['name' => 'Odpočinok', 'start_time' => '12:30', 'end_time' => '13:30', 'kind' => 'fixed', 'color' => 'slate'],
            ['name' => 'Scénka', 'start_time' => '13:30', 'end_time' => '13:45', 'kind' => 'activity', 'color' => 'violet'],
            ['name' => 'BLOK II.', 'start_time' => '13:45', 'end_time' => '15:30', 'kind' => 'activity', 'color' => 'emerald'],
            ['name' => 'Slovko', 'start_time' => '15:30', 'end_time' => '16:00', 'kind' => 'activity', 'color' => 'rose'],
        ];

        foreach ($defaults as $i => $slot) {
            $camp->timeSlots()->create([...$slot, 'position' => $i]);
        }
    }

    /**
     * Generate a CampDay for each calendar day between start and end, skipping none.
     */
    protected function generateDays(Camp $camp): void
    {
        $existing = $camp->days()->pluck('date')->map(fn ($d) => Carbon::parse($d)->toDateString())->all();

        // Dates are cast to CarbonImmutable, so advance by reassigning addDay()'s result.
        $cursor = Carbon::parse($camp->start_date->toDateString());
        $end = Carbon::parse($camp->end_date->toDateString());
        $position = $camp->days()->count();

        while ($cursor->lte($end)) {
            if (! in_array($cursor->toDateString(), $existing, true)) {
                $camp->days()->create([
                    'date' => $cursor->toDateString(),
                    'position' => $position++,
                    // Tuesdays and Thursdays default to trip days.
                    'is_trip' => in_array($cursor->dayOfWeek, [Carbon::TUESDAY, Carbon::THURSDAY], true),
                    'name_days' => NameDays::for($cursor),
                ]);
            }
            $cursor = $cursor->addDay();
        }
    }

    /**
     * Fill in Slovak name days for every camp day that doesn't have one yet.
     */
    public function fillNameDays(Camp $camp): RedirectResponse
    {
        $this->authorize('update', $camp);

        $filled = 0;
        $days = $camp->days()
            ->where(fn ($q) => $q->whereNull('name_days')->orWhere('name_days', ''))
            ->get();

        foreach ($days as $day) {
            if ($name = NameDays::for($day->date)) {
                $day->update(['name_days' => $name]);
                $filled++;
            }
        }

        return back()->with('toast', [
            'type' => 'success',
            'message' => __('Filled name days for :count days.', ['count' => $filled]),
        ]);
    }
}
