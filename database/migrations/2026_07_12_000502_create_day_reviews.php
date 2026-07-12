<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One review per leader per day.
        Schema::create('day_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camp_day_id')->constrained('camp_days')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('camp_rating')->nullable(); // last day only, 1..5
            $table->text('camp_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['camp_day_id', 'user_id']);
        });

        // A star rating for each activity of the reviewed day.
        Schema::create('activity_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('day_review_id')->constrained('day_reviews')->cascadeOnDelete();
            $table->foreignId('program_entry_id')->constrained('program_entries')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating'); // 1..5
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->unique(['day_review_id', 'program_entry_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_ratings');
        Schema::dropIfExists('day_reviews');
    }
};
