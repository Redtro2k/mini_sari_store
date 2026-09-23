<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('images')
                    ->stacked()
                    ->limit(3)
                    ->limitedRemainingText(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('sku')->label('SKU')->searchable(),
                TextColumn::make('barcode')
                    ->searchable(),
                TextColumn::make('description')->limit(50),
                TextColumn::make('price')
                    ->money('PHP'),
                // ->summarize(Sum::make()->money('PHP')),
                TextColumn::make('categories.name')->badge(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('downloadBarcode')
                    ->label('Download barcode')
                    ->visible(fn (Product $record): bool => filled($record->barcode))
                    ->action(fn (Product $record) => response()->streamDownload(
                        function () use ($record): void {
                            echo view('filament.products.barcode-label', ['product' => $record])->render();
                        },
                        'product-'.$record->id.'-barcode.svg',
                        ['Content-Type' => 'image/svg+xml'],
                    )),
                EditAction::make(),
                ReplicateAction::make()
                    ->excludeAttributes(['sku', 'barcode', 'description', 'note'])
                    ->form([

                        TextInput::make('sku')
                            ->label('SKU')
                            ->placeholder('e.g. PRD-0001')
                            ->helperText('Use a unique code that is easy to identify and search.')
                            ->required(),
                        Textarea::make('description')
                            ->placeholder('Describe the product, its features, size, or variant')
                            ->helperText('Add details that help distinguish this product from similar items.')
                            ->rows(4)
                            ->autosize(),
                        Textarea::make('note')
                            ->label('Internal note')
                            ->placeholder('Add optional purchasing, storage, or inventory notes')
                            ->helperText('Only staff can see this note.')
                            ->rows(3)
                            ->autosize(),
                    ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
