<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * How an activity scores groups: 'none' (not a scoring activity),
     * 'raw' (the entered numbers are the points), or 'placement'
     * (the entered numbers only rank the groups; points follow the ranking).
     */
    public function up(): void
    {
        Schema::table('program_entries', function (Blueprint $table) {
            $table->string('points_mode', 12)->default('none')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('program_entries', function (Blueprint $table) {
            $table->dropColumn('points_mode');
        });
    }
};
