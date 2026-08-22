<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extra questions a camp asks its leaders on top of the star ratings.
     * 'day' questions come up in every day's review; 'camp' questions are asked
     * once, as part of the last day's review — which is why one answers table,
     * hanging off day_reviews, covers both.
     */
    public function up(): void
    {
        Schema::create('feedback_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camp_id')->constrained('camps')->cascadeOnDelete();
            $table->string('scope', 10); // day | camp
            $table->string('text', 500);
            $table->unsignedSmallInteger('position')->default(0);
            // Archived questions leave the review wizard but keep their answers.
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });

        Schema::create('feedback_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('day_review_id')->constrained('day_reviews')->cascadeOnDelete();
            $table->foreignId('feedback_question_id')->constrained('feedback_questions')->cascadeOnDelete();
            $table->text('answer');
            $table->timestamps();
            $table->unique(['day_review_id', 'feedback_question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_answers');
        Schema::dropIfExists('feedback_questions');
    }
};
