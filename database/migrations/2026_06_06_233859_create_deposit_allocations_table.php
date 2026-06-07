<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deposit_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deposit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('distribution_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 18, 2);
            $table->foreignId('allocated_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('allocated_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposit_allocations');
    }
};
