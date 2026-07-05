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
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('territory_id')->constrained('users')->nullOnDelete();
        });

        Schema::table('demand_creators', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('territory_id')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
        });

        Schema::table('demand_creators', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
        });
    }
};
