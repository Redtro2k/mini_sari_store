<?php

use App\Models\Product;
use Illuminate\Support\Facades\Route;
use Milon\Barcode\Facades\DNS1DFacade as Barcode;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/barcode', function () {
    return Barcode::getBarcodeHTML(Product::first()->sku, 'C39E', 0.7, 33);
});
