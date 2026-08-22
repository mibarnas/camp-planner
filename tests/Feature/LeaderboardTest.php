<?php

use App\Models\ProgramEntry;
use App\Models\User;

it('adds raw results straight into the standings', function () {
    [$camp, $owner, $entry, $a, $b] = makePointsCamp(ProgramEntry::POINTS_RAW);

    $this->actingAs($owner)->put("/program-entries/{$entry->id}/points", [
        'points' => [
            ['camp_group_id' => $a->id, 'value' => 12],
            ['camp_group_id' => $b->id, 'value' => 8],
        ],
    ]);

    $this->actingAs($owner)
        ->get("/camps/{$camp->id}/leaderboard")
        ->assertInertia(fn ($page) => $page
            ->component('camps/Leaderboard')
            ->where('standings.0.group_id', $a->id)
            ->where('standings.0.total', 12)
            ->where('standings.1.total', 8)
        );
});

it('turns results into placement points', function () {
    [$camp, $owner, $entry, $a, $b] = makePointsCamp(ProgramEntry::POINTS_PLACEMENT);

    // The raw numbers only rank the groups: two competitors, so 2 and 1 points.
    $this->actingAs($owner)->put("/program-entries/{$entry->id}/points", [
        'points' => [
            ['camp_group_id' => $a->id, 'value' => 90],
            ['camp_group_id' => $b->id, 'value' => 30],
        ],
    ]);

    $this->actingAs($owner)
        ->get("/camps/{$camp->id}/leaderboard")
        ->assertInertia(fn ($page) => $page
            ->where('standings.0.group_id', $a->id)
            ->where('standings.0.total', 2)
            ->where('standings.1.total', 1)
            ->where('days.0.entries.0.rows.0.awarded', 2)
        );
});

it('splits placement points when two groups tie', function () {
    [$camp, $owner, $entry, $a, $b] = makePointsCamp(ProgramEntry::POINTS_PLACEMENT);

    $this->actingAs($owner)->put("/program-entries/{$entry->id}/points", [
        'points' => [
            ['camp_group_id' => $a->id, 'value' => 50],
            ['camp_group_id' => $b->id, 'value' => 50],
        ],
    ]);

    $this->actingAs($owner)
        ->get("/camps/{$camp->id}/leaderboard")
        ->assertInertia(fn ($page) => $page
            ->where('standings.0.total', 1.5)
            ->where('standings.1.total', 1.5)
        );
});

it('leaves a group without a recorded result out of the scoring', function () {
    [$camp, $owner, $entry, $a] = makePointsCamp(ProgramEntry::POINTS_PLACEMENT);

    $this->actingAs($owner)->put("/program-entries/{$entry->id}/points", [
        'points' => [['camp_group_id' => $a->id, 'value' => 90]],
    ]);

    $this->actingAs($owner)
        ->get("/camps/{$camp->id}/leaderboard")
        ->assertInertia(fn ($page) => $page
            // Only one group was ranked, so it takes a single point.
            ->where('standings.0.total', 1)
            ->where('standings.1.total', 0)
            ->where('days.0.entries.0.rows.1.value', null)
            ->where('days.0.entries.0.rows.1.awarded', null)
        );
});

it('re-settles the standings when a group stops competing', function () {
    [$camp, $owner, $entry, $a, $b] = makePointsCamp(ProgramEntry::POINTS_PLACEMENT);

    $this->actingAs($owner)->put("/program-entries/{$entry->id}/points", [
        'points' => [
            ['camp_group_id' => $a->id, 'value' => 90],
            ['camp_group_id' => $b->id, 'value' => 30],
        ],
    ]);

    $b->update(['competes' => false]);

    $this->actingAs($owner)
        ->get("/camps/{$camp->id}/leaderboard")
        ->assertInertia(fn ($page) => $page
            ->has('groups', 1)
            // Alone in the ranking now, so the winner scores a single point.
            ->where('standings.0.total', 1)
        );
});

it('shows only the activities that are actually scored', function () {
    [$camp, $owner] = makePointsCamp(ProgramEntry::POINTS_RAW);
    $camp->days()->first()->entries()->create([
        'start_time' => '11:00',
        'duration' => 60,
        'title' => 'Obyčajná hra',
    ]);

    $this->actingAs($owner)
        ->get("/camps/{$camp->id}/leaderboard")
        ->assertInertia(fn ($page) => $page
            ->has('days', 1)
            ->has('days.0.entries', 1)
            ->where('days.0.entries.0.title', 'Olympiáda')
        );
});

it('keeps strangers off the leaderboard', function () {
    [$camp] = makePointsCamp();
    $stranger = User::factory()->create();

    $this->actingAs($stranger)->get("/camps/{$camp->id}/leaderboard")->assertForbidden();
});
