<?php

namespace App\Http\Middleware;

use App\Models\Camp;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'locale' => app()->getLocale(),
            'supportedLocales' => HandleLocale::supported(),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'campContext' => $this->campContext($request),
        ];
    }

    /**
     * The camp the current page belongs to, so the sidebar can show its section.
     * Every camp-scoped page binds {camp}, so this needs no per-controller code.
     *
     * @return array<string, mixed>|null
     */
    protected function campContext(Request $request): ?array
    {
        $camp = $request->route('camp');
        $user = $request->user();

        if (! $camp instanceof Camp || $user === null || ! $user->can('view', $camp)) {
            return null;
        }

        return [
            'id' => $camp->id,
            'name' => $camp->name,
            'icon' => $camp->icon,
            'color' => $camp->color,
            'is_owner' => $camp->owner_id === $user->id,
        ];
    }
}
