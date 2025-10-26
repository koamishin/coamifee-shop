<?php

declare(strict_types=1);

namespace App\Services\Pos;

use App\Models\Product;

final readonly class PosCartService
{
    public function __construct(
        private PosProductService $productService
    ) {}

    public function addToCart(array $cart, int $productId, array $customizations = [], int $quantity = 1): array
    {
        $product = Product::query()->find($productId);
        if (! $product) {
            return $cart;
        }

        $cartKey = $this->generateCartKey($productId, $customizations);

        if (! isset($cart[$cartKey])) {
            $cart[$cartKey] = [
                'product_id' => $productId,
                'product' => $product,
                'quantity' => 0,
                'customizations' => $customizations,
                'price' => $product->price,
                'subtotal' => 0,
            ];
        }

        $maxQuantity = $this->productService->canAddToCart($productId)
            ? $this->productService->getMaxProducibleQuantity($productId)
            : 0;

        $availableQuantity = min($quantity, $maxQuantity - $cart[$cartKey]['quantity']);

        if ($availableQuantity > 0) {
            $cart[$cartKey]['quantity'] += $availableQuantity;
            $cart[$cartKey]['subtotal'] = $cart[$cartKey]['quantity'] * $cart[$cartKey]['price'];
        }

        return $cart;
    }

    public function removeFromCart(array $cart, string $cartKey): array
    {
        unset($cart[$cartKey]);

        return $cart;
    }

    public function updateQuantity(array $cart, string $cartKey, int $quantity): array
    {
        if (! isset($cart[$cartKey])) {
            return $cart;
        }

        $maxQuantity = $this->productService->getMaxProducibleQuantity($cart[$cartKey]['product_id']);
        $cart[$cartKey]['quantity'] = max(0, min($quantity, $maxQuantity));
        $cart[$cartKey]['subtotal'] = $cart[$cartKey]['quantity'] * $cart[$cartKey]['price'];

        if ($cart[$cartKey]['quantity'] === 0) {
            unset($cart[$cartKey]);
        }

        return $cart;
    }

    public function incrementQuantity(array $cart, string $cartKey): array
    {
        if (! isset($cart[$cartKey])) {
            return $cart;
        }

        return $this->updateQuantity($cart, $cartKey, $cart[$cartKey]['quantity'] + 1);
    }

    public function decrementQuantity(array $cart, string $cartKey): array
    {
        if (! isset($cart[$cartKey])) {
            return $cart;
        }

        return $this->updateQuantity($cart, $cartKey, $cart[$cartKey]['quantity'] - 1);
    }

    public function clearCart(): array
    {
        return [];
    }

    public function calculateTotals(array $cart, float $taxRate = 0.0): array
    {
        $subtotal = array_sum(array_column($cart, 'subtotal'));
        $taxAmount = $subtotal * ($taxRate / 100);
        $total = $subtotal + $taxAmount;

        return [
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'item_count' => array_sum(array_column($cart, 'quantity')),
        ];
    }

    public function applyDiscount(array &$cart, float $discountPercentage): float
    {
        $totals = $this->calculateTotals($cart);

        return $totals['subtotal'] * ($discountPercentage / 100);
    }

    public function getCartItemKeys(array $cart): array
    {
        return array_keys($cart);
    }

    public function hasItems(array $cart): bool
    {
        return $cart !== [];
    }

    public function getCartItemCount(array $cart): int
    {
        return array_sum(array_column($cart, 'quantity'));
    }

    private function generateCartKey(int $productId, array $customizations): string
    {
        $customizationHash = md5(serialize($customizations));

        return $productId.'_'.$customizationHash;
    }
}
