<?php

namespace App\Filament\Resources\Sales\Pages;

use App\Filament\Resources\Sales\SaleResource;
use App\Models\Product;
use App\Models\Sale;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;

    public function addScannedProduct(string $barcode): ?string
    {
        $this->authorizeAccess();
        $this->resetErrorBag('data.scanned_barcode');
        $barcode = trim($barcode);

        if ($barcode === '' || strlen($barcode) > 255) {
            $this->addError('data.scanned_barcode', 'Enter a valid product barcode.');

            return null;
        }

        $product = Product::query()->where('barcode', $barcode)->where('is_active', true)->first();

        if (! $product) {
            $this->addError('data.scanned_barcode', 'No active product matches this barcode.');

            return null;
        }

        $items = $this->data['items'] ?? [];
        $matchingKey = null;
        $quantity = 1;

        foreach ($items as $key => $item) {
            if ((int) ($item['product_id'] ?? 0) === $product->id) {
                $matchingKey ??= $key;
                $quantity += max(0, (int) ($item['quantity'] ?? 0));
            }
        }

        if ($quantity > (float) $product->stock_quantity) {
            $this->addError('data.scanned_barcode', 'There is not enough stock to add another item.');

            return null;
        }

        foreach ($items as $key => $item) {
            if (blank($item['product_id'] ?? null) || (int) $item['product_id'] === $product->id) {
                unset($items[$key]);
            }
        }

        $items[$matchingKey ?? (string) Str::uuid()] = [
            'product_id' => $product->id,
            'quantity' => $quantity,
            'price' => $product->selling_price,
            'subtotal' => number_format((float) $product->selling_price * $quantity, 2, '.', ''),
        ];

        $this->data['items'] = $items;
        $this->data['scanned_barcode'] = null;
        Notification::make()->success()->title($product->name.' added')->send();

        return $product->name;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): Sale {
            $items = collect($data['items'] ?? [])
                ->map(fn (array $item): array => [
                    'product_id' => (int) $item['product_id'],
                    'quantity' => (int) $item['quantity'],
                ])
                ->groupBy('product_id')
                ->map(fn ($group): array => [
                    'product_id' => $group->first()['product_id'],
                    'quantity' => $group->sum('quantity'),
                ])
                ->values();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'Add at least one product.']);
            }

            $products = Product::query()
                ->whereIn('id', $items->pluck('product_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $preparedItems = $items->map(function (array $item) use ($products): array {
                $product = $products->get($item['product_id']);

                if (! $product || ! $product->is_active) {
                    throw ValidationException::withMessages(['items' => 'One of the selected products is unavailable.']);
                }

                if ((int) $product->stock_quantity < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'items' => "Not enough stock for {$product->name}.",
                    ]);
                }

                $price = (float) $product->selling_price;

                return [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'price' => $price,
                    'cost_price' => $product->price,
                    'subtotal' => $price * $item['quantity'],
                ];
            });

            $total = $preparedItems->sum('subtotal');
            $amountPaid = (float) ($data['amount_paid'] ?? 0);

            if ($amountPaid < $total) {
                throw ValidationException::withMessages(['amount_paid' => 'The amount paid is less than the sale total.']);
            }

            $sale = Sale::create([
                'reference_number' => $data['reference_number'],
                'payment_method' => $data['payment_method'],
                'total_amount' => $total,
                'amount_paid' => $amountPaid,
                'change_amount' => $amountPaid - $total,
                'user_id' => auth()->id(),
            ]);

            foreach ($preparedItems as $item) {
                $sale->items()->create([
                    'product_id' => $item['product']->id,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'cost_price' => $item['cost_price'],
                    'subtotal' => $item['subtotal'],
                ]);

                $item['product']->decrement('stock_quantity', $item['quantity']);
            }

            return $sale;
        });
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Sale recorded');
    }
}
