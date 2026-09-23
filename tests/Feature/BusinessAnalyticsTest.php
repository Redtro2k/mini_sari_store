<?php

use App\Filament\Pages\BusinessAnalytics;
use App\Filament\Widgets\DailySalesChart;
use App\Models\Sale;
use App\Models\User;
use Carbon\Carbon;
use Database\Factories\ProductFactory;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

it('charts chronological peso values and preserves missing costs and losses', function () {
    $widget = new class extends DailySalesChart
    {
        public function chartData(): array
        {
            return $this->getData();
        }
    };
    $widget->daily = [
        '2026-09-23' => ['revenue' => 1000, 'cost' => 2000, 'missing' => 0],
        '2026-09-21' => ['revenue' => 5000, 'cost' => 2000, 'missing' => 0],
        '2026-09-22' => ['revenue' => 3000, 'cost' => 0, 'missing' => 1],
    ];
    $data = $widget->chartData();
    expect($data['labels'])->toBe(['2026-09-21', '2026-09-22', '2026-09-23'])
        ->and($data['datasets'][0]['data'])->toBe([50.0, 30.0, 10.0])
        ->and($data['datasets'][1]['data'])->toBe([30.0, null, -10.0])
        ->and($data['datasets'][0]['borderColor'])->toStartWith('rgba(')
        ->and($data['datasets'][1]['borderColor'])->not->toBe($data['datasets'][0]['borderColor']);
});

it('embeds the chart and updates its period with the analytics filter', function () {
    $page = Livewire::test(BusinessAnalytics::class)
        ->assertSee('Daily revenue and gross profit');
    expect($page->instance()->report()['daily'])->toHaveCount(30);
    $page->set('days', 7)->assertSee('Daily revenue and gross profit');
    expect($page->instance()->report()['daily'])->toHaveCount(7);
    $page->set('days', 90)->assertSee('Daily revenue and gross profit');
    expect($page->instance()->report()['daily'])->toHaveCount(90);
});

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    Filament::setCurrentPanel(Filament::getPanel('store'));
    $this->travelTo(Carbon::parse('2026-09-22 05:00:00', 'UTC'));
});

it('renders empty analytics with zero totals and no division errors', function () {
    $page = Livewire::test(BusinessAnalytics::class)->assertSuccessful()->assertSee('No sales in this period.');
    expect($page->instance()->report()['totals']['revenue'])->toBe(0);
});

it('groups sales by Philippine date and uses saved cost rather than current product cost', function () {
    $product = ProductFactory::new()->create([
        'name' => 'Test item', 'sku' => 'ANALYTICS-001', 'price' => 100,
        'selling_price' => 20, 'stock_quantity' => 3, 'unit' => 'pcs', 'is_active' => true,
    ]);
    $sale = Sale::factory()->create([
        'reference_number' => 'ANALYTICS-001', 'user_id' => auth()->id(),
        'payment_method' => 'cash', 'total_amount' => 40, 'amount_paid' => 100,
        'change_amount' => 60, 'created_at' => '2026-09-21 16:30:00',
    ]);
    $sale->items()->create(['product_id' => $product->id, 'quantity' => 2, 'price' => 20, 'cost_price' => 10, 'subtotal' => 40]);
    $page = Livewire::test(BusinessAnalytics::class);
    $report = $page->instance()->report();
    expect($report['today']['revenue'])->toBe(4000)
        ->and($report['today']['cost'])->toBe(2000)
        ->and($report['today']['transactions'])->toBe(1)
        ->and($report['payments']['cash'])->toBe(4000)
        ->and($report['topProducts']->first()->product_id)->toBe($product->id);

    $sale->items()->update(['cost_price' => null]);
    $page->refresh()->assertSee('Unavailable');
    expect($page->instance()->report()['totals']['missing'])->toBe(1);

    $sale->forceFill(['created_at' => '2026-09-01 12:00:00'])->save();
    $page->set('days', 7);
    expect($page->instance()->report()['totals']['revenue'])->toBe(0);
});

it('requires authentication to access business analytics', function () {
    auth()->logout();
    $this->get(route('filament.store.pages.business-analytics'))->assertRedirect();
});
