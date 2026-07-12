<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityLibrary;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLibraryController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $library = ActivityLibrary::create([
            'name' => $data['name'],
            'owner_id' => $request->user()->id,
        ]);
        $library->members()->attach($request->user()->id, ['role' => 'owner']);
        $library->seedDefaultCategories();

        return to_route('activities.index', ['library' => $library->id])->with('toast', [
            'type' => 'success',
            'message' => __('Library created.'),
        ]);
    }

    public function update(Request $request, ActivityLibrary $library): RedirectResponse
    {
        $this->authorize('manage', $library);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $library->update($data);

        return back()->with('toast', ['type' => 'success', 'message' => __('Library renamed.')]);
    }

    public function destroy(ActivityLibrary $library): RedirectResponse
    {
        $this->authorize('manage', $library);

        $library->delete();

        return to_route('activities.index')->with('toast', [
            'type' => 'success',
            'message' => __('Library deleted.'),
        ]);
    }

    /**
     * Add a member by e-mail (they must already have an account).
     */
    public function storeMember(Request $request, ActivityLibrary $library): RedirectResponse
    {
        $this->authorize('manage', $library);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $user = User::whereRaw('LOWER(email) = ?', [strtolower($data['email'])])->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => __('No account with this e-mail. Camp members are added automatically when they join a camp.'),
            ]);
        }

        if ($library->hasMember($user)) {
            throw ValidationException::withMessages([
                'email' => __('This person is already a member.'),
            ]);
        }

        $library->members()->attach($user->id, ['role' => 'member']);

        return back()->with('toast', [
            'type' => 'success',
            'message' => __(':name was added.', ['name' => $user->name]),
        ]);
    }

    public function destroyMember(ActivityLibrary $library, User $user): RedirectResponse
    {
        $this->authorize('manage', $library);

        if ($user->id === $library->owner_id) {
            throw ValidationException::withMessages([
                'user' => __('The owner cannot be removed.'),
            ]);
        }

        $library->members()->detach($user->id);

        return back()->with('toast', ['type' => 'success', 'message' => __('Member removed.')]);
    }

    // --- Shareable link ------------------------------------------------------

    public function storeShareLink(ActivityLibrary $library): RedirectResponse
    {
        $this->authorize('manage', $library);

        if (! $library->share_token) {
            $library->update(['share_token' => Str::random(48)]);
        }

        return back()->with('toast', ['type' => 'success', 'message' => __('Share link created.')]);
    }

    public function destroyShareLink(ActivityLibrary $library): RedirectResponse
    {
        $this->authorize('manage', $library);

        $library->update(['share_token' => null]);

        return back()->with('toast', ['type' => 'success', 'message' => __('Share link revoked.')]);
    }

    /**
     * Public landing page for a library share link.
     */
    public function joinShow(Request $request, string $token): Response
    {
        $library = ActivityLibrary::withCount('activities')->where('share_token', $token)->first();

        if (! $library) {
            return Inertia::render('libraries/Join', ['valid' => false]);
        }

        $user = $request->user();
        if (! $user) {
            $request->session()->put('url.intended', $request->fullUrl());
        }

        return Inertia::render('libraries/Join', [
            'valid' => true,
            'token' => $token,
            'library' => [
                'id' => $library->id,
                'name' => $library->name,
                'activities_count' => $library->activities_count,
            ],
            'alreadyMember' => $user ? $library->hasMember($user) : false,
        ]);
    }

    public function joinAccept(Request $request, string $token): RedirectResponse
    {
        $library = ActivityLibrary::where('share_token', $token)->firstOrFail();

        $library->addMember($request->user());

        return to_route('activities.index', ['library' => $library->id])->with('toast', [
            'type' => 'success',
            'message' => __('You have joined :library.', ['library' => $library->name]),
        ]);
    }

    // --- JSON export / import ------------------------------------------------

    /**
     * Download the library (categories + activities) as a JSON file.
     */
    public function export(ActivityLibrary $library): JsonResponse
    {
        $this->authorize('view', $library);

        $payload = [
            'format' => 'taborplanner.library',
            'version' => 1,
            'name' => $library->name,
            'categories' => $library->categories()->get()
                ->map(fn ($c) => ['name' => $c->name, 'color' => $c->color])->values(),
            'activities' => $library->activities()->with('category:id,name')->get()
                ->map(fn (Activity $a) => [
                    'name' => $a->name,
                    'category' => $a->category?->name,
                    'description' => $a->description,
                    'default_duration' => $a->default_duration,
                    'color' => $a->color,
                    'materials' => $a->materials,
                ])->values(),
        ];

        $filename = Str::slug($library->name ?: 'kniznica-aktivit').'.json';

        return response()->json($payload, 200, [
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Import activities (and their categories) from a JSON file into the library.
     */
    public function import(Request $request, ActivityLibrary $library): RedirectResponse
    {
        $this->authorize('update', $library);

        $data = $request->validate([
            'file' => ['required', 'file', 'mimetypes:application/json,text/plain', 'max:2048'],
        ]);

        $decoded = json_decode((string) file_get_contents($data['file']->getRealPath()), true);

        if (! is_array($decoded) || ! is_array($decoded['activities'] ?? null)) {
            throw ValidationException::withMessages([
                'file' => __('Invalid file — expected a library JSON export.'),
            ]);
        }

        // Match categories by name (case-insensitive), creating any that are new.
        $categoryId = $library->categories()->get()
            ->keyBy(fn ($c) => Str::lower($c->name));
        $position = (int) $library->categories()->max('position');

        foreach ($decoded['categories'] ?? [] as $cat) {
            $name = trim((string) ($cat['name'] ?? ''));
            if ($name === '' || $categoryId->has(Str::lower($name))) {
                continue;
            }
            $created = $library->categories()->create([
                'name' => $name,
                'color' => is_string($cat['color'] ?? null) ? $cat['color'] : null,
                'position' => ++$position,
            ]);
            $categoryId->put(Str::lower($name), $created);
        }

        $imported = 0;
        foreach ($decoded['activities'] as $a) {
            $name = trim((string) ($a['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $catName = isset($a['category']) ? Str::lower((string) $a['category']) : null;

            $library->activities()->create([
                'activity_category_id' => $catName ? $categoryId->get($catName)?->id : null,
                'created_by' => $request->user()->id,
                'name' => Str::limit($name, 255, ''),
                'description' => is_string($a['description'] ?? null) ? $a['description'] : null,
                'default_duration' => (int) min(max((int) ($a['default_duration'] ?? 60), 5), 1440),
                'color' => is_string($a['color'] ?? null) ? Str::limit((string) $a['color'], 20, '') : null,
                'materials' => is_string($a['materials'] ?? null) ? $a['materials'] : null,
            ]);
            $imported++;
        }

        return back()->with('toast', [
            'type' => 'success',
            'message' => __(':count activities imported.', ['count' => $imported]),
        ]);
    }
}
