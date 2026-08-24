<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('category_product')->insertUsing(
            ['category_id', 'product_id'],
            DB::table('products')->select(['categories_id', 'id']),
        );
    }

    public function down(): void
    {
        DB::table('products')
            ->select('id')
            ->eachById(function (object $product): void {
                $categoryId = DB::table('category_product')
                    ->where('product_id', $product->id)
                    ->orderBy('category_id')
                    ->value('category_id');

                DB::table('products')
                    ->where('id', $product->id)
                    ->update(['categories_id' => $categoryId]);
            });
    }
};
