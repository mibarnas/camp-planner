<?php

namespace App\Http\Controllers;

use App\Http\Middleware\HandleLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    /**
     * Switch the UI language. The choice is a plain preference cookie (no
     * account needed, no tracking), kept for a year like the appearance cookie.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in(HandleLocale::supported())],
        ]);

        Cookie::queue(Cookie::forever('locale', $validated['locale'], sameSite: 'lax'));

        return back();
    }
}
