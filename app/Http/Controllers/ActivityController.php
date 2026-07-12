<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityLibrary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ActivityController extends Controller
{
    /**
     * Public, read-only view of a single activity (no login required).
     */
    public function shared(string $token): Response
    {
        $activity = Activity::with('category:id,name,color')->where('share_token', $token)->first();

        if (! $activity) {
            return Inertia::render('activities/Shared', ['valid' => false]);
        }

        return Inertia::render('activities/Shared', [
            'valid' => true,
            'activity' => [
                'name' => $activity->name,
                'category' => $activity->category?->only(['name', 'color']),
                'description' => $activity->description,
                'default_duration' => $activity->default_duration,
                'color' => $activity->color,
                'materials' => $activity->materials,
            ],
        ]);
    }

    public function index(Request $request): Response
    {
        $user = $request->user();

        $libraries = $user->activityLibraries()
            ->withCount('activities')
            ->orderBy('name')
            ->get();

        $selectedId = (int) $request->query('library', (string) ($libraries->first()->id ?? 0));
        $selected = $libraries->firstWhere('id', $selectedId) ?? $libraries->first();

        $activities = collect();
        $members = collect();
        $categories = collect();

        if ($selected) {
            $selected->load('members:id,name,email');
            $activities = $selected->activities()
                ->with('creator:id,name')
                ->withCount('entries')
                ->get()
                ->map(fn (Activity $a) => [
                    'id' => $a->id,
                    'name' => $a->name,
                    'category_id' => $a->activity_category_id,
                    'description' => $a->description,
                    'default_duration' => $a->default_duration,
                    'color' => $a->color,
                    'materials' => $a->materials,
                    'creator' => $a->creator?->only(['id', 'name']),
                    'usage_count' => $a->entries_count,
                    'created_at' => $a->created_at?->toDateString(),
                    'share_url' => route('activities.shared', $a->share_token),
                ]);
            $members = $selected->members->map(fn ($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'email' => $m->email,
                'role' => $m->getAttribute('pivot')?->getAttribute('role'),
            ])->values();
            $categories = $selected->categories()->get()
                ->map(fn ($c) => $c->only(['id', 'name', 'color']))->values();
        }

        return Inertia::render('activities/Index', [
            'libraries' => $libraries->map(fn (ActivityLibrary $l) => [
                'id' => $l->id,
                'name' => $l->name,
                'activities_count' => $l->activities_count,
                'is_owner' => $l->owner_id === $user->id,
            ])->values(),
            'selectedLibraryId' => $selected?->id,
            'shareLink' => $selected?->share_token
                ? route('libraries.join.show', $selected->share_token)
                : null,
            'activities' => $activities,
            'members' => $members,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $library = ActivityLibrary::findOrFail((int) $request->input('activity_library_id'));
        $this->authorize('update', $library);

        $data = $this->validateData($request, $library, withLibrary: true);

        Activity::create([...$data, 'created_by' => $request->user()->id]);

        return back()->with('toast', ['type' => 'success', 'message' => __('Activity added.')]);
    }

    public function update(Request $request, Activity $activity): RedirectResponse
    {
        if ($activity->library) {
            $this->authorize('update', $activity->library);
        }

        $activity->update($this->validateData($request, $activity->library));

        return back()->with('toast', ['type' => 'success', 'message' => __('Activity updated.')]);
    }

    public function duplicate(Activity $activity): RedirectResponse
    {
        if ($activity->library) {
            $this->authorize('update', $activity->library);
        }

        $activity->replicate()->fill([
            'name' => $activity->name.' (kópia)',
            'created_by' => request()->user()->id,
        ])->save();

        return back()->with('toast', ['type' => 'success', 'message' => __('Activity duplicated.')]);
    }

    public function destroy(Activity $activity): RedirectResponse
    {
        if ($activity->library) {
            $this->authorize('update', $activity->library);
        }

        $activity->delete();

        return back()->with('toast', ['type' => 'success', 'message' => __('Activity deleted.')]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateData(Request $request, ?ActivityLibrary $library, bool $withLibrary = false): array
    {
        $categoryRule = ['nullable'];
        if ($library) {
            // The chosen category must belong to this activity's library.
            $categoryRule[] = Rule::exists('activity_categories', 'id')
                ->where('activity_library_id', $library->id);
        }

        return $request->validate([
            ...($withLibrary ? ['activity_library_id' => ['required', 'exists:activity_libraries,id']] : []),
            'activity_category_id' => $categoryRule,
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'default_duration' => ['required', 'integer', 'min:5', 'max:1440'],
            'color' => ['nullable', 'string', 'max:20'],
            'materials' => ['nullable', 'string'],
        ]);
    }
}
