<?php

use App\Models\Camp;
use App\Models\User;

beforeEach(function () {
    config(['app.admin_email' => 'admin@example.com']);
});

it('shows every user with the camps they own or lead to the admin', function () {
    $admin = User::factory()->create(['email' => 'Admin@Example.com']);
    $owner = User::factory()->create(['name' => 'Owner']);
    $leader = User::factory()->create(['name' => 'Leader']);
    User::factory()->create(['name' => 'Loner']);

    $camp = Camp::create([
        'owner_id' => $owner->id,
        'name' => 'Tábor',
        'year' => 2026,
        'start_date' => '2026-07-13',
        'end_date' => '2026-07-17',
    ]);
    $camp->addMember($owner, 'owner');
    $camp->addMember($leader, 'leader');

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Index')
            ->where('auth.isAdmin', true)
            ->where('stats', ['users' => 4, 'camps' => 1, 'usersWithoutCamp' => 2])
            ->has('users', 4)
            ->where('users', fn ($users) => collect($users)->firstWhere('id', $owner->id)['camps'][0]['role'] === 'owner'
                && collect($users)->firstWhere('id', $leader->id)['camps'][0]['role'] === 'leader'
                && collect($users)->firstWhere('id', $admin->id)['camps'] === []));
});

it('keeps the admin panel away from everyone else', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

it('sends guests to log in', function () {
    $this->get('/admin')->assertRedirect('/login');
});

it('only flags the admin account in the shared props', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/camps')
        ->assertInertia(fn ($page) => $page->where('auth.isAdmin', false));
});
