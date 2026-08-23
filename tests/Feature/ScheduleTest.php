<?php

use App\Models\ActivityLibrary;
use App\Models\Camp;
use App\Models\PlanVersion;
use App\Models\ProgramEntry;
use App\Models\TimeSlot;
use App\Models\User;

/**
 * @return array{0: Camp, 1: User, 2: User} camp, owner, leader
 */
function makeCamp(): array
{
    $owner = User::factory()->create();
    $leader = User::factory()->create();

    $camp = Camp::create([
        'owner_id' => $owner->id,
        'name' => 'Tábor',
        'year' => 2026,
        'start_date' => '2026-07-13',
        'end_date' => '2026-07-15',
    ]);
    $camp->addMember($owner, 'owner');
    $camp->addMember($leader, 'leader');

    $camp->timeSlots()->create([
        'name' => 'Obed', 'start_time' => '12:00', 'end_time' => '12:30',
        'kind' => 'fixed', 'color' => 'amber', 'position' => 0,
    ]);

    foreach (['2026-07-13', '2026-07-14', '2026-07-15'] as $i => $date) {
        $camp->days()->create(['date' => $date, 'position' => $i]);
    }

    return [$camp->refresh(), $owner, $leader];
}

it('sets an entry status', function () {
    [$camp, $owner] = makeCamp();
    $entry = $camp->days->first()->entries()->create(['start_time' => '09:00', 'duration' => 60]);

    expect($entry->status)->toBe('none');

    $this->actingAs($owner)
        ->put("/program-entries/{$entry->id}/status", ['status' => 'todo'])
        ->assertRedirect();

    expect($entry->refresh()->status)->toBe('todo');

    $this->actingAs($owner)
        ->put("/program-entries/{$entry->id}/status", ['status' => 'nonsense'])
        ->assertSessionHasErrors('status');
});

it('creates a simple block with no library link', function () {
    [$camp, $owner] = makeCamp();

    $this->actingAs($owner)->post('/program-entries', [
        'camp_day_id' => $camp->days->first()->id,
        'kind' => 'simple',
        'start_time' => '08:00',
        'duration' => 45,
        'title' => 'Raňajky',
    ])->assertRedirect();

    $entry = ProgramEntry::firstOrFail();

    expect($entry->kind)->toBe('simple')
        ->and($entry->title)->toBe('Raňajky')
        ->and($entry->activity_id)->toBeNull()
        ->and($entry->isSimple())->toBeTrue();
});

it('defaults an entry to a detailed activity', function () {
    [$camp, $owner] = makeCamp();

    $this->actingAs($owner)->post('/program-entries', [
        'camp_day_id' => $camp->days->first()->id,
        'start_time' => '09:00',
        'duration' => 60,
        'title' => 'Zoznamovačky',
    ])->assertRedirect();

    expect(ProgramEntry::firstOrFail()->kind)->toBe('detailed');
});

it('drops the library link when an activity becomes a simple block', function () {
    [$camp, $owner] = makeCamp();
    $library = $camp->activityLibrary ?? ActivityLibrary::create([
        'name' => 'Knižnica', 'owner_id' => $owner->id,
    ]);
    $activity = $library->activities()->create([
        'name' => 'Zoznamovačky', 'default_duration' => 60, 'created_by' => $owner->id,
    ]);
    $entry = $camp->days->first()->entries()->create([
        'activity_id' => $activity->id,
        'start_time' => '09:00',
        'duration' => 60,
        'description' => 'Scenár…',
    ]);

    $this->actingAs($owner)
        ->put("/program-entries/{$entry->id}", ['kind' => 'simple'])
        ->assertRedirect();

    $entry->refresh();

    expect($entry->kind)->toBe('simple')
        ->and($entry->activity_id)->toBeNull()
        // The scenario survives, so switching back is not destructive.
        ->and($entry->description)->toBe('Scenár…');
});

it('sends the schedule state to the camp page', function () {
    [$camp, $owner] = makeCamp();
    $day = $camp->days->first();
    $day->entries()->create(['start_time' => '09:00', 'duration' => 60, 'status' => 'todo']);
    $day->slotOverrides()->create([
        'time_slot_id' => $camp->timeSlots->first()->id,
        'start_time' => '13:00',
        'end_time' => '13:30',
    ]);
    $this->actingAs($owner)->post("/camps/{$camp->id}/versions", ['name' => 'V1']);

    $this->actingAs($owner)->get("/camps/{$camp->id}")
        ->assertInertia(fn ($page) => $page
            ->component('camps/Show')
            ->where('camp.schedule_locked', false)
            ->where('days.0.entries.0.status', 'todo')
            ->where('days.0.slot_overrides.0.start_time', '13:00')
            ->where('days.1.slot_overrides', [])
            ->where('planVersions.0.name', 'V1')
            ->where('planVersions.0.author', $owner->name)
        );
});

it('overrides a block for one day only', function () {
    [$camp, $owner] = makeCamp();
    $slot = $camp->timeSlots->first();
    $day = $camp->days->first();
    $otherDay = $camp->days->last();

    $this->actingAs($owner)
        ->put("/days/{$day->id}/slots/{$slot->id}/override", [
            'start_time' => '13:00',
            'end_time' => '13:30',
        ])->assertRedirect();

    expect($day->slotOverrides()->count())->toBe(1)
        ->and($otherDay->slotOverrides()->count())->toBe(0)
        ->and(substr((string) $day->slotOverrides()->first()->start_time, 0, 5))->toBe('13:00');

    // Resetting drops the row so the day follows the template again.
    $this->actingAs($owner)
        ->delete("/days/{$day->id}/slots/{$slot->id}/override")
        ->assertRedirect();

    expect($day->slotOverrides()->count())->toBe(0);
});

it('drops an override that no longer deviates from the template', function () {
    [$camp, $owner] = makeCamp();
    $slot = $camp->timeSlots->first();
    $day = $camp->days->first();

    $this->actingAs($owner)->put("/days/{$day->id}/slots/{$slot->id}/override", ['is_hidden' => true]);
    expect($day->slotOverrides()->count())->toBe(1);

    $this->actingAs($owner)->put("/days/{$day->id}/slots/{$slot->id}/override", ['is_hidden' => false]);
    expect($day->slotOverrides()->count())->toBe(0);
});

it('clears an override once the block is back at its template times', function () {
    [$camp, $owner] = makeCamp();
    $slot = $camp->timeSlots->first();
    $day = $camp->days->first();

    $this->actingAs($owner)->put("/days/{$day->id}/slots/{$slot->id}/override", [
        'start_time' => '13:00', 'end_time' => '13:30',
    ]);
    expect($day->slotOverrides()->count())->toBe(1);

    $this->actingAs($owner)->put("/days/{$day->id}/slots/{$slot->id}/override", [
        'start_time' => '12:00', 'end_time' => '12:30',
    ]);
    expect($day->slotOverrides()->count())->toBe(0);
});

it('rejects an override for a slot from another camp', function () {
    [$camp, $owner] = makeCamp();
    [$otherCamp] = makeCamp();

    $this->actingAs($owner)
        ->put("/days/{$camp->days->first()->id}/slots/{$otherCamp->timeSlots->first()->id}/override", [
            'start_time' => '13:00',
        ])->assertNotFound();
});

it('freezes the schedule for everyone including the owner', function () {
    [$camp, $owner, $leader] = makeCamp();
    $entry = $camp->days->first()->entries()->create(['start_time' => '09:00', 'duration' => 60]);

    $this->actingAs($owner)->post("/camps/{$camp->id}/lock")->assertRedirect();
    expect($camp->refresh()->isScheduleLocked())->toBeTrue();

    foreach ([$owner, $leader] as $user) {
        $this->actingAs($user)
            ->put("/program-entries/{$entry->id}", ['start_time' => '10:00'])
            ->assertForbidden();
    }

    // Day notes stay editable while the program is frozen.
    $this->actingAs($leader)
        ->put("/days/{$camp->days->first()->id}", ['notes' => 'Prší.'])
        ->assertRedirect();

    $this->actingAs($leader)->post("/camps/{$camp->id}/lock")->assertForbidden();
    $this->actingAs($owner)->delete("/camps/{$camp->id}/lock")->assertRedirect();

    expect($camp->refresh()->isScheduleLocked())->toBeFalse();

    $this->actingAs($leader)
        ->put("/program-entries/{$entry->id}", ['start_time' => '10:00'])
        ->assertRedirect();
});

it('saves and restores a plan version, keeping a backup', function () {
    [$camp, $owner, $leader] = makeCamp();
    $day = $camp->days->first();
    $slot = $camp->timeSlots->first();

    $day->entries()->create(['start_time' => '09:00', 'duration' => 60, 'title' => 'Pôvodná']);
    $day->slotOverrides()->create(['time_slot_id' => $slot->id, 'start_time' => '13:00', 'end_time' => '13:30']);

    $this->actingAs($leader)->post("/camps/{$camp->id}/versions", ['name' => 'V1'])->assertForbidden();
    $this->actingAs($owner)->post("/camps/{$camp->id}/versions", ['name' => 'V1'])->assertRedirect();

    $version = PlanVersion::firstOrFail();
    expect($version->payload['days'][0]['entries'])->toHaveCount(1)
        ->and($version->payload['days'][0]['overrides'][0]['slot_key'])->toBe(0);

    // Diverge from the saved state, then roll back to it.
    $day->entries()->delete();
    $day->entries()->create(['start_time' => '14:00', 'duration' => 30, 'title' => 'Neskoršia']);
    $camp->timeSlots()->create([
        'name' => 'Večera', 'start_time' => '18:00', 'end_time' => '18:30',
        'kind' => 'fixed', 'position' => 1,
    ]);

    $this->actingAs($owner)
        ->post("/camps/{$camp->id}/versions/{$version->id}/restore")
        ->assertRedirect();

    $restored = $day->entries()->get();
    expect($restored)->toHaveCount(1)
        ->and($restored->first()->title)->toBe('Pôvodná')
        ->and($camp->timeSlots()->count())->toBe(1)
        ->and($day->slotOverrides()->count())->toBe(1)
        ->and(PlanVersion::where('name', __('Before restoring: :name', ['name' => 'V1']))->exists())->toBeTrue();
});

it('restores onto days matched by date and skips dates that are gone', function () {
    [$camp, $owner] = makeCamp();
    $camp->days->last()->entries()->create(['start_time' => '09:00', 'duration' => 60, 'title' => 'Posledný deň']);

    $this->actingAs($owner)->post("/camps/{$camp->id}/versions", ['name' => 'V1']);

    // Shorten the camp: the snapshot's last day no longer exists.
    $this->actingAs($owner)->put("/camps/{$camp->id}", [
        'name' => $camp->name,
        'year' => $camp->year,
        'start_date' => '2026-07-13',
        'end_date' => '2026-07-14',
    ])->assertRedirect();

    expect($camp->refresh()->days()->count())->toBe(2);

    $this->actingAs($owner)
        ->post("/camps/{$camp->id}/versions/".PlanVersion::firstOrFail()->id.'/restore')
        ->assertRedirect();

    expect(ProgramEntry::count())->toBe(0);
});

it('refuses to restore while the schedule is frozen', function () {
    [$camp, $owner] = makeCamp();
    $camp->days->first()->entries()->create(['start_time' => '09:00', 'duration' => 60, 'title' => 'Pôvodná']);

    $this->actingAs($owner)->post("/camps/{$camp->id}/versions", ['name' => 'V1']);
    $this->actingAs($owner)->post("/camps/{$camp->id}/lock");

    $version = PlanVersion::firstOrFail();
    $this->actingAs($owner)
        ->post("/camps/{$camp->id}/versions/{$version->id}/restore")
        ->assertSessionHas('toast.type', 'error');

    expect(PlanVersion::count())->toBe(1); // no backup written
});

it('carries per-day overrides into a duplicated camp', function () {
    [$camp, $owner] = makeCamp();
    $day = $camp->days->first();
    $day->slotOverrides()->create([
        'time_slot_id' => $camp->timeSlots->first()->id,
        'is_hidden' => true,
    ]);

    $this->actingAs($owner)->post("/camps/{$camp->id}/duplicate", [
        'name' => 'Kópia',
        'year' => 2027,
        'start_date' => '2027-07-13',
        'end_date' => '2027-07-15',
        'copy_program' => true,
    ])->assertRedirect();

    $copy = Camp::where('name', 'Kópia')->firstOrFail();
    $copiedOverride = $copy->days()->first()->slotOverrides()->first();

    expect($copiedOverride)->not->toBeNull()
        ->and($copiedOverride->is_hidden)->toBeTrue()
        ->and($copiedOverride->time_slot_id)->toBe($copy->timeSlots()->first()->id)
        ->and(TimeSlot::where('camp_id', $camp->id)->count())->toBe(1);
});
