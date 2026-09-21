<?php

namespace App\Filament\Office\Pages;

use App\Services\StockSettings as StockSettingsService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Operations' two stock switches, backed by {@see StockSettingsService}: the go-live
 * switch that makes posted distributions draw down stock, and whether a distribution
 * that would take a balance below zero is refused or merely warned about.
 *
 * @property-read Schema $form
 */
class StockSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Stock';

    protected static ?int $navigationSort = 99;

    protected static ?string $title = 'Stock Settings';

    protected string $view = 'filament.office.pages.stock-settings';

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['superuser', 'platform_admin']) ?? false;
    }

    public function mount(): void
    {
        $settings = app(StockSettingsService::class);

        $this->form->fill([
            'consumption_enabled' => $settings->consumptionEnabled(),
            'block_negative_stock' => $settings->blockNegativeStock(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Section::make('Stock consumption')
                        ->description('Enter every position\'s opening balance before switching this on — distributions posted while it is off never touch the ledger.')
                        ->schema([
                            Toggle::make('consumption_enabled')
                                ->label('Posted distributions reduce stock')
                                ->helperText('The go-live switch. When on, posting a distribution draws each line down from the position\'s balance.'),
                            Toggle::make('block_negative_stock')
                                ->label('Refuse distributions that exceed stock on hand')
                                ->helperText('When off, the rep is warned and the balance is allowed to go negative. When on, the distribution stays in draft.'),
                        ]),
                ])
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label('Save')
                                ->submit('save')
                                ->keyBindings(['mod+s']),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $settings = app(StockSettingsService::class);
        $settings->setConsumptionEnabled((bool) ($data['consumption_enabled'] ?? false));
        $settings->setBlockNegativeStock((bool) ($data['block_negative_stock'] ?? false));

        Notification::make()
            ->success()
            ->title('Stock settings saved')
            ->send();
    }
}
