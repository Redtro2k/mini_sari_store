<x-filament-panels::page>
    @if (\App\Filament\Resources\Sales\SaleResource::canCreate())
        <h2 class="text-xl font-semibold">Quick sale</h2>
        @livewire(\App\Filament\Resources\Sales\Pages\DashboardSale::class)
    @endif
    {{ $this->content }}
</x-filament-panels::page>
