<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('camp_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camp_id')->constrained('camps')->cascadeOnDelete();
            $table->date('date');
            $table->boolean('is_trip')->default(false);
            $table->string('trip_name')->nullable();
            $table->string('name_days')->nullable();  // meniny
            $table->string('birthdays')->nullable();   // narodeniny
            $table->text('materials')->nullable();
            $table->text('notes')->nullable();          // retrospective / review
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['camp_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('camp_days');
    }
};
