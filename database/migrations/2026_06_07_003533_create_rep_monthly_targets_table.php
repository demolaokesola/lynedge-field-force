<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rep_monthly_targets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cycle_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->date('year_month');
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('target_qty', 14, 2);
            $table->timestamps();
        });

        DB::statement('CREATE UNIQUE INDEX rep_monthly_targets_unique ON rep_monthly_targets (cycle_id, user_id, year_month, product_id)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rep_monthly_targets');
    }
};
