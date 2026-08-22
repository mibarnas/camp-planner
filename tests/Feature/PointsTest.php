<?php

use App\Models\Camp;
use App\Models\CampGroup;
use App\Models\EntryGroupPoint;
use App\Models\ProgramEntry;
use App\Models\User;

/**
 * A camp with two competing groups and one scoring activity.
 *
 * @return array{0: Camp, 1: User, 2: ProgramEntry, 3: CampGroup, 4: CampGroup}
 */
function makePointsCamp(string $mode = ProgramEntry::POINTS_RAW): array
{
    $owner = User::factory()->create();

    $camp = Camp::create([
        'owner_id' => $owner->id,
        'name' => 'Tábor',
        'year' => 2026,
        'start_date' => '2026-07-13',
        'end_date' => '2026-07-14',
    ]);
    $camp->addMember($owner, 'owner');

    foreach (['2026-07-13', '2026-07-14'] as $i => $date) {
        $camp->days()->create(['date' => $date, 'position' => $i]);
    }

    $entry = $camp->days()->first()->entries()->create([
        'start_time' => '09:00',
        'duration' => 60,
        'title' => 'Olympiáda',
        'points_mode' => $mode,
    ]);

    $a = $camp->groups()->create(['name' => 'Levíčatá', 'competes' => true]);
    $b = $camp->groups()->create(['name' => 'Sovy', 'competes' => true]);

    return [$camp, $owner, $entry, $a, $b];
}

it('records and updates a group result', function () {
    [, $owner, $entry, $a, $b] = makePointsCamp();

    $this->actingAs($owner)->put("/program-entries/{$entry->id}/points", [
        'points' => [
            ['camp_group_id' => $a->id, 'value' => 12],
            ['camp_group_id' => $b->id, 'value' => 8],
        ],
    ])->assertRedirect();

    expect(EntryGroupPoint::count())->toBe(2)
        ->and($entry->groupPoints()->where('camp_group_id', $a->id)->firstOrFail()->value)->toBe(12.0)
        ->and($entry->groupPoints()->where('camp_group_id', $a->id)->firstOrFail()->recorded_by)->toBe($owner->id);

    $this->actingAs($owner)->put("/program-entries/{$entry->id}/points", [
        'points' => [['camp_group_id' => $a->id, 'value' => 20]],
    ])->assertRedirect();

    expect(EntryGroupPoint::count())->toBe(2)
        ->and($entry->groupPoints()->where('camp_group_id', $a->id)->firstOrFail()->value)->toBe(20.0);
});

it('clears a result when the value is sent empty', function () {
    [, $owner, $entry, $a] = makePointsCamp();

    $this->actingAs($owner)->put("/program-entries/{$entry->id}/points", [
        'points' => [['camp_group_id' => $a->id, 'value' => 12]],
    ]);
    $this->actingAs($owner)->put("/program-entries/{$entry->id}/points", [
        'points' => [['camp_group_id' => $a->id, 'value' => null]],
    ])->assertRedirect();

    expect(EntryGroupPoint::count())->toBe(0);
});

it('lets any member record for any group, not only their own', function () {
    [$camp, , $entry, $a] = makePointsCamp();
    $other = User::factory()->create();
    $camp->addMember($other, 'leader');

    $this->actingAs($other)->put("/program-entries/{$entry->id}/points", [
        'points' => [['camp_group_id' => $a->id, 'value' => 5]],
    ])->assertRedirect();

    expect(EntryGroupPoint::count())->toBe(1);
});

it('keeps recording available while the schedule is frozen', function () {
    [$camp, $owner, $entry, $a] = makePointsCamp();
    $camp->update(['schedule_locked_at' => now()]);

    $this->actingAs($owner)->put("/program-entries/{$entry->id}/points", [
        'points' => [['camp_group_id' => $a->id, 'value' => 5]],
    ])->assertRedirect();

    expect(EntryGroupPoint::count())->toBe(1);
});

it('ignores groups that do not compete or belong elsewhere', function () {
    [$camp, $owner, $entry] = makePointsCamp();
    $bench = $camp->groups()->create(['name' => 'Fotografi', 'competes' => false]);
    [, , , $foreign] = makePointsCamp();

    $this->actingAs($owner)->put("/program-entries/{$entry->id}/points", [
        'points' => [
            ['camp_group_id' => $bench->id, 'value' => 5],
            ['camp_group_id' => $foreign->id, 'value' => 5],
        ],
    ])->assertRedirect();

    expect(EntryGroupPoint::count())->toBe(0);
});

it('refuses results for an activity that is not scored', function () {
    [, $owner, $entry, $a] = makePointsCamp(ProgramEntry::POINTS_NONE);

    $this->actingAs($owner)->put("/program-entries/{$entry->id}/points", [
        'points' => [['camp_group_id' => $a->id, 'value' => 5]],
    ])->assertSessionHasErrors('points');

    expect(EntryGroupPoint::count())->toBe(0);
});

it('keeps strangers from recording results', function () {
    [, , $entry, $a] = makePointsCamp();
    $stranger = User::factory()->create();

    $this->actingAs($stranger)->put("/program-entries/{$entry->id}/points", [
        'points' => [['camp_group_id' => $a->id, 'value' => 5]],
    ])->assertForbidden();
});

it('sets the scoring mode through the entry itself', function () {
    [, $owner, $entry] = makePointsCamp(ProgramEntry::POINTS_NONE);

    $this->actingAs($owner)->put("/program-entries/{$entry->id}", [
        'points_mode' => 'placement',
    ])->assertRedirect();

    expect($entry->refresh()->points_mode)->toBe('placement');
});

it('rejects a scoring mode it does not know', function () {
    [, $owner, $entry] = makePointsCamp();

    $this->actingAs($owner)
        ->put("/program-entries/{$entry->id}", ['points_mode' => 'vibes'])
        ->assertSessionHasErrors('points_mode');
});

it('sends the mode and recorded results to the planner', function () {
    [$camp, $owner, $entry, $a] = makePointsCamp();

    $this->actingAs($owner)->put("/program-entries/{$entry->id}/points", [
        'points' => [['camp_group_id' => $a->id, 'value' => 12]],
    ]);

    $this->actingAs($owner)
        ->get("/camps/{$camp->id}")
        ->assertInertia(fn ($page) => $page
            ->where('days.0.entries.0.points_mode', 'raw')
            ->has('days.0.entries.0.points', 1)
            ->where('days.0.entries.0.points.0.value', 12)
        );
});

it('flags the groups a leader is responsible for', function () {
    [$camp, $owner, , $a] = makePointsCamp();
    $a->leaders()->sync([$camp->leaders()->firstOrFail()->id]);

    $this->actingAs($owner)
        ->get("/camps/{$camp->id}")
        ->assertInertia(fn ($page) => $page->where('myGroupIds', [$a->id]));
});

it('carries the scoring mode but not the results into a duplicated camp', function () {
    [$camp, $owner, $entry, $a] = makePointsCamp();

    $this->actingAs($owner)->put("/program-entries/{$entry->id}/points", [
        'points' => [['camp_group_id' => $a->id, 'value' => 12]],
    ]);

    $this->actingAs($owner)->post("/camps/{$camp->id}/duplicate", [
        'name' => 'Tábor 2027',
        'year' => 2027,
        'start_date' => '2027-07-12',
        'end_date' => '2027-07-13',
    ])->assertRedirect();

    $new = Camp::where('name', 'Tábor 2027')->firstOrFail();
    $copied = $new->days()->first()->entries()->firstOrFail();

    expect($copied->points_mode)->toBe('raw')
        ->and($copied->groupPoints()->count())->toBe(0)
        // The original results are untouched.
        ->and(EntryGroupPoint::count())->toBe(1);
});
