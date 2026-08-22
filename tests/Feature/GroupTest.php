<?php

use App\Models\Camp;
use App\Models\CampGroup;
use App\Models\GroupType;
use App\Models\User;

/**
 * @return array{0: Camp, 1: User, 2: User} camp, owner, invited leader
 */
function makeGroupCamp(): array
{
    $owner = User::factory()->create();
    $leader = User::factory()->create();

    $camp = Camp::create([
        'owner_id' => $owner->id,
        'name' => 'Tábor',
        'year' => 2026,
        'start_date' => '2026-07-13',
        'end_date' => '2026-07-14',
    ]);
    $camp->addMember($owner, 'owner');
    $camp->addMember($leader, 'leader');

    return [$camp, $owner, $leader];
}

it('lets an invited leader manage group types', function () {
    [$camp, , $leader] = makeGroupCamp();

    $this->actingAs($leader)
        ->post("/camps/{$camp->id}/group-types", ['name' => 'Detské skupiny', 'color' => 'sky'])
        ->assertRedirect();

    $type = $camp->groupTypes()->firstOrFail();
    expect($type->name)->toBe('Detské skupiny')->and($type->color)->toBe('sky');

    $this->actingAs($leader)
        ->put("/camps/{$camp->id}/group-types/{$type->id}", ['name' => 'Oddiely'])
        ->assertRedirect();

    expect($type->refresh()->name)->toBe('Oddiely');

    $this->actingAs($leader)
        ->delete("/camps/{$camp->id}/group-types/{$type->id}")
        ->assertRedirect();

    expect(GroupType::count())->toBe(0);
});

it('creates a group with a type and assigned leaders', function () {
    [$camp, $owner] = makeGroupCamp();
    $type = $camp->groupTypes()->create(['name' => 'Detské skupiny']);
    $leaderIds = $camp->leaders()->pluck('id')->all();

    $this->actingAs($owner)->post("/camps/{$camp->id}/groups", [
        'name' => 'Levíčatá',
        'group_type_id' => $type->id,
        'competes' => true,
        'color' => 'amber',
        'leader_ids' => $leaderIds,
    ])->assertRedirect();

    $group = $camp->groups()->firstOrFail();

    expect($group->name)->toBe('Levíčatá')
        ->and($group->group_type_id)->toBe($type->id)
        ->and($group->competes)->toBeTrue()
        ->and($group->leaders()->count())->toBe(count($leaderIds));
});

it('ignores leaders and types that belong to another camp', function () {
    [$camp, $owner] = makeGroupCamp();
    [$other] = makeGroupCamp();

    $foreignType = $other->groupTypes()->create(['name' => 'Cudzí typ']);
    $foreignLeader = $other->leaders()->firstOrFail();

    $this->actingAs($owner)->post("/camps/{$camp->id}/groups", [
        'name' => 'Levíčatá',
        'group_type_id' => $foreignType->id,
        'leader_ids' => [$foreignLeader->id],
    ])->assertRedirect();

    $group = $camp->groups()->firstOrFail();

    expect($group->group_type_id)->toBeNull()
        ->and($group->leaders()->count())->toBe(0);
});

it('keeps groups when their type is deleted', function () {
    [$camp, $owner] = makeGroupCamp();
    $type = $camp->groupTypes()->create(['name' => 'Detské skupiny']);
    $group = $camp->groups()->create(['name' => 'Levíčatá', 'group_type_id' => $type->id]);

    $this->actingAs($owner)->delete("/camps/{$camp->id}/group-types/{$type->id}")->assertRedirect();

    expect(CampGroup::whereKey($group->id)->exists())->toBeTrue()
        ->and($group->refresh()->group_type_id)->toBeNull();
});

it('replaces the leader assignment on update', function () {
    [$camp, $owner] = makeGroupCamp();
    $leaders = $camp->leaders()->get();
    $group = $camp->groups()->create(['name' => 'Levíčatá']);
    $group->leaders()->sync($leaders->pluck('id')->all());

    $this->actingAs($owner)->put("/camps/{$camp->id}/groups/{$group->id}", [
        'name' => 'Levíčatá',
        'leader_ids' => [$leaders->first()->id],
    ])->assertRedirect();

    expect($group->leaders()->count())->toBe(1);
});

it('leaves the leader assignment alone when the update omits it', function () {
    [$camp, $owner] = makeGroupCamp();
    $group = $camp->groups()->create(['name' => 'Levíčatá']);
    $group->leaders()->sync($camp->leaders()->pluck('id')->all());

    $this->actingAs($owner)->put("/camps/{$camp->id}/groups/{$group->id}", ['name' => 'Levice'])
        ->assertRedirect();

    expect($group->refresh()->name)->toBe('Levice')
        ->and($group->leaders()->count())->toBe(2);
});

it('keeps groups of other camps out of reach', function () {
    [$camp, $owner] = makeGroupCamp();
    [$other] = makeGroupCamp();
    $foreign = $other->groups()->create(['name' => 'Cudzia']);

    $this->actingAs($owner)
        ->put("/camps/{$camp->id}/groups/{$foreign->id}", ['name' => 'Hacked'])
        ->assertNotFound();
});

it('keeps strangers away from groups', function () {
    [$camp] = makeGroupCamp();
    $stranger = User::factory()->create();

    $this->actingAs($stranger)->get("/camps/{$camp->id}/groups")->assertForbidden();
    $this->actingAs($stranger)
        ->post("/camps/{$camp->id}/groups", ['name' => 'Cudzia'])
        ->assertForbidden();
});

it('renders the groups page with its types and leaders', function () {
    [$camp, $owner] = makeGroupCamp();
    $type = $camp->groupTypes()->create(['name' => 'Detské skupiny']);
    $group = $camp->groups()->create(['name' => 'Levíčatá', 'group_type_id' => $type->id]);
    $group->leaders()->sync([$camp->leaders()->firstOrFail()->id]);

    $this->actingAs($owner)
        ->get("/camps/{$camp->id}/groups")
        ->assertInertia(fn ($page) => $page
            ->component('camps/Groups')
            ->has('groups', 1)
            ->where('groups.0.name', 'Levíčatá')
            ->where('groups.0.competes', true)
            ->has('groups.0.leader_ids', 1)
            ->has('groupTypes', 1)
            ->has('leaders', 2)
        );
});

it('copies group structure into a duplicated camp', function () {
    [$camp, $owner] = makeGroupCamp();
    $type = $camp->groupTypes()->create(['name' => 'Detské skupiny']);
    $group = $camp->groups()->create([
        'name' => 'Levíčatá',
        'group_type_id' => $type->id,
        'competes' => true,
    ]);
    $group->leaders()->sync($camp->leaders()->pluck('id')->all());

    $this->actingAs($owner)->post("/camps/{$camp->id}/duplicate", [
        'name' => 'Tábor 2027',
        'year' => 2027,
        'start_date' => '2027-07-12',
        'end_date' => '2027-07-13',
    ])->assertRedirect();

    $new = Camp::where('name', 'Tábor 2027')->firstOrFail();
    $copied = $new->groups()->firstOrFail();

    expect($new->groupTypes()->count())->toBe(1)
        ->and($copied->name)->toBe('Levíčatá')
        ->and($copied->group_type_id)->toBe($new->groupTypes()->firstOrFail()->id)
        ->and($copied->competes)->toBeTrue()
        // The leaders came across as names, so the assignment survives.
        ->and($copied->leaders()->count())->toBe(2);
});
