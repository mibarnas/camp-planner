<?php

namespace App\Http\Controllers;

use App\Support\ChangelogDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ChangelogController extends Controller
{
    /**
     * The current release notes, on demand.
     *
     * The shared `changelog` prop stops being sent once the user has read the
     * notes, so re-opening them from the sidebar has to fetch them. Kept off
     * the page payload deliberately: most visits never need it.
     */
    public function show(): JsonResponse
    {
        $version = (string) config('app.version');

        if (! ChangelogDocument::exists($version)) {
            return response()->json(['message' => __('No release notes yet.')], 404);
        }

        return response()->json([
            'version' => $version,
            'html' => ChangelogDocument::render($version, app()->getLocale()),
        ]);
    }

    /**
     * Remember that this user has read the current version's release notes,
     * so the shared `changelog` prop stops being sent.
     */
    public function dismiss(Request $request): RedirectResponse
    {
        $user = $request->user();

        $user->last_seen_version = (string) config('app.version');
        $user->save();

        return back();
    }
}
