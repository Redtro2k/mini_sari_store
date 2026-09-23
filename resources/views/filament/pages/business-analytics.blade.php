<x-filament-panels::page>
    @php
        $report = $this->report;
        $total = $report['totals'];
        $today = $report['today'];
        $money = fn ($cents) => '₱'.number_format($cents / 100, 2);
    @endphp
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm text-gray-600 dark:text-gray-300">{{ $report['start'] }} to {{ $report['end'] }} · Philippine time (Asia/Manila)</p>
            <p class="text-sm text-gray-600 dark:text-gray-300">Revenue is sales after change. Gross profit is revenue minus product cost, before expenses, fees and taxes.</p>
        </div>
        <div class="flex items-end gap-3">
            <label class="text-sm font-medium">Period
                <select wire:model.live="days" class="block rounded-lg border-gray-300 bg-white text-gray-900 dark:bg-gray-900 dark:text-white">
                    <option value="7">Last 7 days</option><option value="30">Last 30 days</option><option value="90">Last 90 days</option>
                </select>
            </label>
            <x-filament::button wire:click="$refresh" wire:loading.attr="disabled">Refresh</x-filament::button>
        </div>
    </div>
    @if ($total['missing'])
        <p role="status" class="rounded-xl bg-amber-50 p-4 text-sm text-amber-900 dark:bg-amber-950 dark:text-amber-100">Some sales have no historical cost recorded. Profit and cost totals are unavailable for affected dates and the selected period. New sales save their cost automatically.</p>
    @endif
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach (["Today's revenue" => $money($today['revenue']), "Today's gross profit" => $today['missing'] ? 'Unavailable' : $money($today['revenue'] - $today['cost']), 'Period revenue' => $money($total['revenue']), 'Period gross profit' => $total['missing'] ? 'Unavailable' : $money($total['revenue'] - $total['cost'])] as $label => $value)
            <x-filament::section><p class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</p><p class="mt-2 text-2xl font-semibold">{{ $value }}</p></x-filament::section>
        @endforeach
    </div>
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach (['Transactions' => $total['transactions'], 'Units sold' => $total['units'], 'Average sale' => $money($total['transactions'] ? $total['revenue'] / $total['transactions'] : 0), 'Gross margin' => $total['missing'] ? 'Unavailable' : ($total['revenue'] ? number_format(($total['revenue'] - $total['cost']) / $total['revenue'] * 100, 1).'%' : '—')] as $label => $value)
            <x-filament::section><p class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</p><p class="mt-2 text-xl font-semibold">{{ $value }}</p></x-filament::section>
        @endforeach
    </div>
    @livewire(\App\Filament\Widgets\DailySalesChart::class, ['daily' => $report['daily']], key('daily-sales-'.md5(json_encode($report['daily']))))
    <x-filament::section heading="Daily sales and profit">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead><tr class="border-b border-gray-200 dark:border-gray-700"><th class="p-3">Date</th><th class="p-3">Sales revenue</th><th class="p-3">Product cost</th><th class="p-3">Gross profit</th><th class="p-3">Transactions</th></tr></thead>
                <tbody>
                    @foreach ($report['daily'] as $date => $row)
                        <tr class="border-b border-gray-100 dark:border-gray-800"><td class="whitespace-nowrap p-3">{{ $date }}</td><td class="p-3">{{ $money($row['revenue']) }}</td><td class="p-3">{{ $row['missing'] ? 'Unavailable' : $money($row['cost']) }}</td><td class="p-3">{{ $row['missing'] ? 'Unavailable' : $money($row['revenue'] - $row['cost']) }}</td><td class="p-3">{{ $row['transactions'] }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>
    <div class="grid gap-4 lg:grid-cols-2">
        <x-filament::section heading="Top 5 products by sales revenue">
            <ul class="space-y-3">
                @forelse ($report['topProducts'] as $item)
                    <li class="flex justify-between gap-4"><span>{{ $item->product?->name ?? 'Unavailable product' }} <small>({{ $item->units }} sold)</small></span><span>{{ $money((float) $item->revenue * 100) }}</span></li>
                @empty
                    <li>No sales in this period.</li>
                @endforelse
            </ul>
        </x-filament::section>
        <x-filament::section heading="Sales by payment method">
            <ul class="space-y-3">@foreach ($report['payments'] as $method => $amount)<li class="flex justify-between"><span>{{ ucfirst($method) }}</span><span>{{ $money($amount) }}</span></li>@endforeach</ul>
        </x-filament::section>
        <x-filament::section heading="Low stock now (5 or fewer units)">
            <ul class="space-y-3">
                @forelse ($report['lowStock'] as $product)
                    <li class="flex justify-between"><span>{{ $product->name }}</span><span>{{ $product->stock_quantity }} {{ $product->unit->value }}</span></li>
                @empty
                    <li>No low-stock products.</li>
                @endforelse
            </ul>
        </x-filament::section>
    </div>
</x-filament-panels::page>
