<?php

namespace App\Models;

use App\Enums\Unit;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'sku', 'barcode', 'price', 'selling_price', 'description', 'stock_quantity', 'is_active', 'note', 'unit', 'images'])]
class Product extends Model
{
    protected static function booted(): void
    {
        static::creating(function (Product $product): void {
            if (filled($product->barcode)) {
                return;
            }

            do {
                $product->barcode = (string) random_int(10000000, 99999999);
            } while (Product::query()->where('barcode', $product->barcode)->exists());
        });
    }

    protected function casts(): array
    {
        return [
            'stock_quantity' => 'decimal:3',
            'unit' => Unit::class,
            'is_active' => 'boolean',
            'price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'images' => 'json',
        ];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Categories::class, 'category_product', 'product_id', 'category_id');
    }
}
