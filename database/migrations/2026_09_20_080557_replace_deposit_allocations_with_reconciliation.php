<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reconciliation becomes one-to-one: an accountant matches a deposit to a single
 * bank-statement amount. Partial allocation against invoices is removed, so the
 * deposit_allocations table goes and the statement match is recorded on the deposit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->timestamp('reconciled_at')->nullable();
            $table->foreignId('reconciled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('statement_date')->nullable();
            $table->string('statement_reference')->nullable();
        });

        DB::table('deposits')
            ->where('status', 'partially_reconciled')
            ->update(['status' => 'unreconciled']);

        DB::table('deposits')
            ->where('status', 'reconciled')
            ->update(['reconciled_at' => DB::raw('updated_at')]);

        Schema::dropIfExists('deposit_allocations');
    }

    public function down(): void
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

        Schema::table('deposits', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reconciled_by_user_id');
            $table->dropColumn(['reconciled_at', 'statement_date', 'statement_reference']);
        });
    }
};
