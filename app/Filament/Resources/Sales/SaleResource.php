<?php

namespace App\Filament\Resources\Sales;

use App\Filament\Resources\Sales\Pages\CreateSale;
use App\Filament\Resources\Sales\Pages\ListSales;
use App\Filament\Resources\Sales\Pages\ViewSale;
use App\Models\Product;
use App\Models\Sale;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class SaleResource extends \Filament\Resources\Resource
{
    protected static ?string $model = Sale::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::ShoppingCart;

    protected static ?string $navigationLabel = 'Sales';

    protected static ?string $modelLabel = 'sale';

    protected static ?string $pluralModelLabel = 'sales';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Sale details')
                ->columnSpanFull()
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('reference_number')
                            ->default(fn (): string => 'SALE-'.Str::upper(Str::random(8)))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->readOnly(),
                        Select::make('payment_method')
                            ->options([
                                'cash' => 'Cash',
                                'card' => 'Card',
                                'ewallet' => 'E-wallet',
                            ])
                            ->required(),
                        TextInput::make('amount_paid')
                            ->numeric()
                            ->prefix('₱')
                            ->minValue(0)
                            ->required()
                            ->live(onBlur: true),
                    ]),
                ]),
            Section::make('Products')
                ->columnSpanFull()
                ->schema([
                    View::make('filament.sales.camera-scanner')->columnSpanFull(),
                    TextInput::make('scanned_barcode')
                        ->label('Scan or enter a barcode')
                        ->placeholder('Scan here, then press Enter')
                        ->helperText('Use a scanner that types into this field, or paste a code and choose Add item.')
                        ->maxLength(255)
                        ->dehydrated(false)
                        ->extraInputAttributes([
                            'x-on:keydown.enter.prevent.stop' => '$wire.addScannedProduct($event.target.value)',
                            'autocomplete' => 'off',
                        ])
                        ->suffixAction(Action::make('addScannedProduct')
                            ->label('Add item')
                            ->icon(Heroicon::Plus)
                            ->action(fn (CreateSale $livewire, Get $get) => $livewire->addScannedProduct((string) $get('scanned_barcode'))))
                        ->columnSpanFull(),
                    Repeater::make('items')
                        ->label('Sale items')
                        ->schema([
                            Select::make('product_id')
                                ->label('Product')
                                ->columnSpan(['default' => 1, 'md' => 5])
                                ->options(fn (): array => Product::query()
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all())
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (Set $set, Get $get, ?int $state): void {
                                    $product = $state === null ? null : Product::find($state);
                                    $price = (float) ($product?->selling_price ?? 0);
                                    $set('price', $price);
                                    $set('subtotal', $price * max(1, (int) $get('quantity')));
                                }),
                            TextInput::make('quantity')
                                ->columnSpan(['default' => 1, 'md' => 2])
                                ->numeric()
                                ->integer()
                                ->minValue(1)
                                ->default(1)
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Set $set, Get $get, ?int $state): mixed => $set(
                                    'subtotal',
                                    (float) ($get('price') ?? 0) * max(1, (int) ($state ?? 1)),
                                )),
                            TextInput::make('price')
                                ->columnSpan(['default' => 1, 'md' => 2])
                                ->numeric()
                                ->prefix('₱')
                                ->readOnly()
                                ->required(),
                            TextInput::make('subtotal')
                                ->columnSpan(['default' => 1, 'md' => 3])
                                ->numeric()
                                ->prefix('₱')
                                ->readOnly()
                                ->required(),
                        ])
                        ->columns(['default' => 1, 'md' => 12])
                        ->columnSpanFull()
                        ->defaultItems(0)
                        ->reorderable(false)
                        ->minItems(1)
                        ->required()
                        ->addActionLabel('Add product'),
                ]),
            Section::make('Payment summary')
                ->columnSpanFull()
                ->schema([
                    Grid::make(2)->schema([
                        Placeholder::make('total_preview')
                            ->label('Total')
                            ->content(fn (Get $get): string => '₱'.number_format(collect($get('items') ?? [])->sum(
                                fn (array $item): float => (float) ($item['subtotal'] ?? 0),
                            ), 2)),
                        Placeholder::make('payment_note')
                            ->label('Payment')
                            ->content('The amount paid must cover the sale total.'),
                    ]),
                ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Sale details')->schema([
                TextEntry::make('reference_number'),
                TextEntry::make('payment_method')->formatStateUsing(fn (?string $state): string => Str::headline($state ?? '')),
                TextEntry::make('user.name')->label('Cashier'),
                TextEntry::make('created_at')->dateTime(),
                TextEntry::make('total_amount')->money('PHP'),
                TextEntry::make('amount_paid')->money('PHP'),
                TextEntry::make('change_amount')->money('PHP'),
            ])->columns(3),
            Section::make('Items')->schema([
                TextEntry::make('items')->state(fn (Sale $record): string => $record->items
                    ->loadMissing('product')
                    ->map(fn ($item): string => "{$item->product->name} × {$item->quantity} = ₱".number_format((float) $item->subtotal, 2))
                    ->join("\n")),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_number')->searchable()->sortable(),
                TextColumn::make('user.name')->label('Cashier')->searchable(),
                TextColumn::make('payment_method')->badge(),
                TextColumn::make('total_amount')->money('PHP')->sortable(),
                TextColumn::make('amount_paid')->money('PHP'),
                TextColumn::make('change_amount')->money('PHP'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('view')
                    ->label('View')
                    ->icon(Heroicon::Eye)
                    ->url(fn (Sale $record): string => static::getUrl('view', ['record' => $record])),
            ])
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('user'));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSales::route('/'),
            'create' => CreateSale::route('/create'),
            'view' => ViewSale::route('/{record}'),
        ];
    }
}
