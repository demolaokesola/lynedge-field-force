<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('position_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            // Denormalised at write time — intentional; preserves reorg history.
            $table->foreignId('territory_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('adjusted_by_user_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->date('adjustment_date');
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('territory_id');
            $table->index(['position_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
