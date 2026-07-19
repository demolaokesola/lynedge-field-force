<?php

namespace App\Filament\Office\Resources\TargetTiers\Pages;

use App\Enums\TargetBasis;
use App\Filament\Office\Resources\TargetTiers\TargetTierResource;
use App\Jobs\RebuildRepMonthlyTargetsJob;
use App\Models\Product;
use App\Models\TargetAssignment;
use App\Models\TargetTier;
use App\Models\TargetTierLine;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @property-read Schema $form
 */
class TierVolumesGrid extends Page
{
    protected static string $resource = TargetTierResource::class;

    protected string $view = 'filament.office.resources.target-tiers.pages.tier-volumes-grid';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(['volumes' => $this->buildVolumesState()]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save')
                ->action('save'),
        ];
    }

    public function form(Schema $schema): Schema
    {
        $tiers = $this->tiers();

        return $schema
            ->components([
                Repeater::make('volumes')
                    ->hiddenLabel()
                    ->table([
                        TableColumn::make('Product')->width('220px'),
                        ...$tiers->map(
                            fn (TargetTier $tier): TableColumn => TableColumn::make($tier->name)->width('140px'),
                        )->all(),
                    ])
                    ->schema([
                        Hidden::make('product_id'),
                        TextInput::make('product_name')
                            ->hiddenLabel()
                            ->disabled()
                            ->dehydrated(false),
                        ...$tiers->map(
                            fn (TargetTier $tier): TextInput => TextInput::make("tier_{$tier->id}")
                                ->hiddenLabel()
                                ->numeric()
                                ->minValue(0),
                        )->all(),
                    ])
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false)
                    ->compact()
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    /**
     * @return Collection<int, Product>
     */
    public function products(): Collection
    {
        return Product::query()->where('active', true)->orderBy('name')->get();
    }

    /**
     * @return Collection<int, TargetTier>
     */
    public function tiers(): Collection
    {
        return TargetTier::query()->where('active', true)->orderBy('name')->get();
    }

    public function save(): void
    {
        $products = $this->products();
        $tiers = $this->tiers();

        $volumes = $this->form->getState()['volumes'] ?? [];

        $changedTierIds = $this->persistVolumes($products, $tiers, $volumes);

        if ($changedTierIds !== []) {
            $this->rebuildAffectedTargets($changedTierIds);
        }

        $this->form->fill(['volumes' => $this->buildVolumesState()]);

        Notification::make()
            ->title('Volumes saved')
            ->success()
            ->send();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildVolumesState(): array
    {
        $products = $this->products();
        $tiers = $this->tiers();

        $lines = TargetTierLine::query()
            ->whereIn('target_tier_id', $tiers->pluck('id'))
            ->get();

        return $products->mapWithKeys(function (Product $product) use ($tiers, $lines): array {
            $row = ['product_id' => $product->id, 'product_name' => $product->name];

            foreach ($tiers as $tier) {
                $line = $lines->first(
                    fn (TargetTierLine $line): bool => $line->target_tier_id === $tier->id
                        && $line->product_id === $product->id,
                );

                $row["tier_{$tier->id}"] = $line?->annual_volume;
            }

            return [$product->id => $row];
        })->all();
    }

    /**
     * @param  Collection<int, Product>  $products
     * @param  Collection<int, TargetTier>  $tiers
     * @param  array<int, array<string, mixed>>  $volumes
     * @return array<int, int> changed target_tier_id values
     */
    private function persistVolumes(Collection $products, Collection $tiers, array $volumes): array
    {
        $changedTierIds = [];

        $volumesByProduct = collect($volumes)->keyBy('product_id');

        DB::transaction(function () use ($products, $tiers, $volumesByProduct, &$changedTierIds): void {
            $existing = TargetTierLine::query()
                ->whereIn('target_tier_id', $tiers->pluck('id'))
                ->get()
                ->keyBy(fn (TargetTierLine $line): string => "{$line->target_tier_id}.{$line->product_id}");

            foreach ($products as $product) {
                $row = $volumesByProduct->get($product->id, []);

                foreach ($tiers as $tier) {
                    $current = $existing->get("{$tier->id}.{$product->id}");
                    $submitted = $row["tier_{$tier->id}"] ?? null;
                    $submitted = $submitted === '' ? null : $submitted;

                    if ($submitted === null) {
                        if ($current !== null) {
                            $current->delete();
                            $changedTierIds[$tier->id] = $tier->id;
                        }

                        continue;
                    }

                    if ($current !== null && bccomp((string) $current->annual_volume, (string) $submitted, 2) === 0) {
                        continue;
                    }

                    TargetTierLine::updateOrCreate(
                        ['target_tier_id' => $tier->id, 'product_id' => $product->id],
                        ['annual_volume' => $submitted],
                    );

                    $changedTierIds[$tier->id] = $tier->id;
                }
            }
        });

        return array_values($changedTierIds);
    }

    /**
     * @param  array<int, int>  $changedTierIds
     */
    private function rebuildAffectedTargets(array $changedTierIds): void
    {
        TargetAssignment::query()
            ->where('basis', TargetBasis::Tier)
            ->whereIn('target_tier_id', $changedTierIds)
            ->select('user_id', 'cycle_id')
            ->distinct()
            ->get()
            ->each(fn (TargetAssignment $assignment) => RebuildRepMonthlyTargetsJob::dispatch(
                $assignment->user_id,
                $assignment->cycle_id,
            ));
    }
}
