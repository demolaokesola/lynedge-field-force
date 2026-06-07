<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('target_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cycle_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('position_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('target_tier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('basis');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('reason');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['cycle_id', 'user_id', 'effective_from']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('target_assignments');
    }
};
