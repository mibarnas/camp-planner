<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityLibrary;
use App\Models\Camp;
use App\Models\CampDay;
use App\Models\CampLeader;
use App\Models\EntryGroupPoint;
use App\Models\FeedbackQuestion;
use App\Models\PlanVersion;
use App\Models\ProgramEntry;
use App\Models\TimeSlot;
use App\Models\TimeSlotOverride;
use App\Models\User;
use App\Support\NameDays;
use App\Support\NameMatcher;
use App\Support\Weekdays;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CampController extends Controller
{
    public function index(Request $request): Response
    {
        $camps = $request->user()->camps()
            ->with('owner:id,name')
            ->withCount(['days'])
            ->orderByDesc('year')
            ->orderBy('name')
            ->get()
            ->map(fn (Camp $camp) => [
                'id' => $camp->id,
                'name' => $camp->name,
                'icon' => $camp->icon,
                'color' => $camp->color,
                'year' => $camp->year,
                'description' => $camp->description,
                'location' => $camp->location,
                'start_date' => $camp->start_date->toDateString(),
                'end_date' => $camp->end_date->toDateString(),
                'days_count' => $camp->days_count,
                'role' => $camp->pivot?->getAttribute('role'),
                'owner' => $camp->owner?->only(['id', 'name']),
            ]);

        return Inertia::render('camps/Index', [
            'camps' => $camps,
            'libraries' => $request->user()->activityLibraries()
                ->orderBy('name')->get()
                ->map(fn (ActivityLibrary $l) => $l->only(['id', 'name']))
                ->values(),
        ]);
    }

    /**
     * The camp-creation wizard.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('camps/Create', [
            'libraries' => $request->user()->activityLibraries()
                ->orderBy('name')->get()
                ->map(fn (ActivityLibrary $l) => $l->only(['id', 'name']))
                ->values(),
            'defaultSlots' => $this->defaultSlots(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:40'],
            'color' => ['nullable', 'string', 'max:20'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'activity_library_id' => ['nullable', 'exists:activity_libraries,id'],
            'slots' => ['nullable', 'array'],
            'slots.*.name' => ['required_with:slots', 'string', 'max:255'],
            'slots.*.start_time' => ['required_with:slots', 'date_format:H:i'],
            'slots.*.end_time' => ['required_with:slots', 'date_format:H:i', 'after:slots.*.start_time'],
            'slots.*.kind' => ['required_with:slots', 'in:fixed,activity'],
            'slots.*.color' => ['nullable', 'string', 'max:20'],
            'trip_dates' => ['nullable', 'array'],
            'trip_dates.*' => ['date'],
            'leader_emails' => ['nullable', 'array'],
            'leader_emails.*' => ['email', 'max:255'],
        ]);

        $user = $request->user();

        // Link an existing library the user belongs to, or create a fresh one.
        $library = null;
        if (! empty($data['activity_library_id'])) {
            $library = ActivityLibrary::findOrFail((int) $data['activity_library_id']);
            abort_unless($library->hasMember($user), 403);
        }

        $camp = DB::transaction(function () use ($data, $user, $library) {
            if (! $library) {
                $library = ActivityLibrary::create([
                    'name' => $data['name'],
                    'owner_id' => $user->id,
                ]);
                $library->members()->attach($user->id, ['role' => 'owner']);
                $library->seedDefaultCategories();
            }

            $camp = Camp::create([
                'owner_id' => $user->id,
                'activity_library_id' => $library->id,
                'name' => $data['name'],
                'icon' => $data['icon'] ?? 'tent',
                'color' => $data['color'] ?? 'emerald',
                'year' => $data['year'],
                'description' => $data['description'] ?? null,
                'location' => $data['location'] ?? null,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
            ]);

            $camp->addMember($user, 'owner');

            // Daily blocks: from the wizard, or the sensible defaults.
            $slots = ! empty($data['slots']) ? $data['slots'] : $this->defaultSlots();
            foreach (array_values($slots) as $i => $slot) {
                $camp->timeSlots()->create([
                    'name' => $slot['name'],
                    'start_time' => $slot['start_time'],
                    'end_time' => $slot['end_time'],
                    'kind' => $slot['kind'],
                    'color' => $slot['color'] ?? null,
                    'position' => $i,
                ]);
            }

            $this->generateDays($camp, $data['trip_dates'] ?? null);

            foreach ($data['leader_emails'] ?? [] as $email) {
                $this->inviteLeader($camp, $email, $user);
            }

            return $camp;
        });

        // Inertia's own flash channel (not the plain session one) is what the
        // client actually listens to, and it is never persisted into history
        // state — so the onboarding modal fires exactly once, for the creator.
        Inertia::flash('camp_onboarding', true);
        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Camp created.'),
        ]);

        return to_route('camps.show', $camp);
    }

    /**
     * Add an existing user as a leader, or store a pending e-mail invitation.
     */
    protected function inviteLeader(Camp $camp, string $email, User $inviter): void
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return;
        }

        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if ($user) {
            if (! $camp->hasMember($user)) {
                $camp->addMember($user, 'leader');
            }

            return;
        }

        $camp->invitations()->updateOrCreate(
            ['email' => $email],
            ['role' => 'leader', 'invited_by' => $inviter->id, 'accepted_at' => null],
        );
    }

    public function show(Camp $camp): Response
    {
        $this->authorize('view', $camp);

        $camp->load([
            'timeSlots',
            'days.entries' => fn ($q) => $q->orderBy('start_time'),
            'days.entries.activity:id,name,color',
            'days.entries.groupPoints',
            'days.slotOverrides',
            'days.reviews.ratings',
            'days.reviews.answers',
            'days.reviews.user:id,name',
        ]);

        $userId = Auth::id();
        $lastDayId = $camp->days->last()?->id;

        $slots = $camp->timeSlots->map(fn (TimeSlot $slot) => [
            'id' => $slot->id,
            'name' => $slot->name,
            'start_time' => substr((string) $slot->start_time, 0, 5),
            'end_time' => substr((string) $slot->end_time, 0, 5),
            'kind' => $slot->kind,
            'color' => $slot->color,
            'position' => $slot->position,
        ])->values();

        $days = $camp->days->map(function (CampDay $day) use ($userId, $lastDayId) {
            // Aggregate every leader's activity ratings for the day, keyed by entry.
            $ratingsByEntry = $day->reviews->flatMap->ratings->groupBy('program_entry_id');

            $entries = $day->entries->map(function (ProgramEntry $entry) use ($ratingsByEntry) {
                $rs = $ratingsByEntry->get($entry->id);

                return [
                    'id' => $entry->id,
                    'activity_id' => $entry->activity_id,
                    'kind' => $entry->kind,
                    'activity' => $entry->activity?->only(['id', 'name', 'color']),
                    'start_time' => substr((string) $entry->start_time, 0, 5),
                    'duration' => $entry->duration,
                    'title' => $entry->title,
                    'description' => $entry->description,
                    'responsible' => $entry->responsible,
                    'materials' => $entry->materials,
                    'notes' => $entry->notes,
                    'status' => $entry->status,
                    'points_mode' => $entry->points_mode,
                    'points' => $entry->groupPoints->map(fn (EntryGroupPoint $p) => [
                        'camp_group_id' => $p->camp_group_id,
                        'value' => $p->value,
                    ])->values()->all(),
                    'avg_rating' => $rs && $rs->count() ? round($rs->avg('rating'), 1) : null,
                    'rating_count' => $rs?->count() ?? 0,
                ];
            })->values()->all();

            // The current user's own review, if any.
            $mine = $day->reviews->firstWhere('user_id', $userId);
            $myReview = null;
            if ($mine) {
                $myRatings = [];
                foreach ($mine->ratings as $r) {
                    $myRatings[$r->program_entry_id] = ['rating' => $r->rating, 'reason' => $r->reason];
                }
                $myAnswers = [];
                foreach ($mine->answers as $a) {
                    $myAnswers[$a->feedback_question_id] = $a->answer;
                }
                $myReview = [
                    'notes' => $mine->notes,
                    'camp_rating' => $mine->camp_rating,
                    'camp_reason' => $mine->camp_reason,
                    'ratings' => $myRatings,
                    'answers' => $myAnswers,
                ];
            }

            $allRatings = $day->reviews->flatMap->ratings;
            $campRatings = $day->reviews->pluck('camp_rating')->filter();

            return [
                'id' => $day->id,
                'date' => $day->date->toDateString(),
                'weekday' => Weekdays::for($day->date->dayOfWeekIso),
                'label' => $day->date->format('j.n.'),
                'is_trip' => $day->is_trip,
                'trip_name' => $day->trip_name,
                'name_days' => $day->name_days,
                'birthdays' => $day->birthdays,
                'materials' => $day->materials,
                'notes' => $day->notes,
                'entries' => $entries,
                'slot_overrides' => $day->slotOverrides->map(fn (TimeSlotOverride $o) => [
                    'time_slot_id' => $o->time_slot_id,
                    'start_time' => $o->start_time === null ? null : substr((string) $o->start_time, 0, 5),
                    'end_time' => $o->end_time === null ? null : substr((string) $o->end_time, 0, 5),
                    'is_hidden' => $o->is_hidden,
                ])->values()->all(),
                'is_last' => $day->id === $lastDayId,
                'my_review' => $myReview,
                'review_summary' => [
                    'reviewers' => $day->reviews->count(),
                    'avg' => $allRatings->count() ? round($allRatings->avg('rating'), 1) : null,
                    'camp_avg' => $campRatings->count() ? round($campRatings->avg(), 1) : null,
                ],
            ];
        })->values();

        return Inertia::render('camps/Show', [
            'camp' => [
                'id' => $camp->id,
                'name' => $camp->name,
                'icon' => $camp->icon,
                'color' => $camp->color,
                'year' => $camp->year,
                'description' => $camp->description,
                'location' => $camp->location,
                'start_date' => $camp->start_date->toDateString(),
                'end_date' => $camp->end_date->toDateString(),
                'owner_id' => $camp->owner_id,
                'is_owner' => $camp->owner_id === Auth::id(),
                'schedule_locked' => $camp->isScheduleLocked(),
            ],
            'slots' => $slots,
            'days' => $days,
            'planVersions' => $camp->planVersions()->with('user:id,name')->latest()->get()
                ->map(fn (PlanVersion $v) => [
                    'id' => $v->id,
                    'name' => $v->name,
                    'author' => $v->user?->name,
                    'created_at' => $v->created_at?->toIso8601String(),
                ])->values(),
            'membersCount' => $camp->members()->count(),
            // Which competing groups this user leads — used to nudge them when
            // their group's result for a scoring activity is still missing.
            'myGroupIds' => $camp->groups()
                ->where('competes', true)
                ->whereHas('leaders', fn ($q) => $q->where('user_id', $userId))
                ->pluck('id')->values()->all(),
            // The camp's own review questions, asked inside the review wizard.
            'feedbackQuestions' => $camp->feedbackQuestions()->active()->get()
                ->map(fn (FeedbackQuestion $q) => [
                    'id' => $q->id,
                    'scope' => $q->scope,
                    'text' => $q->text,
                    'position' => $q->position,
                ])->values()->all(),
            // Typing a responsible person stays free text; the planner only uses
            // this to show which names it recognises.
            'leaders' => $camp->leaders()->get()->map(fn (CampLeader $leader) => [
                'id' => $leader->id,
                'name' => $leader->name,
                'user_id' => $leader->user_id,
                'color' => $leader->color,
            ])->values()->all(),
            'activities' => ($camp->activityLibrary?->activities()->get() ?? collect())
                ->map(fn (Activity $a) => [
                    'id' => $a->id,
                    'name' => $a->name,
                    'category_id' => $a->activity_category_id,
                    'description' => $a->description,
                    'default_duration' => $a->default_duration,
                    'color' => $a->color,
                    'materials' => $a->materials,
                ])->values(),
            'categories' => ($camp->activityLibrary?->categories()->get() ?? collect())
                ->map(fn ($c) => $c->only(['id', 'name', 'color']))->values(),
            'library' => $camp->activityLibrary?->only(['id', 'name']),
        ]);
    }

    /**
     * The camp's own settings page (name, appearance, dates, deletion).
     */
    public function settings(Camp $camp): Response
    {
        $this->authorize('update', $camp);

        return Inertia::render('camps/Settings', [
            'camp' => [
                'id' => $camp->id,
                'name' => $camp->name,
                'icon' => $camp->icon,
                'color' => $camp->color,
                'year' => $camp->year,
                'description' => $camp->description,
                'location' => $camp->location,
                'start_date' => $camp->start_date->toDateString(),
                'end_date' => $camp->end_date->toDateString(),
                'owner_id' => $camp->owner_id,
                'is_owner' => $camp->owner_id === Auth::id(),
                'schedule_locked' => $camp->isScheduleLocked(),
            ],
        ]);
    }

    public function update(Request $request, Camp $camp): RedirectResponse
    {
        $this->authorize('update', $camp);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:40'],
            'color' => ['nullable', 'string', 'max:20'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $datesChanged = $camp->start_date->toDateString() !== Carbon::parse($data['start_date'])->toDateString()
            || $camp->end_date->toDateString() !== Carbon::parse($data['end_date'])->toDateString();

        $camp->update($data);

        // Days are the days the camp spans — keep them in sync with the range.
        if ($datesChanged) {
            $this->syncDays($camp->refresh());
        }

        return back()->with('toast', ['type' => 'success', 'message' => __('Camp updated.')]);
    }

    public function destroy(Camp $camp): RedirectResponse
    {
        $this->authorize('delete', $camp);

        $camp->delete();

        return to_route('camps.index')->with('toast', [
            'type' => 'success',
            'message' => __('Camp deleted.'),
        ]);
    }

    /**
     * Duplicate a camp's structure (slots + activities + program) into a new session.
     */
    public function duplicate(Request $request, Camp $camp): RedirectResponse
    {
        $this->authorize('view', $camp);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'copy_program' => ['boolean'],
        ]);

        $copyProgram = (bool) ($data['copy_program'] ?? true);

        $new = DB::transaction(function () use ($camp, $data, $copyProgram, $request) {
            $new = Camp::create([
                'owner_id' => $request->user()->id,
                // The duplicate shares the source camp's activity library.
                'activity_library_id' => $camp->activity_library_id,
                'name' => $data['name'],
                'icon' => $camp->icon,
                'color' => $camp->color,
                'year' => $data['year'],
                'description' => $camp->description,
                'location' => $camp->location,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
            ]);
            $new->addMember($request->user(), 'owner');

            // Carry the leader list over as names. Rows for other people's
            // accounts arrive unlinked — they aren't members of the new camp
            // yet — and re-link by name when they join.
            $carried = $new->leaders()->get();
            $newLeaderIdByOld = [];
            foreach ($camp->leaders()->get() as $leader) {
                $match = $carried->first(
                    fn (CampLeader $l) => NameMatcher::matches($l->name, $leader->name),
                );

                if (! $match) {
                    $match = $new->leaders()->create(['name' => $leader->name, 'color' => $leader->color]);
                    $carried->push($match);
                }

                $newLeaderIdByOld[$leader->id] = $match->id;
            }

            // Group structure carries over; points and standings do not.
            $newTypeIdByOld = [];
            foreach ($camp->groupTypes()->get() as $type) {
                $newTypeIdByOld[$type->id] = $new->groupTypes()->create(
                    $type->only(['name', 'color', 'position']),
                )->id;
            }

            foreach ($camp->groups()->with('leaders:id')->get() as $group) {
                $copy = $new->groups()->create([
                    'group_type_id' => $newTypeIdByOld[$group->group_type_id] ?? null,
                    'name' => $group->name,
                    'competes' => $group->competes,
                    'color' => $group->color,
                    'position' => $group->position,
                ]);

                $copy->leaders()->sync(
                    $group->leaders
                        ->map(fn (CampLeader $l) => $newLeaderIdByOld[$l->id] ?? null)
                        ->filter()
                        ->all(),
                );
            }

            // The questions carry over; the answers stay with the old camp.
            foreach ($camp->feedbackQuestions()->active()->get() as $question) {
                $new->feedbackQuestions()->create($question->only(['scope', 'text', 'position']));
            }

            // Copy the time-block guides, remembering which new slot each old
            // one became so per-day overrides can point at the right block.
            $newSlotIdByOld = [];
            foreach ($camp->timeSlots()->get() as $slot) {
                $newSlotIdByOld[$slot->id] = $new->timeSlots()->create($slot->only([
                    'name', 'start_time', 'end_time', 'kind', 'color', 'position',
                ]))->id;
            }

            $this->generateDays($new);

            // Optionally copy the program content onto matching days by position.
            if ($copyProgram) {
                $oldDays = $camp->days()->with(['entries', 'slotOverrides'])->get()->values();
                $newDays = $new->days()->get()->values();

                foreach ($oldDays as $i => $oldDay) {
                    $target = $newDays[$i] ?? null;
                    if (! $target) {
                        break;
                    }
                    $target->update([
                        'is_trip' => $oldDay->is_trip,
                        'trip_name' => $oldDay->trip_name,
                    ]);
                    foreach ($oldDay->entries as $entry) {
                        $target->entries()->create([
                            'activity_id' => $entry->activity_id,
                            'kind' => $entry->kind,
                            'start_time' => $entry->start_time,
                            'duration' => $entry->duration,
                            'title' => $entry->title,
                            'description' => $entry->description,
                            'responsible' => $entry->responsible,
                            'materials' => $entry->materials,
                            'notes' => $entry->notes,
                            'points_mode' => $entry->points_mode,
                        ]);
                    }

                    foreach ($oldDay->slotOverrides as $override) {
                        $slotId = $newSlotIdByOld[$override->time_slot_id] ?? null;

                        if (! $slotId) {
                            continue;
                        }

                        $target->slotOverrides()->create([
                            'time_slot_id' => $slotId,
                            'start_time' => $override->start_time,
                            'end_time' => $override->end_time,
                            'is_hidden' => $override->is_hidden,
                        ]);
                    }
                }
            }

            return $new;
        });

        return to_route('camps.show', $new)->with('toast', [
            'type' => 'success',
            'message' => __('Camp duplicated.'),
        ]);
    }

    /**
     * Freeze the program for everyone — the owner included — so an accidental
     * drag can't move anything until it is unlocked again.
     */
    public function lock(Camp $camp): RedirectResponse
    {
        $this->authorize('manageMembers', $camp);

        $camp->update(['schedule_locked_at' => now()]);

        return back()->with('toast', ['type' => 'success', 'message' => __('Schedule locked.')]);
    }

    public function unlock(Camp $camp): RedirectResponse
    {
        $this->authorize('manageMembers', $camp);

        $camp->update(['schedule_locked_at' => null]);

        return back()->with('toast', ['type' => 'success', 'message' => __('Schedule unlocked.')]);
    }

    /**
     * The default daily time skeleton offered by the wizard.
     *
     * @return list<array{name: string, start_time: string, end_time: string, kind: string, color: string}>
     */
    protected function defaultSlots(): array
    {
        return [
            ['name' => 'Ranné chvály', 'start_time' => '08:00', 'end_time' => '08:30', 'kind' => 'activity', 'color' => 'sky'],
            ['name' => 'Scénka', 'start_time' => '08:30', 'end_time' => '09:00', 'kind' => 'activity', 'color' => 'violet'],
            ['name' => 'BLOK I.', 'start_time' => '09:00', 'end_time' => '12:00', 'kind' => 'activity', 'color' => 'emerald'],
            ['name' => 'Obed', 'start_time' => '12:00', 'end_time' => '12:30', 'kind' => 'fixed', 'color' => 'amber'],
            ['name' => 'Odpočinok', 'start_time' => '12:30', 'end_time' => '13:30', 'kind' => 'fixed', 'color' => 'slate'],
            ['name' => 'Scénka', 'start_time' => '13:30', 'end_time' => '13:45', 'kind' => 'activity', 'color' => 'violet'],
            ['name' => 'BLOK II.', 'start_time' => '13:45', 'end_time' => '15:30', 'kind' => 'activity', 'color' => 'emerald'],
            ['name' => 'Slovko', 'start_time' => '15:30', 'end_time' => '16:00', 'kind' => 'activity', 'color' => 'rose'],
        ];
    }

    /**
     * Generate a CampDay for each calendar day between start and end, skipping none.
     *
     * @param  list<string>|null  $tripDates  dates flagged as trips (else Tue/Thu default)
     */
    protected function generateDays(Camp $camp, ?array $tripDates = null): void
    {
        $existing = $camp->days()->pluck('date')->map(fn ($d) => Carbon::parse($d)->toDateString())->all();

        // Dates are cast to CarbonImmutable, so advance by reassigning addDay()'s result.
        $cursor = Carbon::parse($camp->start_date->toDateString());
        $end = Carbon::parse($camp->end_date->toDateString());
        $position = $camp->days()->count();

        while ($cursor->lte($end)) {
            $date = $cursor->toDateString();
            if (! in_array($date, $existing, true)) {
                $camp->days()->create([
                    'date' => $date,
                    'position' => $position++,
                    'is_trip' => $tripDates !== null
                        ? in_array($date, $tripDates, true)
                        : in_array($cursor->dayOfWeek, [Carbon::TUESDAY, Carbon::THURSDAY], true),
                    'name_days' => NameDays::for($cursor),
                ]);
            }
            $cursor = $cursor->addDay();
        }
    }

    /**
     * Reconcile the camp's days with its date range (days are the days the camp
     * spans — not manually added/removed). Existing days keep their content and
     * shift by position; surplus days are removed, missing ones created.
     */
    protected function syncDays(Camp $camp): void
    {
        $dates = [];
        $cursor = Carbon::parse($camp->start_date->toDateString());
        $end = Carbon::parse($camp->end_date->toDateString());
        while ($cursor->lte($end)) {
            $dates[] = $cursor->toDateString();
            $cursor = $cursor->addDay();
        }
        $count = count($dates);

        DB::transaction(function () use ($camp, $dates, $count) {
            $days = $camp->days()->orderBy('position')->orderBy('date')->get()->values();

            // Drop days beyond the new length (their program cascades away).
            $days->slice($count)->each->delete();
            $kept = $days->slice(0, $count)->values();

            $originalDates = $kept->map(fn (CampDay $d) => $d->date->toDateString())->all();

            // Park kept days on temporary, distinct dates to dodge the (camp_id,date)
            // unique index while re-dating them by position.
            $parkBase = Carbon::parse('2999-01-01');
            foreach ($kept as $i => $day) {
                $day->update([
                    'date' => $parkBase->copy()->addDays($i)->toDateString(),
                    'position' => $i,
                ]);
            }

            // Assign the real dates; refresh name days only where the date changed.
            foreach ($kept as $i => $day) {
                $attrs = ['date' => $dates[$i]];
                if (($originalDates[$i] ?? null) !== $dates[$i]) {
                    $attrs['name_days'] = NameDays::for($dates[$i]);
                }
                $day->update($attrs);
            }

            // Create any missing trailing days.
            for ($i = $kept->count(); $i < $count; $i++) {
                $camp->days()->create([
                    'date' => $dates[$i],
                    'position' => $i,
                    'is_trip' => false,
                    'name_days' => NameDays::for($dates[$i]),
                ]);
            }
        });
    }

    /**
     * Fill in Slovak name days for every camp day that doesn't have one yet.
     */
    public function fillNameDays(Camp $camp): RedirectResponse
    {
        $this->authorize('update', $camp);

        $filled = 0;
        $days = $camp->days()
            ->where(fn ($q) => $q->whereNull('name_days')->orWhere('name_days', ''))
            ->get();

        foreach ($days as $day) {
            if ($name = NameDays::for($day->date)) {
                $day->update(['name_days' => $name]);
                $filled++;
            }
        }

        return back()->with('toast', [
            'type' => 'success',
            'message' => __('Filled name days for :count days.', ['count' => $filled]),
        ]);
    }
}
