<?php

namespace App\Http\Controllers;

use App\Models\ActivityCategory;
use App\Models\ActivityLibrary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ActivityCategoryController extends Controller
{
    public function store(Request $request, ActivityLibrary $library): RedirectResponse
    {
        $this->authorize('update', $library);

        $data = $this->validateData($request, $library);

        $library->categories()->create([
            ...$data,
            'position' => (int) $library->categories()->max('position') + 1,
        ]);

        return back()->with('toast', ['type' => 'success', 'message' => __('Category added.')]);
    }

    public function update(Request $request, ActivityCategory $category): RedirectResponse
    {
        $this->authorize('update', $category->library);

        $category->update($this->validateData($request, $category->library, $category));

        return back()->with('toast', ['type' => 'success', 'message' => __('Category updated.')]);
    }

    public function destroy(ActivityCategory $category): RedirectResponse
    {
        $this->authorize('update', $category->library);

        // Activities keep existing; they just become uncategorised (FK nulls out).
        $category->delete();

        return back()->with('toast', ['type' => 'success', 'message' => __('Category removed.')]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateData(Request $request, ActivityLibrary $library, ?ActivityCategory $ignore = null): array
    {
        return $request->validate([
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('activity_categories', 'name')
                    ->where('activity_library_id', $library->id)
                    ->ignore($ignore?->id),
            ],
            'color' => ['nullable', 'string', 'max:20'],
        ]);
    }
}
