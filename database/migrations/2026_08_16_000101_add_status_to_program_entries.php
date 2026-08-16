<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('program_entries', function (Blueprint $table) {
            $table->string('status', 10)->default('none')->after('notes');
        });

        DB::table('program_entries')->where('is_done', true)->update(['status' => 'done']);

        // Separate closure: SQLite rebuilds the table to drop a column.
        Schema::table('program_entries', function (Blueprint $table) {
            $table->dropColumn('is_done');
        });
    }

    public function down(): void
    {
        Schema::table('program_entries', function (Blueprint $table) {
            $table->boolean('is_done')->default(false)->after('notes');
        });

        DB::table('program_entries')->where('status', 'done')->update(['is_done' => true]);

        Schema::table('program_entries', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
