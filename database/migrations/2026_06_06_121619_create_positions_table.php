<?php

use App\Enums\PositionStatus;
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
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('territory_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('code')->unique();
            $table->string('label');
            $table->boolean('enforce_team_uniqueness')->default(false);
            $table->string('status')->default(PositionStatus::Active->value);
            $table->timestamps();
        });

        // One active position per (territory, team) in STRICT territories. Liberal
        // territories carry enforce_team_uniqueness = false, so they fall outside
        // the partial index and may share a team across positions.
        DB::statement("
            CREATE UNIQUE INDEX positions_strict_team_unique
            ON positions (territory_id, team_id)
            WHERE enforce_team_uniqueness AND status = 'active'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};
