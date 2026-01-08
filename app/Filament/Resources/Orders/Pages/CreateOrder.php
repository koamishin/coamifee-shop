<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

final class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    /**
     * Store order items temporarily.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $orderItems = [];

    /**
     * Mutate form data before creating the order.
     * This handles the custom order date for backdated orders.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Handle backdated order date
        if (! empty($data['order_date'])) {
            $orderDate = Date::parse($data['order_date']);

            $data['created_at'] = $orderDate;
            $data['updated_at'] = $orderDate;
        }

        unset($data['order_date']);

        // Store order items for later processing
        $this->orderItems = $data['order_items'] ?? [];
        unset($data['order_items']);

        // Calculate subtotal from items if they exist
        if ($this->orderItems !== []) {
            $subtotal = 0.0;
            foreach ($this->orderItems as $item) {
                $quantity = (int) ($item['quantity'] ?? 0);
                $price = (float) ($item['price'] ?? 0);
                $subtotal += $quantity * $price;
            }
            $data['subtotal'] = $subtotal;

            // Set total if not already set or if it was 0
            if (empty($data['total']) || (float) $data['total'] === 0.0) {
                $data['total'] = $subtotal;
            }
        }

        // Set payment_status to 'paid' for completed orders since these are backdated
        if (($data['status'] ?? '') === 'completed') {
            $data['payment_status'] = 'paid';
        }

        return $data;
    }

    /**
     * Handle record creation including order items.
     */
    protected function handleRecordCreation(array $data): Model
    {
        /** @var Order $order */
        $order = self::getModel()::query()->create($data);

        // Create order items
        $this->createOrderItems($order);

        return $order;
    }

    /**
     * Create order items for the order.
     */
    private function createOrderItems(Order $order): void
    {
        foreach ($this->orderItems as $item) {
            $productId = $item['product_id'] ?? null;
            if (! $productId) {
                continue;
            }

            $quantity = (int) ($item['quantity'] ?? 1);
            $price = (float) ($item['price'] ?? 0);
            $variantId = $item['product_variant_id'] ?? null;
            $notes = $item['notes'] ?? null;

            // Get variant name if variant is selected
            $variantName = null;
            if ($variantId) {
                $variant = ProductVariant::query()->find($variantId);
                if ($variant) {
                    $variantName = $variant->name;
                }
            }

            OrderItem::query()->create([
                'order_id' => $order->id,
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'variant_name' => $variantName,
                'quantity' => $quantity,
                'price' => $price,
                'subtotal' => $quantity * $price,
                'notes' => $notes,
                'is_served' => true, // Mark as served since these are completed orders
            ]);
        }
    }
}
