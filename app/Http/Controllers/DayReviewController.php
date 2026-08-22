<?php

namespace App\Http\Controllers;

use App\Models\CampDay;
use App\Models\DayReview;
use App\Models\FeedbackQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DayReviewController extends Controller
{
    /**
     * Create or update the current leader's review of a day: a star rating for
     * each activity (with an optional reason), plus — on the camp's last day —
     * an overall camp rating.
     */
    public function store(Request $request, CampDay $day): RedirectResponse
    {
        $this->authorize('update', $day->camp);

        $lastDay = $day->camp->days()->reorder()->orderByDesc('position')->orderByDesc('date')->first();
        $isLastDay = $lastDay?->id === $day->id;

        $data = $request->validate([
            'ratings' => ['array'],
            'ratings.*.program_entry_id' => ['required', 'integer', 'exists:program_entries,id'],
            'ratings.*.rating' => ['required', 'integer', 'min:1', 'max:5'],
            'ratings.*.reason' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string'],
            'camp_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'camp_reason' => ['nullable', 'string', 'max:2000'],
            'answers' => ['array'],
            'answers.*.feedback_question_id' => ['required', 'integer'],
            'answers.*.answer' => ['nullable', 'string', 'max:2000'],
        ]);

        // Only accept ratings for entries that belong to this day.
        $dayEntryIds = $day->entries()->pluck('id')->all();

        // Whole-camp questions are only asked once, on the last day.
        $questions = $day->camp->feedbackQuestions()->active()->get()
            ->filter(fn (FeedbackQuestion $q) => $q->scope === 'day' || $isLastDay)
            ->keyBy('id');

        DB::transaction(function () use ($day, $request, $data, $isLastDay, $dayEntryIds, $questions) {
            $review = DayReview::updateOrCreate(
                ['camp_day_id' => $day->id, 'user_id' => $request->user()->id],
                [
                    'notes' => $data['notes'] ?? null,
                    'camp_rating' => $isLastDay ? ($data['camp_rating'] ?? null) : null,
                    'camp_reason' => $isLastDay ? ($data['camp_reason'] ?? null) : null,
                ],
            );

            foreach ($data['ratings'] ?? [] as $item) {
                if (! in_array((int) $item['program_entry_id'], $dayEntryIds, true)) {
                    continue;
                }
                $review->ratings()->updateOrCreate(
                    ['program_entry_id' => $item['program_entry_id']],
                    ['rating' => $item['rating'], 'reason' => $item['reason'] ?? null],
                );
            }

            foreach ($data['answers'] ?? [] as $item) {
                if (! $questions->has((int) $item['feedback_question_id'])) {
                    continue;
                }

                $answer = trim((string) ($item['answer'] ?? ''));

                // Clearing an answer removes it rather than storing a blank.
                if ($answer === '') {
                    $review->answers()
                        ->where('feedback_question_id', $item['feedback_question_id'])
                        ->delete();

                    continue;
                }

                $review->answers()->updateOrCreate(
                    ['feedback_question_id' => $item['feedback_question_id']],
                    ['answer' => $answer],
                );
            }
        });

        return back()->with('toast', ['type' => 'success', 'message' => __('Day review saved.')]);
    }
}
