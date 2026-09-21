<?php

namespace App\Services;

use App\Models\Position;
use App\Models\StockCount;
use App\Models\StockDispatch;
use App\Models\User;
use App\Notifications\StockCountSubmitted;
use App\Notifications\StockDispatchAccepted;
use App\Notifications\StockDispatchSent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * Who hears about stock document transitions. Kept out of the lifecycle services so
 * recipient rules live in one place: the position's current occupant for anything
 * that needs a rep's action, every platform_admin for anything that needs Operations'.
 */
class StockNotifier
{
    public function dispatchSent(StockDispatch $dispatch): void
    {
        Notification::send($this->occupantsOf($dispatch->position), new StockDispatchSent($dispatch));
    }

    public function dispatchAccepted(StockDispatch $dispatch): void
    {
        Notification::send($this->operations(), new StockDispatchAccepted($dispatch));
    }

    public function countSubmitted(StockCount $count): void
    {
        Notification::send($this->operations(), new StockCountSubmitted($count));
    }

    /**
     * @return Collection<int, User>
     */
    private function occupantsOf(Position $position): Collection
    {
        return User::query()
            ->whereHas('positionAssignments', fn (Builder $q): Builder => $q
                ->where('position_id', $position->id)
                ->whereNull('effective_to'))
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    private function operations(): Collection
    {
        return User::role('platform_admin')->get();
    }
}
