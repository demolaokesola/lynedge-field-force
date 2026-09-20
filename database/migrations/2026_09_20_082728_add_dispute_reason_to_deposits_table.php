<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An accountant must explain why a deposit is disputed; the reason is shown to the
 * rep on the deposit's read-only view page.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->text('dispute_reason')->nullable()->after('statement_reference');
        });
    }

    public function down(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->dropColumn('dispute_reason');
        });
    }
};
