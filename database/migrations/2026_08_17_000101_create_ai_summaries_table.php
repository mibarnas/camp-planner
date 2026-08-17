<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The latest AI summary per scope: one row per day, plus one with a null
        // camp_day_id for the whole-camp summary.
        Schema::create('ai_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camp_id')->constrained('camps')->cascadeOnDelete();
            $table->foreignId('camp_day_id')->nullable()->constrained('camp_days')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('summary');
            $table->timestamps();

            $table->unique(['camp_id', 'camp_day_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_summaries');
    }
};
