<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camp_day_id')->constrained('camp_days')->cascadeOnDelete();
            $table->foreignId('time_slot_id')->constrained('time_slots')->cascadeOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained('activities')->nullOnDelete();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('responsible')->nullable(); // Zodpovedny
            $table->text('materials')->nullable();
            $table->boolean('is_done')->default(false);
            $table->timestamps();

            $table->unique(['camp_day_id', 'time_slot_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_entries');
    }
};
