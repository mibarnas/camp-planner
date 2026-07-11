<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('category')->default('other'); // game, sport, craft, spiritual, educational, skit, station, other
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('default_duration')->default(60); // minutes
            $table->string('color', 20)->nullable();
            $table->text('materials')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
