<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A named snapshot of a camp's whole schedule (slots, entries, per-day
        // slot overrides) stored as JSON so it survives id churn.
        Schema::create('plan_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camp_id')->constrained('camps')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->longText('payload');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_versions');
    }
};
