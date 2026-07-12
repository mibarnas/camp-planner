<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->string('share_token', 32)->nullable()->unique()->after('id');
        });

        // Give existing activities a share token.
        foreach (DB::table('activities')->whereNull('share_token')->pluck('id') as $id) {
            DB::table('activities')->where('id', $id)->update(['share_token' => Str::random(24)]);
        }
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn('share_token');
        });
    }
};
