<?php

namespace App\Http\Controllers;

use App\Models\ActivityRating;
use App\Models\AiSummary;
use App\Models\Camp;
use App\Models\CampDay;
use App\Models\DayReview;
use App\Models\FeedbackAnswer;
use App\Models\FeedbackQuestion;
use App\Support\Markdown;
use App\Support\Weekdays;
use Inertia\Inertia;
use Inertia\Response;

class FeedbackController extends Controller
{
    /**
     * Everything about reviews in one place: what every leader said about each
     * day, and the stored AI summaries.
     */
    public function index(Camp $camp): Response
    {
        $this->authorize('view', $camp);

        $camp->load([
            'days',
            'days.entries:id,camp_day_id,kind',
            'days.reviews.user:id,name',
            'days.reviews.answers',
            'days.reviews.ratings.entry:id,title,activity_id',
            'days.reviews.ratings.entry.activity:id,name',
        ]);

        $lastDayId = $camp->days->last()?->id;

        $days = $camp->days->map(function (CampDay $day) use ($lastDayId) {
            $allRatings = $day->reviews->flatMap->ratings;

            return [
                'id' => $day->id,
                'date' => $day->date->toDateString(),
                'weekday' => Weekdays::for($day->date->dayOfWeekIso),
                'label' => $day->date->format('j.n.'),
                'is_last' => $day->id === $lastDayId,
                'entries_count' => $day->entries->count(),
                'reviewers' => $day->reviews->count(),
                'avg' => $allRatings->count() ? round($allRatings->avg('rating'), 1) : null,
                'reviews' => $day->reviews->map(fn (DayReview $review) => [
                    'id' => $review->id,
                    'user_name' => $review->user->name,
                    'notes' => $review->notes,
                    'camp_rating' => $review->camp_rating,
                    'camp_reason' => $review->camp_reason,
                    'ratings' => $review->ratings->map(fn (ActivityRating $r) => [
                        'entry_id' => $r->program_entry_id,
                        'entry_title' => $r->entry?->title ?: $r->entry?->activity?->name ?: 'Aktivita',
                        'rating' => $r->rating,
                        'reason' => $r->reason,
                    ])->values()->all(),
                    'answers' => $review->answers->map(fn (FeedbackAnswer $a) => [
                        'question_id' => $a->feedback_question_id,
                        'answer' => $a->answer,
                    ])->values()->all(),
                ])->values()->all(),
            ];
        })->values();

        return Inertia::render('camps/Feedback', [
            'camp' => [
                'id' => $camp->id,
                'name' => $camp->name,
            ],
            'days' => $days,
            // Archived questions stay listed so old answers still make sense.
            'questions' => $camp->feedbackQuestions()->get()
                ->map(fn (FeedbackQuestion $q) => [
                    'id' => $q->id,
                    'scope' => $q->scope,
                    'text' => $q->text,
                    'position' => $q->position,
                    'archived' => $q->isArchived(),
                ])->values()->all(),
            'aiSummaries' => $camp->aiSummaries()->with('user:id,name')->get()
                ->map(fn (AiSummary $s) => [
                    'camp_day_id' => $s->camp_day_id,
                    'summary' => $s->summary,
                    'summary_html' => Markdown::toHtml($s->summary),
                    'author' => $s->user?->name,
                    'saved_at' => $s->updated_at?->toIso8601String(),
                ])->values(),
        ]);
    }
}
