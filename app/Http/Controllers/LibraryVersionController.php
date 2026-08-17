<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityCategory;
use App\Models\ActivityLibrary;
use App\Models\LibraryVersion;
use App\Services\LibrarySnapshot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LibraryVersionController extends Controller
{
    /**
     * Save the library's activities under a name so they can be restored later.
     */
    public function store(Request $request, ActivityLibrary $library): RedirectResponse
    {
        $this->authorize('manage', $library);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        LibraryVersion::create([
            'activity_library_id' => $library->id,
            'user_id' => $request->user()->id,
            'name' => $data['name'],
            'payload' => LibrarySnapshot::payload($library),
        ]);

        return back()->with('toast', ['type' => 'success', 'message' => __('Version saved.')]);
    }

    /**
     * Bring the library back to a saved state. The state being replaced is kept as
     * an automatic backup version first, so a restore is never final.
     */
    public function restore(Request $request, ActivityLibrary $library, LibraryVersion $libraryVersion): RedirectResponse
    {
        $this->authorize('manage', $library);

        $payload = $libraryVersion->payload;

        if (($payload['version'] ?? null) !== LibraryVersion::VERSION) {
            return back()->with('toast', [
                'type' => 'error',
                'message' => __('This version was saved in an unsupported format.'),
            ]);
        }

        DB::transaction(function () use ($library, $libraryVersion, $payload, $request) {
            LibraryVersion::create([
                'activity_library_id' => $library->id,
                'user_id' => $request->user()->id,
                'name' => __('Before restoring: :name', ['name' => $libraryVersion->name]),
                'payload' => LibrarySnapshot::payload($library),
            ]);

            $categories = $this->resolveCategories($library, $payload);

            // Activities are matched by name so the ones that survive keep their ids —
            // and with them every schedule cell that points at them.
            $live = $library->activities()->get()->groupBy(fn (Activity $a) => Str::lower($a->name));

            foreach ($payload['activities'] ?? [] as $a) {
                $name = trim((string) ($a['name'] ?? ''));

                if ($name === '') {
                    continue;
                }

                $catName = isset($a['category']) ? Str::lower((string) $a['category']) : null;
                $attributes = [
                    'activity_category_id' => $catName ? $categories->get($catName)?->id : null,
                    'name' => Str::limit($name, 255, ''),
                    'description' => is_string($a['description'] ?? null) ? $a['description'] : null,
                    'default_duration' => (int) min(max((int) ($a['default_duration'] ?? 60), 5), 1440),
                    'color' => is_string($a['color'] ?? null) ? Str::limit((string) $a['color'], 20, '') : null,
                    'materials' => is_string($a['materials'] ?? null) ? $a['materials'] : null,
                ];

                $match = $live->get(Str::lower($name))?->shift();

                if ($match) {
                    $match->update($attributes);
                } else {
                    $library->activities()->create($attributes + ['created_by' => $request->user()->id]);
                }
            }

            // Whatever the snapshot doesn't contain goes. Program entries reference
            // activities with nullOnDelete and carry their own copy of the text, so
            // schedules survive this as unlinked cells.
            foreach ($live->flatten() as $leftover) {
                $leftover->delete();
            }
        });

        return back()->with('toast', [
            'type' => 'success',
            'message' => __('Library restored. The previous state was saved as a backup.'),
        ]);
    }

    public function destroy(ActivityLibrary $library, LibraryVersion $libraryVersion): RedirectResponse
    {
        $this->authorize('manage', $library);

        $libraryVersion->delete();

        return back()->with('toast', ['type' => 'success', 'message' => __('Version deleted.')]);
    }

    /**
     * The library's categories keyed by lowercased name, creating any the snapshot
     * mentions that no longer exist. Categories are never deleted by a restore.
     *
     * @param  array<string, mixed>  $payload
     * @return Collection<string, ActivityCategory>
     */
    private function resolveCategories(ActivityLibrary $library, array $payload): Collection
    {
        $categories = $library->categories()->get()->keyBy(fn ($c) => Str::lower($c->name));
        $position = (int) $library->categories()->max('position');

        foreach ($payload['categories'] ?? [] as $cat) {
            $name = trim((string) ($cat['name'] ?? ''));

            if ($name === '' || $categories->has(Str::lower($name))) {
                continue;
            }

            $created = $library->categories()->create([
                'name' => $name,
                'color' => is_string($cat['color'] ?? null) ? $cat['color'] : null,
                'position' => ++$position,
            ]);
            $categories->put(Str::lower($name), $created);
        }

        return $categories;
    }
}
