<x-filament-panels::page>
    @php
        $products = $this->products();
        $tiers = $this->tiers();
    @endphp

    @if ($products->isEmpty() || $tiers->isEmpty())
        <div class="fi-section rounded-xl bg-white p-6 text-sm text-gray-500 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:text-gray-400 dark:ring-white/10">
            @if ($tiers->isEmpty())
                No active tiers exist yet. Create a tier before assigning annual volumes.
            @else
                No active products exist yet.
            @endif
        </div>
    @else
        {{ $this->form }}
    @endif
</x-filament-panels::page>
