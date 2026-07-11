<?php

namespace App\Http\Controllers;

use App\Models\ActivityLibrary;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
}
