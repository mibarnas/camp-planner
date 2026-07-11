<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('program_entries', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('materials');
        });
    }

    public function down(): void
    {
        Schema::table('program_entries', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};
