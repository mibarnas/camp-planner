<?php

use App\Models\Camp;
use App\Models\CampLeader;
use App\Models\User;
use App\Support\NameMatcher;

/**
 * @return array{0: Camp, 1: User} camp, owner
 */
function makeLeaderCamp(): array
{
    $owner = User::factory()->create(['name' => 'Peter Novák']);

    $camp = Camp::create([
        'owner_id' => $owner->id,
        'name' => 'Tábor',
        'year' => 2026,
        'start_date' => '2026-07-13',
        'end_date' => '2026-07-14',
    ]);
    $camp->addMember($owner, 'owner');

    return [$camp, $owner];
}

it('gives a joining member their own leader row', function () {
    [$camp, $owner] = makeLeaderCamp();

    $leader = $camp->leaders()->firstOrFail();

    expect($camp->leaders()->count())->toBe(1)
        ->and($leader->user_id)->toBe($owner->id)
        ->and($leader->name)->toBe('Peter Novák');
});

it('does not duplicate the leader row when addMember runs again', function () {
    [$camp, $owner] = makeLeaderCamp();

    $camp->addMember($owner, 'owner');

    expect($camp->leaders()->count())->toBe(1);
});

it('lets any member add, rename and remove a leader without an account', function () {
    [$camp] = makeLeaderCamp();
    $leader = User::factory()->create();
    $camp->addMember($leader, 'leader');

    $this->actingAs($leader)
        ->post("/camps/{$camp->id}/leaders", ['name' => 'Katka M.', 'color' => 'rose'])
        ->assertRedirect();

    $named = $camp->leaders()->whereNull('user_id')->firstOrFail();
    expect($named->name)->toBe('Katka M.')->and($named->color)->toBe('rose');

    $this->actingAs($leader)
        ->put("/camps/{$camp->id}/leaders/{$named->id}", ['name' => 'Katarína M.'])
        ->assertRedirect();

    expect($named->refresh()->name)->toBe('Katarína M.');

    $this->actingAs($leader)
        ->delete("/camps/{$camp->id}/leaders/{$named->id}")
        ->assertRedirect();

    expect(CampLeader::whereKey($named->id)->exists())->toBeFalse();
});

it('refuses to delete a leader who has an account', function () {
    [$camp, $owner] = makeLeaderCamp();
    $linked = $camp->leaders()->firstOrFail();

    $this->actingAs($owner)
        ->delete("/camps/{$camp->id}/leaders/{$linked->id}")
        ->assertSessionHasErrors('leader');

    expect(CampLeader::whereKey($linked->id)->exists())->toBeTrue();
});

it('keeps leaders of other camps out of reach', function () {
    [$camp, $owner] = makeLeaderCamp();
    [$other] = makeLeaderCamp();
    $foreign = $other->leaders()->firstOrFail();

    $this->actingAs($owner)
        ->put("/camps/{$camp->id}/leaders/{$foreign->id}", ['name' => 'Hacked'])
        ->assertNotFound();
});

it('keeps strangers away from the leader list', function () {
    [$camp] = makeLeaderCamp();
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->post("/camps/{$camp->id}/leaders", ['name' => 'Kto?'])
        ->assertForbidden();
});

it('links a named leader to the account of someone joining under that name', function () {
    [$camp, $owner] = makeLeaderCamp();

    $this->actingAs($owner)
        ->post("/camps/{$camp->id}/leaders", ['name' => 'Zuzka Bielá'])
        ->assertRedirect();

    // The same person, registered without diacritics and with sloppy spacing.
    $zuzka = User::factory()->create(['name' => 'Zuzka  Biela']);
    $camp->addMember($zuzka, 'leader');

    $row = $camp->leaders()->where('name', 'Zuzka Bielá')->firstOrFail();

    // She took over the existing row rather than gaining a second one.
    expect($camp->leaders()->count())->toBe(2)
        ->and($row->user_id)->toBe($zuzka->id);
});

it('matches names regardless of diacritics, case and spacing', function () {
    expect(NameMatcher::matches('Zuzka Biela', 'zuzka  biela'))->toBeTrue()
        ->and(NameMatcher::matches('Ľubomír Šťastný', 'lubomir stastny'))->toBeTrue()
        ->and(NameMatcher::matches(' Katka M. ', 'katka m.'))->toBeTrue()
        ->and(NameMatcher::matches('Katka', 'Jano'))->toBeFalse()
        ->and(NameMatcher::matches(null, ''))->toBeFalse()
        ->and(NameMatcher::matches('', ''))->toBeFalse();
});

it('keeps the leader as a name after their access is removed', function () {
    [$camp, $owner] = makeLeaderCamp();
    $leader = User::factory()->create(['name' => 'Jano Malý']);
    $camp->addMember($leader, 'leader');

    $this->actingAs($owner)
        ->delete("/camps/{$camp->id}/members/{$leader->id}")
        ->assertRedirect();

    $row = $camp->leaders()->where('name', 'Jano Malý')->firstOrFail();

    expect($row->user_id)->toBeNull()
        ->and($camp->members()->count())->toBe(1);
});

it('re-links the same person when they rejoin the camp', function () {
    [$camp, $owner] = makeLeaderCamp();
    $leader = User::factory()->create(['name' => 'Jano Malý']);
    $camp->addMember($leader, 'leader');

    $this->actingAs($owner)->delete("/camps/{$camp->id}/members/{$leader->id}");
    $camp->addMember($leader, 'leader');

    expect($camp->leaders()->count())->toBe(2)
        ->and($camp->leaders()->where('name', 'Jano Malý')->firstOrFail()->user_id)->toBe($leader->id);
});

it('sends the leader list to the planner and the leaders page', function () {
    [$camp, $owner] = makeLeaderCamp();
    $camp->leaders()->create(['name' => 'Katka M.', 'color' => 'rose']);

    $this->actingAs($owner)
        ->get("/camps/{$camp->id}")
        ->assertInertia(fn ($page) => $page->has('leaders', 2));

    $this->actingAs($owner)
        ->get("/camps/{$camp->id}/leaders")
        ->assertInertia(fn ($page) => $page
            ->has('people', 2)
            ->where('people.0.status', 'owner')
            ->where('people.0.email', $owner->email)
            ->where('people.1.name', 'Katka M.')
            ->where('people.1.status', 'name_only')
            ->where('people.1.user_id', null)
        );
});

it('carries leaders into a duplicated camp as plain names', function () {
    [$camp, $owner] = makeLeaderCamp();
    $camp->leaders()->create(['name' => 'Katka M.', 'color' => 'rose']);

    $this->actingAs($owner)->post("/camps/{$camp->id}/duplicate", [
        'name' => 'Tábor 2027',
        'year' => 2027,
        'start_date' => '2027-07-12',
        'end_date' => '2027-07-13',
    ])->assertRedirect();

    $new = Camp::where('name', 'Tábor 2027')->firstOrFail();

    expect($new->leaders()->count())->toBe(2)
        ->and($new->leaders()->where('name', 'Katka M.')->firstOrFail()->user_id)->toBeNull()
        ->and($new->leaders()->where('name', $owner->name)->firstOrFail()->user_id)->toBe($owner->id);
});
