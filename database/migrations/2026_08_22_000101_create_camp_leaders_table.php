<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A leader of a camp, whether or not they have an account. Leaders without
     * a user can still be referenced (as the person responsible for an activity,
     * or as a member of a group); they gain a user_id if they later join.
     */
    public function up(): void
    {
        Schema::create('camp_leaders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camp_id')->constrained('camps')->cascadeOnDelete();
            // Null = a named leader without an account. MySQL allows repeated
            // NULLs under a unique index, so one row per member still holds.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('color', 20)->nullable();
            $table->timestamps();
            $table->unique(['camp_id', 'user_id']);
        });

        // Every existing membership becomes a leader row.
        $now = now();
        $names = DB::table('users')->pluck('name', 'id');

        foreach (DB::table('camp_user')->orderBy('id')->get() as $row) {
            DB::table('camp_leaders')->insert([
                'camp_id' => $row->camp_id,
                'user_id' => $row->user_id,
                'name' => $names[$row->user_id] ?? 'Vedúci',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('camp_leaders');
    }
};
