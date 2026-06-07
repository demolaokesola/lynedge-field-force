<?php

namespace App\Jobs;

use App\Models\Cycle;
use App\Models\User;
use App\Services\TargetMaterializer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RebuildRepMonthlyTargetsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $userId,
        public readonly int $cycleId,
    ) {}

    public function handle(TargetMaterializer $materializer): void
    {
        $materializer->rebuild(
            User::findOrFail($this->userId),
            Cycle::findOrFail($this->cycleId),
        );
    }
}
