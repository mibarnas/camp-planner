<?php

namespace App\Http\Controllers;

use App\Models\Camp;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    /**
     * Every account and the camps it owns or leads. Read-only.
     */
    public function index(): Response
    {
        $users = User::query()
            ->with(['camps' => fn ($query) => $query->orderByDesc('year')->orderBy('name')])
            ->latest()
            ->orderByDesc('id')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified' => $user->email_verified_at !== null,
                'created_at' => $user->created_at?->toIso8601String(),
                'camps' => $user->camps->map(fn (Camp $camp) => [
                    'id' => $camp->id,
                    'name' => $camp->name,
                    'icon' => $camp->icon,
                    'color' => $camp->color,
                    'year' => $camp->year,
                    'start_date' => $camp->start_date->toDateString(),
                    'end_date' => $camp->end_date->toDateString(),
                    // Ownership comes from the camp itself; the pivot role is
                    // only a mirror of it.
                    'role' => $camp->owner_id === $user->id ? 'owner' : 'leader',
                ])->values(),
            ]);

        return Inertia::render('admin/Index', [
            'users' => $users,
            'stats' => [
                'users' => $users->count(),
                'camps' => Camp::count(),
                'usersWithoutCamp' => $users->filter(fn (array $u) => $u['camps']->isEmpty())->count(),
            ],
        ]);
    }
}
