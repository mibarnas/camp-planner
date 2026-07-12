<?php

namespace App\Http\Controllers;

use App\Models\CampDay;
use App\Models\DayReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DayReviewController extends Controller
{
    /**
     * Create or update the current leader's review of a day: a star rating for
     * each activity (with an optional reason), plus — on the camp's last day —
     * an overall camp rating.
     */
    public function store(Request $request, CampDay $day): RedirectResponse
    {
        $this->authorize('update', $day->camp);

        $lastDay = $day->camp->days()->reorder()->orderByDesc('position')->orderByDesc('date')->first();
        $isLastDay = $lastDay?->id === $day->id;

        $data = $request->validate([
            'ratings' => ['array'],
            'ratings.*.program_entry_id' => ['required', 'integer', 'exists:program_entries,id'],
            'ratings.*.rating' => ['required', 'integer', 'min:1', 'max:5'],
            'ratings.*.reason' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string'],
            'camp_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'camp_reason' => ['nullable', 'string', 'max:2000'],
        ]);

        // Only accept ratings for entries that belong to this day.
        $dayEntryIds = $day->entries()->pluck('id')->all();

        DB::transaction(function () use ($day, $request, $data, $isLastDay, $dayEntryIds) {
            $review = DayReview::updateOrCreate(
                ['camp_day_id' => $day->id, 'user_id' => $request->user()->id],
                [
                    'notes' => $data['notes'] ?? null,
                    'camp_rating' => $isLastDay ? ($data['camp_rating'] ?? null) : null,
                    'camp_reason' => $isLastDay ? ($data['camp_reason'] ?? null) : null,
                ],
            );

            foreach ($data['ratings'] ?? [] as $item) {
                if (! in_array((int) $item['program_entry_id'], $dayEntryIds, true)) {
                    continue;
                }
                $review->ratings()->updateOrCreate(
                    ['program_entry_id' => $item['program_entry_id']],
                    ['rating' => $item['rating'], 'reason' => $item['reason'] ?? null],
                );
            }
        });

        return back()->with('toast', ['type' => 'success', 'message' => __('Day review saved.')]);
    }
}
