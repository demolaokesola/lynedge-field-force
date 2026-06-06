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
        Schema::create('calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('position_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            // Denormalised from the position at write time — intentional (reorg history).
            $table->foreignId('territory_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('demand_creator_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('call_type');
            $table->timestamp('called_at');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'called_at']);
            $table->index('territory_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calls');
    }
};
