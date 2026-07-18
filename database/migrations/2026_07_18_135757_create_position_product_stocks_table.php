<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('position_product_stocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('position_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            // May go negative — for now distribution is not blocked by insufficient stock.
            $table->decimal('quantity', 14, 2)->default(0);
            $table->timestamps();

            $table->unique(['position_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('position_product_stocks');
    }
};
