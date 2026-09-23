<?php

namespace App\Filament\Pages;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;

class BusinessAnalytics extends Page
{
    protected string $view = 'filament.pages.business-analytics';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::ChartBar;

    protected static ?int $navigationSort = 1;

    public int $days = 30;

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function report(): array
    {
        $days = in_array($this->days, [7, 30, 90], true) ? $this->days : 30;
        $today = CarbonImmutable::now('Asia/Manila')->startOfDay();
        $start = $today->subDays($days - 1);
        $from = $start->setTimezone(config('app.timezone'));
        $until = $today->addDay()->setTimezone(config('app.timezone'));
        $daily = [];

        for ($date = $start; $date <= $today; $date = $date->addDay()) {
            $daily[$date->toDateString()] = ['revenue' => 0, 'cost' => 0, 'transactions' => 0, 'units' => 0, 'missing' => 0];
        }

        $sales = Sale::query()->where('created_at', '>=', $from)->where('created_at', '<', $until)
            ->select(['id', 'created_at', 'total_amount', 'payment_method'])
            ->withSum('items as cost_total', DB::raw('quantity * cost_price'))
            ->withSum('items as units', 'quantity')
            ->withCount(['items', 'items as missing_cost_items' => fn (Builder $query): Builder => $query->whereNull('cost_price')]);
        $payments = ['cash' => 0, 'card' => 0, 'ewallet' => 0];

        foreach ($sales->cursor() as $sale) {
            $date = $sale->created_at->setTimezone('Asia/Manila')->toDateString();
            $revenue = (int) round((float) $sale->total_amount * 100);
            $daily[$date]['revenue'] += $revenue;
            $daily[$date]['cost'] += (int) round((float) $sale->cost_total * 100);
            $daily[$date]['transactions']++;
            $daily[$date]['units'] += (int) $sale->units;
            $daily[$date]['missing'] += (int) $sale->missing_cost_items + ($sale->items_count === 0 ? 1 : 0);
            $payments[$sale->payment_method] = ($payments[$sale->payment_method] ?? 0) + $revenue;
        }

        $totals = [];
        foreach (['revenue', 'cost', 'transactions', 'units', 'missing'] as $field) {
            $totals[$field] = array_sum(array_column($daily, $field));
        }

        $topProducts = SaleItem::query()
            ->whereIn('sale_id', Sale::query()->select('id')->where('created_at', '>=', $from)->where('created_at', '<', $until))
            ->select('product_id')->selectRaw('SUM(quantity) as units, SUM(subtotal) as revenue')
            ->groupBy('product_id')->orderByDesc('revenue')->limit(5)->with('product:id,name')->get();

        return [
            'daily' => array_reverse($daily, true), 'today' => $daily[$today->toDateString()],
            'totals' => $totals, 'payments' => $payments, 'topProducts' => $topProducts,
            'lowStock' => Product::query()->where('is_active', true)->where('stock_quantity', '<=', 5)
                ->orderBy('stock_quantity')->limit(10)->get(['id', 'name', 'stock_quantity', 'unit']),
            'start' => $start->toDateString(), 'end' => $today->toDateString(),
        ];
    }
}
