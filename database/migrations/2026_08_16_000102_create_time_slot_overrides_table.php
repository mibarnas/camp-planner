<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A day's deviation from the camp-wide daily skeleton: the block moved,
        // resized or dropped for that day only. No row = the template applies.
        Schema::create('time_slot_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camp_day_id')->constrained('camp_days')->cascadeOnDelete();
            $table->foreignId('time_slot_id')->constrained('time_slots')->cascadeOnDelete();
            $table->time('start_time')->nullable(); // null = keep the template's
            $table->time('end_time')->nullable();
            // Not `hidden`: that name collides with Eloquent's own Model::$hidden.
            $table->boolean('is_hidden')->default(false);
            $table->timestamps();

            $table->unique(['camp_day_id', 'time_slot_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_slot_overrides');
    }
};
