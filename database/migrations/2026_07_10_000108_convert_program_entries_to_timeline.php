<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add the time-positioning columns.
        Schema::table('program_entries', function (Blueprint $table) {
            $table->time('start_time')->nullable()->after('activity_id');
            $table->unsignedSmallInteger('duration')->default(60)->after('start_time'); // minutes
        });

        // Backfill from the block each entry currently belongs to. Done in PHP so it
        // works on every driver (the old UPDATE ... JOIN was MySQL-only and broke SQLite).
        DB::table('time_slots')->orderBy('id')->each(function ($slot) {
            $start = Carbon::parse($slot->start_time);
            $end = Carbon::parse($slot->end_time);
            $duration = max((int) abs($start->diffInMinutes($end)), 5);

            DB::table('program_entries')
                ->where('time_slot_id', $slot->id)
                ->update([
                    'start_time' => $slot->start_time,
                    'duration' => $duration,
                ]);
        });

        // Entries are now positioned by time, not bound to a single block.
        // Drop the time_slot FK first; then give camp_day_id its own index so the
        // composite unique (which the camp_day_id FK was relying on) can be dropped.
        Schema::table('program_entries', function (Blueprint $table) {
            $table->dropForeign(['time_slot_id']);
        });
        Schema::table('program_entries', function (Blueprint $table) {
            $table->index('camp_day_id');
        });
        Schema::table('program_entries', function (Blueprint $table) {
            $table->dropUnique('program_entries_camp_day_id_time_slot_id_unique');
        });
        Schema::table('program_entries', function (Blueprint $table) {
            $table->dropColumn('time_slot_id');
        });
    }

    public function down(): void
    {
        Schema::table('program_entries', function (Blueprint $table) {
            $table->foreignId('time_slot_id')->nullable()->after('camp_day_id')->constrained('time_slots')->cascadeOnDelete();
            $table->dropIndex(['camp_day_id']);
            $table->dropColumn(['start_time', 'duration']);
        });
    }
};
