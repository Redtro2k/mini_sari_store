<?php

use App\Models\Product;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Milon\Barcode\DNS1D;

uses(LazilyRefreshDatabase::class);

it('renders printable labels with white background and quiet zones', function () {
    $product = createProduct(['name' => 'Pens & pencils']);
    $label = view('filament.products.barcode-label', ['product' => $product])->render();
    $document = new DOMDocument;
    expect($document->loadXML($label))->toBeTrue();
    expect($label)->toContain('fill="white"', 'translate(40, 20)', $product->barcode, 'Pens &amp; pencils');
});

it('generates a unique barcode when one is not supplied', function () {
    $firstProduct = createProduct(['sku' => 'PRD-0001']);
    $secondProduct = createProduct(['sku' => 'PRD-0002']);

    expect($firstProduct->barcode)
        ->toMatch('/^[0-9]{8}$/')
        ->not->toBe($secondProduct->barcode);
});

it('preserves a supplied barcode and renders it as a code 128 image', function () {
    $product = createProduct([
        'sku' => 'PRD-0003',
        'barcode' => 'SUPPLIER-123',
    ]);

    $barcodeImage = (new DNS1D)->getBarcodePNG($product->barcode, 'C128');

    expect($product->barcode)
        ->toBe('SUPPLIER-123')
        ->and(base64_decode($barcodeImage, true))
        ->not->toBeFalse();
});

/**
 * @param  array<string, mixed>  $attributes
 */
function createProduct(array $attributes = []): Product
{
    return Product::query()->create(array_merge([
        'name' => 'Test Product',
        'sku' => 'PRD-TEST',
        'price' => 10,
        'selling_price' => 15,
        'stock_quantity' => 1,
        'unit' => 'pcs',
        'is_active' => true,
    ], $attributes));
}
