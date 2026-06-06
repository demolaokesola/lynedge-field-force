<?php

use App\Enums\AssignmentStatus;
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
        Schema::create('position_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('position_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('status')->default(AssignmentStatus::Active->value);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // At most one OPEN occupancy (effective_to IS NULL) per position.
        DB::statement('
            CREATE UNIQUE INDEX one_open_assignment_per_position
            ON position_assignments (position_id)
            WHERE effective_to IS NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('position_assignments');
    }
};
