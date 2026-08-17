<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityLibrary;
use App\Models\LibraryVersion;

class LibrarySnapshot
{
    /**
     * The library's categories and activities as a portable payload. Nothing
     * references database ids, so the same payload works as a downloadable JSON
     * export and as a stored version.
     *
     * @return array<string, mixed>
     */
    public static function payload(ActivityLibrary $library): array
    {
        return [
            'format' => LibraryVersion::FORMAT,
            'version' => LibraryVersion::VERSION,
            'name' => $library->name,
            'categories' => $library->categories()->get()
                ->map(fn ($c) => ['name' => $c->name, 'color' => $c->color])->values(),
            'activities' => $library->activities()->with('category:id,name')->get()
                ->map(fn (Activity $a) => [
                    'name' => $a->name,
                    'category' => $a->category?->name,
                    'description' => $a->description,
                    'default_duration' => $a->default_duration,
                    'color' => $a->color,
                    'materials' => $a->materials,
                ])->values(),
        ];
    }
}
