<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('products')->whereNull('barcode')->orderBy('id')->chunkById(100, function ($products): void {
            foreach ($products as $product) {
                do {
                    $barcode = Str::upper(Str::random(12));
                } while (DB::table('products')->where('barcode', $barcode)->exists());

                DB::table('products')->where('id', $product->id)->whereNull('barcode')->update(['barcode' => $barcode]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Preserve assigned identifiers: printed labels may already reference them.
    }
};
