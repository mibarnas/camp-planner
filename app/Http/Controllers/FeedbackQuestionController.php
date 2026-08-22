<?php

namespace App\Http\Controllers;

use App\Models\Camp;
use App\Models\FeedbackQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FeedbackQuestionController extends Controller
{
    public function store(Request $request, Camp $camp): RedirectResponse
    {
        $this->authorize('update', $camp);

        $data = $request->validate([
            'scope' => ['required', 'in:day,camp'],
            'text' => ['required', 'string', 'max:500'],
        ]);

        $camp->feedbackQuestions()->create([
            ...$data,
            'position' => (int) $camp->feedbackQuestions()->max('position') + 1,
        ]);

        return back()->with('toast', ['type' => 'success', 'message' => __('Question added.')]);
    }

    /**
     * Only the wording changes — answers already given stay attached, and the
     * scope is fixed once anyone has answered so day/camp answers can't swap.
     */
    public function update(Request $request, Camp $camp, FeedbackQuestion $feedbackQuestion): RedirectResponse
    {
        $this->authorize('update', $camp);

        $data = $request->validate([
            'text' => ['required', 'string', 'max:500'],
            'scope' => ['sometimes', 'in:day,camp'],
        ]);

        $scope = $data['scope'] ?? $feedbackQuestion->scope;

        if ($scope !== $feedbackQuestion->scope && $feedbackQuestion->answers()->exists()) {
            throw ValidationException::withMessages([
                'scope' => __('A question that has answers cannot change its scope.'),
            ]);
        }

        $feedbackQuestion->update(['text' => $data['text'], 'scope' => $scope]);

        return back()->with('toast', ['type' => 'success', 'message' => __('Question updated.')]);
    }

    /**
     * A question nobody answered is simply gone; one with answers is archived so
     * what leaders already wrote stays readable.
     */
    public function destroy(Camp $camp, FeedbackQuestion $feedbackQuestion): RedirectResponse
    {
        $this->authorize('update', $camp);

        if ($feedbackQuestion->answers()->exists()) {
            $feedbackQuestion->update(['archived_at' => now()]);

            return back()->with('toast', [
                'type' => 'success',
                'message' => __('Question archived — its answers are kept.'),
            ]);
        }

        $feedbackQuestion->delete();

        return back()->with('toast', ['type' => 'success', 'message' => __('Question removed.')]);
    }
}
