<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * What one group scored in one activity. In 'placement' mode this is the
     * raw result (seconds, goals, whatever the leaders counted) and the awarded
     * points are derived from it on read — never stored — so editing a value or
     * dropping a group re-settles the whole leaderboard on its own.
     */
    public function up(): void
    {
        Schema::create('entry_group_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_entry_id')->constrained('program_entries')->cascadeOnDelete();
            $table->foreignId('camp_group_id')->constrained('camp_groups')->cascadeOnDelete();
            $table->decimal('value', 10, 2);
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['program_entry_id', 'camp_group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entry_group_points');
    }
};
