<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->date('effective_date')->nullable()->after('type');
        });

        // Existing rows only know when they were written; that is the best effective
        // date available for them.
        DB::statement('UPDATE stock_movements SET effective_date = created_at::date WHERE effective_date IS NULL');

        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->date('effective_date')->nullable(false)->change();
            $table->index(['position_id', 'effective_date']);
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->dropIndex(['position_id', 'effective_date']);
            $table->dropColumn('effective_date');
        });
    }
};
