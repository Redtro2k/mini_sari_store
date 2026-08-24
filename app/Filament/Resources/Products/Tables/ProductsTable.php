<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\{TextInput, Textarea};



class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('price')
                ->money('PHP')
                ->summarize(\Filament\Tables\Columns\Summarizers\Sum::make()->money('PHP')),
                TextColumn::make('categories.name'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                ReplicateAction::make()
                ->excludeAttributes(['sku', 'description','note'])
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
                ])
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
