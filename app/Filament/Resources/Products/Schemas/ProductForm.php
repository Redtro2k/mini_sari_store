<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class ProductForm
{
    
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('categories')
                    ->label('Categories')
                    ->placeholder('Select one or more categories')
                    ->relationship('categories', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->placeholder('e.g. Beverages')
                            ->required(),
                        Textarea::make('description')
                            ->placeholder('Briefly describe this category')
                            ->rows(3),
                    ])
                    ->required(),
                TextInput::make('name')
                    ->placeholder('e.g. Classic Milk Tea')
                    ->helperText('Use the product name customers and staff will recognize.')
                    ->required(),
                TextInput::make('sku')
                    ->label('SKU')
                    ->placeholder('e.g. PRD-0001')
                    ->helperText('Use a unique code that is easy to identify and search.')
                    ->required(),
                Grid::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make('price')
                            ->prefix('₱')
                            ->placeholder('0.00')
                            ->helperText('The product acquisition or unit cost.')
                            ->required()
                            ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2),
                        TextInput::make('selling_price')
                            ->label('Selling price')
                            ->prefix('₱')
                            ->placeholder('0.00')
                            ->helperText('The price customers will pay.')
                            ->required()
                            ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2),
                    ]),
                Textarea::make('description')
                    ->placeholder('Describe the product, its features, size, or variant')
                    ->helperText('Add details that help distinguish this product from similar items.')
                    ->rows(4)
                    ->autosize(),
                Grid::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make('stock_quantity')
                            ->label('Opening stock')
                            ->placeholder('0')
                            ->helperText('Enter the quantity currently available in inventory.')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required(),
                        Radio::make('is_active')
                            ->label('Product status')
                            ->helperText('Inactive products remain saved but are not available for use.')
                            ->inline()
                            ->boolean(),
                    ]),
                Textarea::make('note')
                    ->label('Internal note')
                    ->placeholder('Add optional purchasing, storage, or inventory notes')
                    ->helperText('Only staff can see this note.')
                    ->rows(3)
                    ->autosize(),
            ]);
    }
}
