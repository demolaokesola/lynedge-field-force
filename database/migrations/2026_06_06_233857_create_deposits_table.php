<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('territory_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 18, 2);
            $table->date('deposit_date');
            $table->string('reference')->nullable();
            $table->string('bank')->nullable();
            $table->string('channel')->nullable();
            $table->string('status')->default('unreconciled');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposits');
    }
};
