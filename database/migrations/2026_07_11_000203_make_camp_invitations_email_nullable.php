<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * An invitation with email = null is the camp's shareable link:
     * unlimited use, never marked accepted.
     */
    public function up(): void
    {
        Schema::table('camp_invitations', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('camp_invitations', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
        });
    }
};
