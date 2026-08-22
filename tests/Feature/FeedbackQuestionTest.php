<?php

use App\Models\Camp;
use App\Models\FeedbackAnswer;
use App\Models\FeedbackQuestion;
use App\Models\User;

/**
 * A two-day camp with an owner and an invited leader.
 *
 * @return array{0: Camp, 1: User, 2: User} camp, owner, leader
 */
function makeQuestionCamp(): array
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

it('lets any member add, reword and remove a question', function () {
    [$camp, , $leader] = makeQuestionCamp();

    $this->actingAs($leader)
        ->post("/camps/{$camp->id}/feedback-questions", ['scope' => 'day', 'text' => 'Čo by si zmenil?'])
        ->assertRedirect();

    $question = $camp->feedbackQuestions()->firstOrFail();
    expect($question->scope)->toBe('day');

    $this->actingAs($leader)
        ->put("/camps/{$camp->id}/feedback-questions/{$question->id}", ['text' => 'Čo zmeniť?'])
        ->assertRedirect();

    expect($question->refresh()->text)->toBe('Čo zmeniť?');

    $this->actingAs($leader)
        ->delete("/camps/{$camp->id}/feedback-questions/{$question->id}")
        ->assertRedirect();

    expect(FeedbackQuestion::count())->toBe(0);
});

it('keeps strangers away from the questions', function () {
    [$camp] = makeQuestionCamp();
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->post("/camps/{$camp->id}/feedback-questions", ['scope' => 'day', 'text' => 'Kto?'])
        ->assertForbidden();
});

it('archives instead of deleting a question that has answers', function () {
    [$camp, $owner] = makeQuestionCamp();
    $question = $camp->feedbackQuestions()->create(['scope' => 'day', 'text' => 'Čo by si zmenil?']);
    $day = $camp->days()->first();

    $this->actingAs($owner)->post("/days/{$day->id}/review", [
        'answers' => [['feedback_question_id' => $question->id, 'answer' => 'Viac vody.']],
    ])->assertRedirect();

    $this->actingAs($owner)
        ->delete("/camps/{$camp->id}/feedback-questions/{$question->id}")
        ->assertRedirect();

    expect($question->refresh()->archived_at)->not->toBeNull()
        ->and(FeedbackAnswer::count())->toBe(1);
});

it('refuses to move a question between scopes once it has answers', function () {
    [$camp, $owner] = makeQuestionCamp();
    $question = $camp->feedbackQuestions()->create(['scope' => 'day', 'text' => 'Čo by si zmenil?']);
    $day = $camp->days()->first();

    $this->actingAs($owner)->post("/days/{$day->id}/review", [
        'answers' => [['feedback_question_id' => $question->id, 'answer' => 'Viac vody.']],
    ]);

    $this->actingAs($owner)
        ->put("/camps/{$camp->id}/feedback-questions/{$question->id}", [
            'text' => 'Čo by si zmenil?',
            'scope' => 'camp',
        ])
        ->assertSessionHasErrors('scope');

    expect($question->refresh()->scope)->toBe('day');
});

it('stores a day answer with the review', function () {
    [$camp, $owner] = makeQuestionCamp();
    $question = $camp->feedbackQuestions()->create(['scope' => 'day', 'text' => 'Čo by si zmenil?']);
    $day = $camp->days()->first();

    $this->actingAs($owner)->post("/days/{$day->id}/review", [
        'notes' => 'Dobrý deň.',
        'answers' => [['feedback_question_id' => $question->id, 'answer' => '  Viac vody.  ']],
    ])->assertRedirect();

    $answer = FeedbackAnswer::firstOrFail();

    expect($answer->answer)->toBe('Viac vody.')
        ->and($answer->feedback_question_id)->toBe($question->id);
});

it('only asks whole-camp questions on the last day', function () {
    [$camp, $owner] = makeQuestionCamp();
    $question = $camp->feedbackQuestions()->create(['scope' => 'camp', 'text' => 'Ako celkovo?']);
    $days = $camp->days()->get();

    $this->actingAs($owner)->post("/days/{$days[0]->id}/review", [
        'answers' => [['feedback_question_id' => $question->id, 'answer' => 'Priskoro.']],
    ])->assertRedirect();

    expect(FeedbackAnswer::count())->toBe(0);

    $this->actingAs($owner)->post("/days/{$days[1]->id}/review", [
        'answers' => [['feedback_question_id' => $question->id, 'answer' => 'Super.']],
    ])->assertRedirect();

    expect(FeedbackAnswer::count())->toBe(1);
});

it('ignores answers to another camp\'s questions', function () {
    [$camp, $owner] = makeQuestionCamp();
    [$other] = makeQuestionCamp();
    $foreign = $other->feedbackQuestions()->create(['scope' => 'day', 'text' => 'Cudzia?']);
    $day = $camp->days()->first();

    $this->actingAs($owner)->post("/days/{$day->id}/review", [
        'answers' => [['feedback_question_id' => $foreign->id, 'answer' => 'Nemalo by prejsť.']],
    ])->assertRedirect();

    expect(FeedbackAnswer::count())->toBe(0);
});

it('clears an answer when it is submitted empty', function () {
    [$camp, $owner] = makeQuestionCamp();
    $question = $camp->feedbackQuestions()->create(['scope' => 'day', 'text' => 'Čo by si zmenil?']);
    $day = $camp->days()->first();

    $this->actingAs($owner)->post("/days/{$day->id}/review", [
        'answers' => [['feedback_question_id' => $question->id, 'answer' => 'Viac vody.']],
    ]);
    $this->actingAs($owner)->post("/days/{$day->id}/review", [
        'answers' => [['feedback_question_id' => $question->id, 'answer' => '']],
    ]);

    expect(FeedbackAnswer::count())->toBe(0);
});

it('does not ask an archived question any more', function () {
    [$camp, $owner] = makeQuestionCamp();
    $question = $camp->feedbackQuestions()->create([
        'scope' => 'day',
        'text' => 'Stará otázka',
        'archived_at' => now(),
    ]);
    $day = $camp->days()->first();

    $this->actingAs($owner)->post("/days/{$day->id}/review", [
        'answers' => [['feedback_question_id' => $question->id, 'answer' => 'Nemalo by prejsť.']],
    ])->assertRedirect();

    expect(FeedbackAnswer::count())->toBe(0);

    $this->actingAs($owner)
        ->get("/camps/{$camp->id}")
        ->assertInertia(fn ($page) => $page->has('feedbackQuestions', 0));
});

it('sends active questions to the planner and my own answers with them', function () {
    [$camp, $owner] = makeQuestionCamp();
    $question = $camp->feedbackQuestions()->create(['scope' => 'day', 'text' => 'Čo by si zmenil?']);
    $day = $camp->days()->first();

    $this->actingAs($owner)->post("/days/{$day->id}/review", [
        'answers' => [['feedback_question_id' => $question->id, 'answer' => 'Viac vody.']],
    ]);

    $this->actingAs($owner)
        ->get("/camps/{$camp->id}")
        ->assertInertia(fn ($page) => $page
            ->has('feedbackQuestions', 1)
            ->where('feedbackQuestions.0.text', 'Čo by si zmenil?')
            ->where("days.0.my_review.answers.{$question->id}", 'Viac vody.')
        );
});

it('shows questions and their answers on the feedback page', function () {
    [$camp, $owner] = makeQuestionCamp();
    $question = $camp->feedbackQuestions()->create(['scope' => 'day', 'text' => 'Čo by si zmenil?']);
    $day = $camp->days()->first();

    $this->actingAs($owner)->post("/days/{$day->id}/review", [
        'answers' => [['feedback_question_id' => $question->id, 'answer' => 'Viac vody.']],
    ]);

    $this->actingAs($owner)
        ->get("/camps/{$camp->id}/feedback")
        ->assertInertia(fn ($page) => $page
            ->has('questions', 1)
            ->where('questions.0.archived', false)
            ->where('days.0.reviews.0.answers.0.answer', 'Viac vody.')
        );
});

it('copies questions but not answers into a duplicated camp', function () {
    [$camp, $owner] = makeQuestionCamp();
    $question = $camp->feedbackQuestions()->create(['scope' => 'camp', 'text' => 'Ako celkovo?']);
    $day = $camp->days()->get()->last();

    $this->actingAs($owner)->post("/days/{$day->id}/review", [
        'answers' => [['feedback_question_id' => $question->id, 'answer' => 'Super.']],
    ]);

    $this->actingAs($owner)->post("/camps/{$camp->id}/duplicate", [
        'name' => 'Tábor 2027',
        'year' => 2027,
        'start_date' => '2027-07-12',
        'end_date' => '2027-07-13',
    ])->assertRedirect();

    $new = Camp::where('name', 'Tábor 2027')->firstOrFail();

    expect($new->feedbackQuestions()->count())->toBe(1)
        ->and($new->feedbackQuestions()->firstOrFail()->scope)->toBe('camp')
        ->and(FeedbackAnswer::count())->toBe(1);
});
