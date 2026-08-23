<?php

use App\Models\Camp;
use App\Models\User;

/**
 * A camp with an owner and an invited leader.
 *
 * @return array{0: Camp, 1: User, 2: User} camp, owner, leader
 */
function makePageCamp(): array
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

    foreach (['2026-07-13', '2026-07-14'] as $i => $date) {
        $camp->days()->create(['date' => $date, 'position' => $i]);
    }

    return [$camp, $owner, $leader];
}

it('renders every camp page for a member', function (string $path, string $component) {
    [$camp, $owner] = makePageCamp();

    $this->actingAs($owner)
        ->get("/camps/{$camp->id}{$path}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component($component));
})->with([
    ['/leaders', 'camps/Leaders'],
    ['/settings', 'camps/Settings'],
    ['/blocks', 'camps/Blocks'],
    ['/feedback', 'camps/Feedback'],
]);

it('keeps camp pages away from strangers', function (string $path) {
    [$camp] = makePageCamp();
    $stranger = User::factory()->create();

    $this->actingAs($stranger)->get("/camps/{$camp->id}{$path}")->assertForbidden();
})->with(['/leaders', '/settings', '/blocks', '/feedback']);

it('lets an invited leader open the camp pages too', function (string $path) {
    [$camp, , $leader] = makePageCamp();

    $this->actingAs($leader)->get("/camps/{$camp->id}{$path}")->assertOk();
})->with(['/leaders', '/settings', '/blocks', '/feedback']);

it('shares the camp context so the sidebar can show its section', function () {
    [$camp, $owner] = makePageCamp();

    $this->actingAs($owner)
        ->get("/camps/{$camp->id}/leaders")
        ->assertInertia(fn ($page) => $page
            ->where('campContext.id', $camp->id)
            ->where('campContext.name', 'Tábor')
            ->where('campContext.is_owner', true)
        );
});

it('leaves the camp context empty away from a camp', function () {
    [, $owner] = makePageCamp();

    $this->actingAs($owner)
        ->get('/camps')
        ->assertInertia(fn ($page) => $page->where('campContext', null));
});

it('marks an invited leader as not being the owner', function () {
    [$camp, , $leader] = makePageCamp();

    $this->actingAs($leader)
        ->get("/camps/{$camp->id}")
        ->assertInertia(fn ($page) => $page->where('campContext.is_owner', false));
});

it('lists members and the share link on the leaders page', function () {
    [$camp, $owner] = makePageCamp();

    $this->actingAs($owner)->post("/camps/{$camp->id}/share-link")->assertRedirect();

    $this->actingAs($owner)
        ->get("/camps/{$camp->id}/leaders")
        ->assertInertia(fn ($page) => $page
            ->has('people', 2)
            ->where('people.0.status', 'owner')
            ->has('shareLink')
            ->where('camp.is_owner', true)
        );
});

it('sends the camp time blocks to the blocks page', function () {
    [$camp, $owner] = makePageCamp();
    $camp->timeSlots()->create([
        'name' => 'BLOK I.',
        'start_time' => '09:00',
        'end_time' => '12:00',
        'kind' => 'activity',
        'position' => 0,
    ]);

    $this->actingAs($owner)
        ->get("/camps/{$camp->id}/blocks")
        ->assertInertia(fn ($page) => $page
            ->has('slots', 1)
            ->where('slots.0.name', 'BLOK I.')
            ->where('slots.0.start_time', '09:00')
            ->where('camp.schedule_locked', false)
        );
});

it('shows every leader review on the feedback page', function () {
    [$camp, $owner, $leader] = makePageCamp();
    $day = $camp->days()->first();
    $entry = $day->entries()->create(['start_time' => '09:00', 'duration' => 60, 'title' => 'Ranná hra']);

    foreach ([[$owner, 4], [$leader, 5]] as [$user, $rating]) {
        $review = $day->reviews()->create(['user_id' => $user->id, 'notes' => "Poznámka {$user->id}"]);
        $review->ratings()->create(['program_entry_id' => $entry->id, 'rating' => $rating]);
    }

    $this->actingAs($leader)
        ->get("/camps/{$camp->id}/feedback")
        ->assertInertia(fn ($page) => $page
            ->has('days', 2)
            ->where('days.0.reviewers', 2)
            ->where('days.0.avg', 4.5)
            ->has('days.0.reviews', 2)
            ->where('days.0.reviews.0.ratings.0.entry_title', 'Ranná hra')
            ->where('days.1.reviewers', 0)
        );
});

it('no longer ships member and summary detail to the planner page', function () {
    [$camp, $owner] = makePageCamp();

    $this->actingAs($owner)
        ->get("/camps/{$camp->id}")
        ->assertInertia(fn ($page) => $page
            ->where('membersCount', 2)
            ->missing('members')
            ->missing('invitations')
            ->missing('aiSummaries')
        );
});

test('creating a camp flashes the onboarding signal to its creator', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/camps', [
        'name' => 'Nový tábor',
        'year' => 2026,
        'start_date' => '2026-07-13',
        'end_date' => '2026-07-15',
    ]);

    $camp = Camp::where('name', 'Nový tábor')->sole();
    $response->assertRedirect(route('camps.show', $camp));

    // Inertia's own flash channel is the one the client listens to; it is not
    // persisted into history state, so the modal opens exactly once.
    $flash = session('inertia.flash_data');
    expect($flash['camp_onboarding'])->toBeTrue()
        ->and($flash['toast']['type'])->toBe('success');
});
