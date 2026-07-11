<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_libraries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('activity_library_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_library_id')->constrained('activity_libraries')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->default('member'); // owner | member
            $table->timestamps();

            $table->unique(['activity_library_id', 'user_id']);
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->foreignId('activity_library_id')->nullable()->after('id')
                ->constrained('activity_libraries')->cascadeOnDelete();
        });

        Schema::table('camps', function (Blueprint $table) {
            $table->foreignId('activity_library_id')->nullable()->after('owner_id')
                ->constrained('activity_libraries')->nullOnDelete();
        });

        // Backfill: the activity pool used to be global. Move everything into one
        // shared library owned by the earliest user, link all camps to it, and add
        // every camp member so nobody loses access.
        $firstUserId = DB::table('users')->orderBy('id')->value('id');

        if ($firstUserId !== null) {
            $libraryId = DB::table('activity_libraries')->insertGetId([
                'owner_id' => $firstUserId,
                'name' => 'Knižnica aktivít',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('activities')->update(['activity_library_id' => $libraryId]);
            DB::table('camps')->update(['activity_library_id' => $libraryId]);

            $memberIds = DB::table('camp_user')->distinct()->pluck('user_id')
                ->push($firstUserId)->unique();

            foreach ($memberIds as $userId) {
                DB::table('activity_library_user')->insert([
                    'activity_library_id' => $libraryId,
                    'user_id' => $userId,
                    'role' => $userId === $firstUserId ? 'owner' : 'member',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('camps', function (Blueprint $table) {
            $table->dropConstrainedForeignId('activity_library_id');
        });
        Schema::table('activities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('activity_library_id');
        });
        Schema::dropIfExists('activity_library_user');
        Schema::dropIfExists('activity_libraries');
    }
};
