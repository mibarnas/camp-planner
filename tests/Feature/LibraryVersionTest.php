<?php

use App\Models\Activity;
use App\Models\ActivityLibrary;
use App\Models\Camp;
use App\Models\LibraryVersion;
use App\Models\User;

/**
 * @return array{0: ActivityLibrary, 1: User, 2: User} library, owner, member
 */
function makeLibrary(): array
{
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $library = ActivityLibrary::create(['name' => 'Knižnica', 'owner_id' => $owner->id]);
    $library->addMember($owner, 'owner');
    $library->addMember($member, 'member');

    $library->categories()->create(['name' => 'Hra', 'color' => 'emerald', 'position' => 0]);

    return [$library->refresh(), $owner, $member];
}

it('saves a library version in the export payload shape', function () {
    [$library, $owner, $member] = makeLibrary();
    $library->activities()->create([
        'name' => 'Zoznamovačky',
        'default_duration' => 60,
        'created_by' => $owner->id,
        'activity_category_id' => $library->categories->first()->id,
    ]);

    $this->actingAs($member)->post("/libraries/{$library->id}/versions", ['name' => 'V1'])->assertForbidden();
    $this->actingAs($owner)->post("/libraries/{$library->id}/versions", ['name' => 'V1'])->assertRedirect();

    $payload = LibraryVersion::firstOrFail()->payload;

    expect($payload['format'])->toBe(LibraryVersion::FORMAT)
        ->and($payload['version'])->toBe(LibraryVersion::VERSION)
        ->and($payload['activities'])->toHaveCount(1)
        ->and($payload['activities'][0]['name'])->toBe('Zoznamovačky')
        ->and($payload['activities'][0]['category'])->toBe('Hra')
        ->and($payload['categories'][0]['name'])->toBe('Hra');
});

it('restores matched activities in place, adds missing ones and drops the rest', function () {
    [$library, $owner] = makeLibrary();
    $kept = $library->activities()->create([
        'name' => 'Zoznamovačky', 'default_duration' => 60, 'created_by' => $owner->id,
    ]);

    $this->actingAs($owner)->post("/libraries/{$library->id}/versions", ['name' => 'V1']);

    // Diverge: edit the kept one, drop the snapshot's, add one it doesn't know about.
    $kept->update(['default_duration' => 15, 'description' => 'Zmenené']);
    $extra = $library->activities()->create([
        'name' => 'Nová hra', 'default_duration' => 30, 'created_by' => $owner->id,
    ]);

    $this->actingAs($owner)
        ->post("/libraries/{$library->id}/versions/".LibraryVersion::firstOrFail()->id.'/restore')
        ->assertRedirect();

    $kept->refresh();

    expect($library->activities()->count())->toBe(1)
        ->and($kept->default_duration)->toBe(60)
        ->and($kept->description)->toBeNull()
        ->and(Activity::find($extra->id))->toBeNull()
        ->and(LibraryVersion::where('name', __('Before restoring: :name', ['name' => 'V1']))->exists())->toBeTrue();
});

it('keeps schedule cells when a restore deletes the activity they point at', function () {
    [$library, $owner] = makeLibrary();

    $camp = Camp::create([
        'owner_id' => $owner->id,
        'activity_library_id' => $library->id,
        'name' => 'Tábor',
        'year' => 2026,
        'start_date' => '2026-07-13',
        'end_date' => '2026-07-13',
    ]);
    $camp->addMember($owner, 'owner');
    $day = $camp->days()->create(['date' => '2026-07-13', 'position' => 0]);

    $survives = $library->activities()->create([
        'name' => 'Zoznamovačky', 'default_duration' => 60, 'created_by' => $owner->id,
    ]);

    $this->actingAs($owner)->post("/libraries/{$library->id}/versions", ['name' => 'V1']);

    // Added after the snapshot, so the restore will delete it.
    $doomed = $library->activities()->create([
        'name' => 'Nočná hra', 'default_duration' => 90, 'created_by' => $owner->id,
    ]);

    $linked = $day->entries()->create([
        'activity_id' => $survives->id, 'start_time' => '09:00', 'duration' => 60, 'title' => 'Ranná',
    ]);
    $orphaned = $day->entries()->create([
        'activity_id' => $doomed->id, 'start_time' => '21:00', 'duration' => 90, 'title' => 'Nočná hra',
    ]);

    $this->actingAs($owner)
        ->post("/libraries/{$library->id}/versions/".LibraryVersion::firstOrFail()->id.'/restore')
        ->assertRedirect();

    expect($linked->refresh()->activity_id)->toBe($survives->id)
        ->and($orphaned->refresh()->activity_id)->toBeNull()
        ->and($orphaned->title)->toBe('Nočná hra');
});

it('recreates a category the snapshot needs but the library lost', function () {
    [$library, $owner] = makeLibrary();
    $library->activities()->create([
        'name' => 'Zoznamovačky',
        'default_duration' => 60,
        'created_by' => $owner->id,
        'activity_category_id' => $library->categories->first()->id,
    ]);

    $this->actingAs($owner)->post("/libraries/{$library->id}/versions", ['name' => 'V1']);

    $library->categories()->delete();
    expect($library->categories()->count())->toBe(0);

    $this->actingAs($owner)
        ->post("/libraries/{$library->id}/versions/".LibraryVersion::firstOrFail()->id.'/restore')
        ->assertRedirect();

    $category = $library->categories()->firstOrFail();

    expect($category->name)->toBe('Hra')
        ->and($library->activities()->firstOrFail()->activity_category_id)->toBe($category->id);
});

it('refuses to restore a version saved in an unsupported format', function () {
    [$library, $owner] = makeLibrary();
    $library->activities()->create([
        'name' => 'Zoznamovačky', 'default_duration' => 60, 'created_by' => $owner->id,
    ]);

    $version = LibraryVersion::create([
        'activity_library_id' => $library->id,
        'user_id' => $owner->id,
        'name' => 'Z budúcnosti',
        'payload' => ['format' => LibraryVersion::FORMAT, 'version' => 99, 'activities' => []],
    ]);

    $this->actingAs($owner)
        ->post("/libraries/{$library->id}/versions/{$version->id}/restore")
        ->assertSessionHas('toast.type', 'error');

    expect($library->activities()->count())->toBe(1)
        ->and(LibraryVersion::count())->toBe(1); // no backup written
});

it('sends the library versions to the activities page', function () {
    [$library, $owner] = makeLibrary();
    $this->actingAs($owner)->post("/libraries/{$library->id}/versions", ['name' => 'V1']);

    $this->actingAs($owner)
        ->get('/activities?library='.$library->id)
        ->assertInertia(fn ($page) => $page
            ->has('versions', 1)
            ->where('versions.0.name', 'V1')
            ->where('versions.0.author', $owner->name)
        );
});

it('deletes a version', function () {
    [$library, $owner, $member] = makeLibrary();
    $this->actingAs($owner)->post("/libraries/{$library->id}/versions", ['name' => 'V1']);
    $version = LibraryVersion::firstOrFail();

    $this->actingAs($member)->delete("/libraries/{$library->id}/versions/{$version->id}")->assertForbidden();
    $this->actingAs($owner)->delete("/libraries/{$library->id}/versions/{$version->id}")->assertRedirect();

    expect(LibraryVersion::count())->toBe(0);
});
