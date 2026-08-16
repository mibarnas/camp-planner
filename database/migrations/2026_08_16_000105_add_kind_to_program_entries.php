<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 'detailed' = a programme activity (library-backed, has a scenario,
        // materials, a responsible leader, gets rated). 'simple' = a plain block
        // on the timeline such as Raňajky or Presun do Tatier.
        Schema::table('program_entries', function (Blueprint $table) {
            $table->string('kind', 10)->default('detailed')->after('activity_id');
        });
    }

    public function down(): void
    {
        Schema::table('program_entries', function (Blueprint $table) {
            $table->dropColumn('kind');
        });
    }
};
