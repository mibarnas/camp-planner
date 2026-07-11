<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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

        // Backfill from the block each entry currently belongs to.
        DB::statement('
            UPDATE program_entries pe
            JOIN time_slots ts ON pe.time_slot_id = ts.id
            SET pe.start_time = ts.start_time,
                pe.duration = GREATEST(TIMESTAMPDIFF(MINUTE, ts.start_time, ts.end_time), 5)
        ');

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
