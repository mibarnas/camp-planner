<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_libraries', function (Blueprint $table) {
            // null = no shareable link; a token = anyone with the link can join.
            $table->string('share_token', 64)->nullable()->unique()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('activity_libraries', function (Blueprint $table) {
            $table->dropColumn('share_token');
        });
    }
};
