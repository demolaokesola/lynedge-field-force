<?php

use App\Filament\Shared\Resources\Deposits\Pages\ListDeposits;
use App\Models\Deposit;
use App\Models\User;
use Filament\Facades\Filament;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('office'));

    $this->actingAs(User::factory()->withRole('accountant')->create());
});

test('amount renders with thousand separators and no currency symbol', function (): void {
    $deposit = Deposit::factory()->create(['amount' => '1234567.50']);

    livewire(ListDeposits::class)
        ->assertTableColumnFormattedStateSet('amount', '1,234,567.50', $deposit);
});
