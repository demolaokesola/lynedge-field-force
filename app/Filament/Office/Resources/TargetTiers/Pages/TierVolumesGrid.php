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
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TierVolumesGrid extends Page
{
    protected static string $resource = TargetTierResource::class;

    protected string $view = 'filament.office.resources.target-tiers.pages.tier-volumes-grid';

    /**
     * @var array<int, array<int, string|null>>
     */
    public array $volumes = [];

    public function mount(): void
    {
        $this->fillVolumes();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save')
                ->action('save'),
        ];
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

        $rules = [];

        foreach ($products as $product) {
            foreach ($tiers as $tier) {
                $rules["volumes.{$product->id}.{$tier->id}"] = ['nullable', 'numeric', 'min:0'];
            }
        }

        $this->validate($rules);

        $changedTierIds = $this->persistVolumes($products, $tiers);

        if ($changedTierIds !== []) {
            $this->rebuildAffectedTargets($changedTierIds);
        }

        $this->fillVolumes();

        Notification::make()
            ->title('Volumes saved')
            ->success()
            ->send();
    }

    private function fillVolumes(): void
    {
        $products = $this->products();
        $tiers = $this->tiers();

        $lines = TargetTierLine::query()
            ->whereIn('target_tier_id', $tiers->pluck('id'))
            ->get();

        $this->volumes = $products->mapWithKeys(function (Product $product) use ($tiers, $lines): array {
            return [
                $product->id => $tiers->mapWithKeys(function (TargetTier $tier) use ($product, $lines): array {
                    $line = $lines->first(
                        fn (TargetTierLine $line): bool => $line->target_tier_id === $tier->id
                            && $line->product_id === $product->id,
                    );

                    return [$tier->id => $line?->annual_volume];
                })->all(),
            ];
        })->all();
    }

    /**
     * @param  Collection<int, Product>  $products
     * @param  Collection<int, TargetTier>  $tiers
     * @return array<int, int> changed target_tier_id values
     */
    private function persistVolumes(Collection $products, Collection $tiers): array
    {
        $changedTierIds = [];

        DB::transaction(function () use ($products, $tiers, &$changedTierIds): void {
            $existing = TargetTierLine::query()
                ->whereIn('target_tier_id', $tiers->pluck('id'))
                ->get()
                ->keyBy(fn (TargetTierLine $line): string => "{$line->target_tier_id}.{$line->product_id}");

            foreach ($products as $product) {
                foreach ($tiers as $tier) {
                    $current = $existing->get("{$tier->id}.{$product->id}");
                    $submitted = $this->volumes[$product->id][$tier->id] ?? null;
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
