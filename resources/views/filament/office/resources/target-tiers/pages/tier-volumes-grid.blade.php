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
        <div class="fi-section overflow-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <table class="w-full border-collapse text-sm">
                <thead>
                    <tr>
                        <th class="sticky left-0 top-0 z-20 min-w-[12rem] border-b border-r border-gray-200 bg-gray-50 p-3 text-left font-semibold text-gray-950 dark:border-white/10 dark:bg-gray-800 dark:text-white">
                            Product
                        </th>
                        @foreach ($tiers as $tier)
                            <th class="sticky top-0 z-10 min-w-[10rem] border-b border-gray-200 bg-gray-50 p-3 text-left font-semibold text-gray-950 dark:border-white/10 dark:bg-gray-800 dark:text-white">
                                {{ $tier->name }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $product)
                        <tr>
                            <th class="sticky left-0 z-10 border-b border-r border-gray-200 bg-gray-50 p-3 text-left font-medium text-gray-950 dark:border-white/10 dark:bg-gray-800 dark:text-white">
                                {{ $product->name }}
                            </th>
                            @foreach ($tiers as $tier)
                                <td class="border-b border-gray-200 p-2 dark:border-white/10">
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        inputmode="decimal"
                                        wire:model="volumes.{{ $product->id }}.{{ $tier->id }}"
                                        class="block w-full rounded-lg border-gray-300 py-1.5 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    />
                                    @error("volumes.{$product->id}.{$tier->id}")
                                        <p class="mt-1 text-xs text-danger-600 dark:text-danger-400">{{ $message }}</p>
                                    @enderror
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-filament-panels::page>
