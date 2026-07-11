<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('camps', function (Blueprint $table) {
            $table->string('icon', 40)->default('tent')->after('name');
            $table->string('color', 20)->default('emerald')->after('icon');
            $table->string('location')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('camps', function (Blueprint $table) {
            $table->dropColumn(['icon', 'color', 'location']);
        });
    }
};
