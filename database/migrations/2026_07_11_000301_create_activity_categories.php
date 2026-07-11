<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Categories become user-defined tags scoped to an activity library,
     * replacing the hardcoded `activities.category` string enum.
     */
    public function up(): void
    {
        Schema::create('activity_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_library_id')->constrained('activity_libraries')->cascadeOnDelete();
            $table->string('name');
            $table->string('color', 20)->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['activity_library_id', 'name']);
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->foreignId('activity_category_id')->nullable()->after('activity_library_id')
                ->constrained('activity_categories')->nullOnDelete();
        });

        // Backfill: per library, turn the legacy category strings its activities
        // use into real category rows (with the original Slovak labels/colours).
        $legacy = [
            'spiritual' => ['Duchovné', 'sky'],
            'skit' => ['Scénka', 'violet'],
            'game' => ['Hra', 'emerald'],
            'sport' => ['Šport', 'lime'],
            'craft' => ['Tvorenie', 'orange'],
            'station' => ['Stanoviská', 'teal'],
            'educational' => ['Náučné', 'indigo'],
            'meal' => ['Jedlo / oddych', 'amber'],
            'trip' => ['Výlet', 'fuchsia'],
            'other' => ['Iné', 'slate'],
        ];

        $rows = DB::table('activities')
            ->whereNotNull('activity_library_id')
            ->select('activity_library_id', 'category')
            ->distinct()
            ->get();

        foreach ($rows as $i => $row) {
            [$name, $color] = $legacy[$row->category] ?? [ucfirst($row->category), 'slate'];

            $categoryId = DB::table('activity_categories')->insertGetId([
                'activity_library_id' => $row->activity_library_id,
                'name' => $name,
                'color' => $color,
                'position' => $i,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('activities')
                ->where('activity_library_id', $row->activity_library_id)
                ->where('category', $row->category)
                ->update(['activity_category_id' => $categoryId]);
        }

        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->string('category')->default('other')->after('name');
        });
        Schema::table('activities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('activity_category_id');
        });
        Schema::dropIfExists('activity_categories');
    }
};
