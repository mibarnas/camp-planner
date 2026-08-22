<?php

namespace App\Http\Controllers;

use App\Models\Camp;
use App\Models\CampDay;
use App\Models\CampGroup;
use App\Models\CampLeader;
use App\Models\ProgramEntry;
use App\Support\Placement;
use App\Support\Weekdays;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LeaderboardController extends Controller
{
    /**
     * Standings of the competing groups, plus every scoring activity so results
     * can be filled in right here.
     */
    public function index(Camp $camp): Response
    {
        $this->authorize('view', $camp);

        $groups = $camp->groups()->where('competes', true)->with(['type:id,name', 'leaders:id,name'])->get();

        $camp->load([
            'days',
            'days.entries' => fn ($q) => $q->where('points_mode', '!=', ProgramEntry::POINTS_NONE)
                ->orderBy('start_time'),
            'days.entries.activity:id,name',
            'days.entries.groupPoints',
        ]);

        $totals = array_fill_keys($groups->pluck('id')->all(), 0.0);

        $days = $camp->days
            ->filter(fn (CampDay $day) => $day->entries->isNotEmpty())
            ->map(function (CampDay $day) use (&$totals, $groups) {
                $entries = $day->entries->map(function (ProgramEntry $entry) use (&$totals, $groups) {
                    // Results only count for groups still competing.
                    $values = [];
                    foreach ($entry->groupPoints as $point) {
                        if (array_key_exists($point->camp_group_id, $totals)) {
                            $values[$point->camp_group_id] = $point->value;
                        }
                    }

                    $awards = Placement::awards($values, $entry->points_mode);

                    foreach ($awards as $groupId => $award) {
                        $totals[$groupId] += $award;
                    }

                    return [
                        'id' => $entry->id,
                        'title' => $entry->title ?: $entry->activity?->name ?: 'Aktivita',
                        'start_time' => substr((string) $entry->start_time, 0, 5),
                        'points_mode' => $entry->points_mode,
                        'rows' => $groups->map(fn (CampGroup $group) => [
                            'group_id' => $group->id,
                            'value' => $values[$group->id] ?? null,
                            'awarded' => $awards[$group->id] ?? null,
                        ])->values()->all(),
                    ];
                })->values()->all();

                return [
                    'id' => $day->id,
                    'date' => $day->date->toDateString(),
                    'weekday' => Weekdays::sk($day->date->dayOfWeekIso),
                    'label' => $day->date->format('j.n.'),
                    'entries' => $entries,
                ];
            })->values()->all();

        // The groups the current user leads — their missing results get flagged.
        $myGroupIds = $camp->groups()
            ->whereHas('leaders', fn ($q) => $q->where('user_id', Auth::id()))
            ->pluck('id')->all();

        $standings = collect($totals)
            ->map(fn (float $total, int $groupId) => ['group_id' => $groupId, 'total' => round($total, 2)])
            ->sortByDesc('total')
            ->values()->all();

        return Inertia::render('camps/Leaderboard', [
            'camp' => [
                'id' => $camp->id,
                'name' => $camp->name,
            ],
            'groups' => $groups->map(fn (CampGroup $group) => [
                'id' => $group->id,
                'name' => $group->name,
                'color' => $group->color,
                'type_name' => $group->type?->name,
                'leaders' => $group->leaders->map(fn (CampLeader $l) => $l->name)->values()->all(),
            ])->values()->all(),
            'standings' => $standings,
            'days' => $days,
            'myGroupIds' => $myGroupIds,
            'today' => now()->toDateString(),
        ]);
    }
}
