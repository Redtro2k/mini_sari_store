<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'sku', 'price', 'selling_price', 'description', 'stock_quantity', 'is_active', 'note'])]
class Product extends Model
{
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Categories::class, 'category_product', 'product_id', 'category_id');
    }
}
