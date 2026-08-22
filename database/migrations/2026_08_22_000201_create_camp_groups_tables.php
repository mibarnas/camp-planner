<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Groups a camp is split into (oddiely, program team, photographers…), the
     * camp-defined types they fall under, and which leaders belong to them.
     *
     * The table is `camp_groups`, not `groups`: GROUPS is reserved in MySQL 8,
     * and the name matches camp_days / camp_leaders.
     */
    public function up(): void
    {
        Schema::create('group_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camp_id')->constrained('camps')->cascadeOnDelete();
            $table->string('name');
            $table->string('color', 20)->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('camp_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camp_id')->constrained('camps')->cascadeOnDelete();
            $table->foreignId('group_type_id')->nullable()->constrained('group_types')->nullOnDelete();
            $table->string('name');
            // Only competing groups appear on the leaderboard and get points prompts.
            $table->boolean('competes')->default(true);
            $table->string('color', 20)->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('camp_group_leader', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camp_group_id')->constrained('camp_groups')->cascadeOnDelete();
            $table->foreignId('camp_leader_id')->constrained('camp_leaders')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['camp_group_id', 'camp_leader_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('camp_group_leader');
        Schema::dropIfExists('camp_groups');
        Schema::dropIfExists('group_types');
    }
};
