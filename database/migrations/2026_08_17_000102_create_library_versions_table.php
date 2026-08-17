<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A named snapshot of an activity library (categories + activities) stored
        // as JSON in the same shape as the library's file export.
        Schema::create('library_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_library_id')->constrained('activity_libraries')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->longText('payload');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_versions');
    }
};
