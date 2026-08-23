<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityRating;
use App\Models\Camp;
use App\Models\CampDay;
use App\Models\CampGroup;
use App\Models\CampInvitation;
use App\Models\CampLeader;
use App\Models\DayReview;
use App\Models\FeedbackAnswer;
use App\Models\FeedbackQuestion;
use App\Models\ProgramEntry;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataExportController extends Controller
{
    /**
     * GDPR Article 15 (access) and Article 20 (portability): hand the user
     * everything the app holds about them, in a machine-readable file.
     *
     * The export covers their account, the camps they own or belong to with the
     * full plan, and the feedback they personally wrote. Secrets — the password
     * hash, two-factor keys, the Gemini API key — are deliberately excluded:
     * they are not useful to the user and exporting them only creates risk.
     */
    public function __invoke(Request $request): StreamedResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $payload = [
            'export' => [
                'generated_at' => now()->toIso8601String(),
                'service' => config('app.name'),
                'format' => 'taborplanner-gdpr-export/1',
                'notice' => 'Personal data export under GDPR Art. 15 and Art. 20. '
                    .'Secrets (password hash, two-factor keys, API keys) are intentionally omitted.',
            ],
            'account' => $this->account($user),
            'camps' => $this->camps($user),
            'activity_libraries' => $this->libraries($user),
            'my_feedback' => $this->feedback($user),
        ];

        $filename = 'taborplanner-export-'.now()->format('Y-m-d').'.json';

        return response()->streamDownload(function () use ($payload) {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }, $filename, ['Content-Type' => 'application/json']);
    }

    /** @return array<string, mixed> */
    protected function account(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'two_factor_enabled' => $user->two_factor_confirmed_at !== null,
            'gemini_key_stored' => $user->gemini_api_key !== null,
            'passkeys' => $user->passkeys()->get()->map(fn ($passkey) => [
                'name' => $passkey->name,
                'created_at' => $passkey->created_at?->toIso8601String(),
                'last_used_at' => $passkey->last_used_at?->toIso8601String(),
            ])->values()->all(),
            'created_at' => $user->created_at?->toIso8601String(),
            'updated_at' => $user->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Every camp the user can see, with its plan. Camp content is shared with
     * the other members, so this is "data about you" only in part — the export
     * says which role the user holds in each.
     *
     * @return list<array<string, mixed>>
     */
    protected function camps(User $user): array
    {
        $camps = Camp::query()
            ->whereHas('members', fn ($q) => $q->whereKey($user->id))
            ->with([
                'days.entries',
                'days.reviews.user:id,name',
                'days.reviews.ratings',
                'days.reviews.answers.question',
                'timeSlots',
                'leaders',
                'groups.type',
                'groupTypes',
                'feedbackQuestions',
                'invitations',
                'members:id,name,email',
                'owner:id,name,email',
            ])
            ->get();

        $rows = [];

        foreach ($camps as $camp) {
            $rows[] = [
                'id' => $camp->id,
                'name' => $camp->name,
                'year' => $camp->year,
                'description' => $camp->description,
                'location' => $camp->location,
                'start_date' => (string) $camp->start_date,
                'end_date' => (string) $camp->end_date,
                'your_role' => $camp->owner_id === $user->id ? 'owner' : 'leader',
                'owner' => ['name' => $camp->owner?->name, 'email' => $camp->owner?->email],
                'members' => $camp->members->map(fn (User $m) => [
                    'name' => $m->name,
                    'email' => $m->email,
                    'role' => $m->getAttribute('pivot')?->getAttribute('role'),
                ])->values()->all(),
                'leaders' => $camp->leaders->map(fn (CampLeader $l) => [
                    'name' => $l->name,
                    'has_account' => $l->user_id !== null,
                ])->values()->all(),
                'groups' => $camp->groups->map(fn (CampGroup $g) => [
                    'name' => $g->name,
                    'type' => $g->type?->name,
                    'competes' => $g->competes,
                ])->values()->all(),
                'group_types' => $camp->groupTypes->pluck('name')->all(),
                'feedback_questions' => $camp->feedbackQuestions->map(fn (FeedbackQuestion $q) => [
                    'scope' => $q->scope,
                    'text' => $q->text,
                    'archived' => $q->archived_at !== null,
                ])->values()->all(),
                'invitations' => $camp->invitations->map(fn (CampInvitation $i) => [
                    'email' => $i->email,
                    'created_at' => $i->created_at?->toIso8601String(),
                ])->values()->all(),
                'time_slots' => $camp->timeSlots->map(fn (TimeSlot $s) => [
                    'name' => $s->name,
                    'kind' => $s->kind,
                    'start_time' => $s->start_time,
                    'end_time' => $s->end_time,
                ])->values()->all(),
                'days' => $camp->days->map(fn (CampDay $day) => [
                    'date' => (string) $day->date,
                    'is_trip' => $day->is_trip,
                    'trip_name' => $day->trip_name,
                    'name_days' => $day->name_days,
                    'birthdays' => $day->birthdays,
                    'materials' => $day->materials,
                    'notes' => $day->notes,
                    'entries' => $day->entries->map(fn (ProgramEntry $e) => [
                        'title' => $e->title,
                        'description' => $e->description,
                        'start_time' => $e->start_time,
                        'duration' => $e->duration,
                        'responsible' => $e->responsible,
                        'materials' => $e->materials,
                        'notes' => $e->notes,
                        'status' => $e->status,
                    ])->values()->all(),
                    'reviews' => $day->reviews->map(fn (DayReview $r) => [
                        'by' => $r->user?->name,
                        'is_yours' => $r->user_id === $user->id,
                        'notes' => $r->notes,
                        'camp_rating' => $r->camp_rating,
                        'camp_reason' => $r->camp_reason,
                        'ratings' => $r->ratings->map(fn (ActivityRating $rating) => [
                            'rating' => $rating->rating,
                            'reason' => $rating->reason,
                        ])->values()->all(),
                        'answers' => $r->answers->map(fn (FeedbackAnswer $a) => [
                            'question' => $a->question?->text,
                            'answer' => $a->answer,
                        ])->values()->all(),
                    ])->values()->all(),
                ])->values()->all(),
            ];
        }

        return $rows;
    }

    /** @return list<array<string, mixed>> */
    protected function libraries(User $user): array
    {
        $rows = [];

        $libraries = $user->activityLibraries()
            ->with(['activities.category', 'categories'])
            ->get();

        foreach ($libraries as $library) {
            $rows[] = [
                'name' => $library->name,
                'your_role' => $library->getAttribute('pivot')?->getAttribute('role'),
                'categories' => $library->categories->pluck('name')->all(),
                'activities' => $library->activities->map(fn (Activity $a) => [
                    'name' => $a->name,
                    'category' => $a->category?->name,
                    'description' => $a->description,
                    'materials' => $a->materials,
                    'default_duration' => $a->default_duration,
                ])->values()->all(),
            ];
        }

        return $rows;
    }

    /**
     * The feedback this user personally wrote, pulled out separately so it is
     * easy to find without reading through every camp.
     *
     * @return list<array<string, mixed>>
     */
    protected function feedback(User $user): array
    {
        $rows = [];

        $reviews = DayReview::query()
            ->where('user_id', $user->id)
            ->with(['day.camp:id,name', 'ratings', 'answers.question'])
            ->get();

        foreach ($reviews as $review) {
            $rows[] = [
                'camp' => $review->day->camp?->name,
                'date' => (string) $review->day->date,
                'notes' => $review->notes,
                'camp_rating' => $review->camp_rating,
                'camp_reason' => $review->camp_reason,
                'activity_ratings' => $review->ratings->map(fn (ActivityRating $r) => [
                    'rating' => $r->rating,
                    'reason' => $r->reason,
                ])->values()->all(),
                'answers' => $review->answers->map(fn (FeedbackAnswer $a) => [
                    'question' => $a->question?->text,
                    'answer' => $a->answer,
                ])->values()->all(),
                'created_at' => $review->created_at?->toIso8601String(),
            ];
        }

        return $rows;
    }
}
