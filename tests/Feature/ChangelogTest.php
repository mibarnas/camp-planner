<?php

use App\Models\User;
use App\Support\ChangelogDocument;

it('offers the current release notes to a user who has not seen them', function () {
    $user = User::factory()->create(['last_seen_version' => null]);

    $this->actingAs($user)
        ->get('/camps')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('changelog.version', config('app.version'))
            ->where('changelog.html', fn (string $html) => str_contains($html, '<h2>'))
        );
});

it('does not offer release notes the user has already seen', function () {
    $user = User::factory()->create(['last_seen_version' => config('app.version')]);

    $this->actingAs($user)
        ->get('/camps')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('changelog', null));
});

it('does not offer release notes to a guest', function () {
    $this->get('/privacy')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('changelog', null));
});

it('remembers that the release notes were read', function () {
    $user = User::factory()->create(['last_seen_version' => null]);

    $this->actingAs($user)
        ->from('/camps')
        ->post('/changelog/seen')
        ->assertRedirect('/camps');

    expect($user->fresh()->last_seen_version)->toBe(config('app.version'));

    $this->actingAs($user)
        ->get('/camps')
        ->assertInertia(fn ($page) => $page->where('changelog', null));
});

it('serves the release notes on demand even once they have been read', function () {
    $user = User::factory()->create(['last_seen_version' => config('app.version')]);

    $this->actingAs($user)
        ->getJson('/changelog')
        ->assertOk()
        ->assertJsonPath('version', config('app.version'))
        ->assertJson(fn ($json) => $json
            ->where('html', fn (string $html) => str_contains($html, '<h2>'))
            ->etc()
        );
});

it('keeps the release notes behind authentication', function () {
    $this->getJson('/changelog')->assertUnauthorized();
});

it('renders the release notes in the requested locale', function (string $locale, string $expected) {
    expect(ChangelogDocument::render('1.0.0', $locale))->toContain($expected);
})->with([
    ['sk', 'TáborPlanner 1.0 je tu'],
    ['en', 'TáborPlanner 1.0 is here'],
    ['de', 'TáborPlanner 1.0 ist da'],
]);

it('falls back to the fallback locale when a translation is missing', function () {
    $html = ChangelogDocument::render('1.0.0', 'fr');

    expect($html)->toContain('TáborPlanner 1.0 is here');
});

it('reports an unwritten version as missing rather than failing', function () {
    expect(ChangelogDocument::exists('1.0.0'))->toBeTrue()
        ->and(ChangelogDocument::exists('99.0.0'))->toBeFalse()
        ->and(ChangelogDocument::exists('../../etc/passwd'))->toBeFalse();
});
