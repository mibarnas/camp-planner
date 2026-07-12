<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AiController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Ai', [
            'hasKey' => filled($request->user()->gemini_api_key),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'gemini_api_key' => ['nullable', 'string', 'max:200'],
        ]);

        $user = $request->user();
        $user->gemini_api_key = filled($data['gemini_api_key'] ?? null) ? $data['gemini_api_key'] : null;
        $user->save();

        return back()->with('toast', [
            'type' => 'success',
            'message' => filled($user->gemini_api_key) ? __('Gemini key saved.') : __('Gemini key removed.'),
        ]);
    }
}
