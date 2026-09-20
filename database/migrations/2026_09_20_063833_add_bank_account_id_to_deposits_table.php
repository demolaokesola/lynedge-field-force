<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Replaces the free-text deposits.bank column with a required FK to the company
 * bank account the customer paid into. No backfill: existing rows were cleared.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deposits', function (Blueprint $table): void {
            $table->dropColumn('bank');
            $table->foreignId('bank_account_id')
                ->after('reference')
                ->constrained()
                ->restrictOnDelete();
            $table->index('bank_account_id');
        });
    }

    public function down(): void
    {
        Schema::table('deposits', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('bank_account_id');
            $table->string('bank')->nullable()->after('reference');
        });
    }
};
