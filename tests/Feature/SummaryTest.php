<?php

use App\Models\AiSummary;
use App\Models\Camp;
use App\Models\User;
use Illuminate\Support\Facades\Http;

/**
 * A camp with one reviewed day.
 *
 * @return array{0: Camp, 1: User} camp, owner (with a Gemini key)
 */
function makeReviewedCamp(string $summary = 'Súhrn'): array
{
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [
                ['content' => ['parts' => [['text' => $summary]]]],
            ],
        ]),
    ]);

    $owner = User::factory()->create(['gemini_api_key' => 'test-key']);

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

    $day = $camp->refresh()->days->first();
    $entry = $day->entries()->create(['start_time' => '09:00', 'duration' => 60, 'title' => 'Ranná hra']);

    $review = $day->reviews()->create(['user_id' => $owner->id, 'notes' => 'Šlo to dobre.']);
    $review->ratings()->create(['program_entry_id' => $entry->id, 'rating' => 5, 'reason' => 'Deti sa smiali.']);

    return [$camp, $owner];
}

it('stores the whole-camp summary and returns it as markdown and html', function () {
    [$camp, $owner] = makeReviewedCamp('## Zhrnutie

- **Ranná hra** bola najlepšia');

    $response = $this->actingAs($owner)->postJson("/camps/{$camp->id}/summary")->assertOk();

    expect($response->json('summary'))->toContain('Ranná hra')
        ->and($response->json('summary_html'))->toContain('<strong>Ranná hra</strong>')
        ->and($response->json('summary_html'))->toContain('<li>')
        ->and($response->json('saved_at'))->not->toBeNull();

    $stored = AiSummary::firstOrFail();

    expect(AiSummary::count())->toBe(1)
        ->and($stored->camp_day_id)->toBeNull()
        ->and($stored->camp_id)->toBe($camp->id)
        ->and($stored->user_id)->toBe($owner->id);
});

it('keeps one row per scope when regenerating', function () {
    [$camp, $owner] = makeReviewedCamp();

    $this->actingAs($owner)->postJson("/camps/{$camp->id}/summary")->assertOk();
    $this->actingAs($owner)->postJson("/camps/{$camp->id}/summary")->assertOk();

    expect(AiSummary::count())->toBe(1);
});

it('stores a day summary against that day', function () {
    [$camp, $owner] = makeReviewedCamp();
    $day = $camp->days->first();

    $this->actingAs($owner)->postJson("/days/{$day->id}/summary")->assertOk();

    expect(AiSummary::firstOrFail()->camp_day_id)->toBe($day->id);
});

it('escapes html the model may emit', function () {
    [$camp, $owner] = makeReviewedCamp('**tučné** <script>alert(1)</script>');

    $html = $this->actingAs($owner)->postJson("/camps/{$camp->id}/summary")->json('summary_html');

    expect($html)->toContain('<strong>tučné</strong>')
        ->and($html)->toContain('&lt;script&gt;')
        ->and($html)->not->toContain('<script>');
});

it('asks for a key instead of calling the API when the user has none', function () {
    [$camp, $owner] = makeReviewedCamp();
    // Not mass-assignable, hence set directly.
    $owner->gemini_api_key = null;
    $owner->save();

    $this->actingAs($owner)
        ->postJson("/camps/{$camp->id}/summary")
        ->assertStatus(422)
        ->assertJson(['needs_key' => true]);

    expect(AiSummary::count())->toBe(0);
});

it('sends stored summaries to the camp page as rendered html', function () {
    [$camp, $owner] = makeReviewedCamp('- **Ranná hra** bola najlepšia');

    $this->actingAs($owner)->postJson("/camps/{$camp->id}/summary")->assertOk();

    $this->actingAs($owner)
        ->get("/camps/{$camp->id}")
        ->assertInertia(fn ($page) => $page
            ->has('aiSummaries', 1)
            ->where('aiSummaries.0.camp_day_id', null)
            ->where('aiSummaries.0.author', $owner->name)
            ->has('aiSummaries.0.summary_html')
        );
});

it('refuses a summary for a camp nobody has reviewed', function () {
    [$camp, $owner] = makeReviewedCamp();
    $camp->days->first()->reviews()->delete();

    $this->actingAs($owner)->postJson("/camps/{$camp->id}/summary")->assertStatus(422);

    expect(AiSummary::count())->toBe(0);
});
