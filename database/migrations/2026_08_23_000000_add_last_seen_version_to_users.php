<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // The app version whose changelog this user has already seen. Null
            // means "predates the changelog", so they get the current entry once.
            $table->string('last_seen_version', 20)->nullable()->after('gemini_api_key');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('last_seen_version');
        });
    }
};
