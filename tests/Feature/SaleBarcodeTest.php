<?php

use App\Filament\Resources\Sales\Pages\CreateSale;
use App\Filament\Resources\Sales\Pages\DashboardSale;
use App\Models\Sale;
use App\Models\User;
use Database\Factories\ProductFactory;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

it('shows checkout directly on the dashboard', function () {
    config(['app.env' => 'local']);
    $this->get(route('filament.store.pages.dashboard'))
        ->assertSuccessful()
        ->assertSee('Quick sale')
        ->assertSee('Scan with camera')
        ->assertSee('Complete sale');
});

it('completes dashboard sales and resets for the next customer without redirecting', function () {
    $page = Livewire::test(DashboardSale::class);
    $reference = $page->get('data.reference_number');
    $page->call('addScannedProduct', $this->product->barcode)
        ->set('data.payment_method', 'cash')
        ->set('data.amount_paid', 20)
        ->call('create')
        ->assertHasNoErrors()
        ->assertNoRedirect()
        ->assertSet('data.items', [])
        ->assertSet('isCreating', false);

    expect($page->get('data.reference_number'))->not->toBe($reference);
    $page->call('addScannedProduct', $this->product->barcode)
        ->set('data.payment_method', 'cash')
        ->set('data.amount_paid', 20)
        ->call('create')
        ->assertHasNoErrors()
        ->assertNoRedirect();
    expect(Sale::count())->toBe(2)
        ->and((float) $this->product->fresh()->stock_quantity)->toBe(0.0);
});

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    Filament::setCurrentPanel(Filament::getPanel('store'));
    $this->product = ProductFactory::new()->create([
        'name' => 'Scan product', 'sku' => 'SCAN-001', 'price' => 10,
        'selling_price' => 15, 'stock_quantity' => 2, 'unit' => 'pcs', 'is_active' => true,
    ]);
});

it('adds scanned items and increments quantity without changing stock before checkout', function () {
    $page = Livewire::test(CreateSale::class)
        ->call('addScannedProduct', $this->product->barcode)
        ->call('addScannedProduct', $this->product->barcode)
        ->assertHasNoErrors();

    $items = array_values($page->get('data.items'));
    expect($items)->toHaveCount(1)
        ->and($items[0]['quantity'])->toBe(2)
        ->and((float) $items[0]['subtotal'])->toBe(30.0)
        ->and((float) $this->product->fresh()->stock_quantity)->toBe(2.0);

    $page->call('addScannedProduct', $this->product->barcode)
        ->assertHasErrors('data.scanned_barcode');
    expect(array_values($page->get('data.items'))[0]['quantity'])->toBe(2);
});

it('rejects unknown and inactive barcodes', function () {
    Livewire::test(CreateSale::class)
        ->call('addScannedProduct', 'UNKNOWN')
        ->assertHasErrors('data.scanned_barcode');

    $this->product->update(['is_active' => false]);
    Livewire::test(CreateSale::class)
        ->call('addScannedProduct', $this->product->barcode)
        ->assertHasErrors('data.scanned_barcode')
        ->assertSet('data.items', []);
});

it('completes a scanned sale using server prices and deducts stock once', function () {
    $page = Livewire::test(CreateSale::class)
        ->call('addScannedProduct', $this->product->barcode);
    $items = $page->get('data.items');
    $key = array_key_first($items);

    $page->set('data.items.'.$key.'.price', 1)
        ->set('data.items.'.$key.'.subtotal', 1)
        ->set('data.payment_method', 'cash')
        ->set('data.amount_paid', 20)
        ->call('create')
        ->assertHasNoErrors();

    $sale = Sale::query()->sole();
    expect((float) $sale->total_amount)->toBe(15.0)
        ->and((float) $sale->change_amount)->toBe(5.0)
        ->and($sale->items()->count())->toBe(1)
        ->and((float) $sale->items()->first()->cost_price)->toBe(10.0)
        ->and((float) $this->product->fresh()->stock_quantity)->toBe(1.0);
});
