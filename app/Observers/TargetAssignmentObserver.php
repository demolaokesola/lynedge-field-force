<?php

namespace App\Observers;

use App\Jobs\RebuildRepMonthlyTargetsJob;
use App\Models\TargetAssignment;

class TargetAssignmentObserver
{
    public function created(TargetAssignment $assignment): void
    {
        $this->dispatch($assignment);
    }

    public function updated(TargetAssignment $assignment): void
    {
        $this->dispatch($assignment);
    }

    public function deleted(TargetAssignment $assignment): void
    {
        $this->dispatch($assignment);
    }

    private function dispatch(TargetAssignment $assignment): void
    {
        RebuildRepMonthlyTargetsJob::dispatch($assignment->user_id, $assignment->cycle_id);
    }
}
