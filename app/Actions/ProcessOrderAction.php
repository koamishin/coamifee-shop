<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Order;
use App\Services\OrderProcessingService;

final class ProcessOrderAction
{
    public function __construct(
        private OrderProcessingService $orderProcessingService,
    ) {}

    public function execute(Order $order): array
    {
        if (! $this->orderProcessingService->canFulfillOrder($order)) {
            return [
                'success' => false,
                'message' => 'Cannot fulfill order: Insufficient ingredients',
                'order_id' => $order->id,
            ];
        }

        if ($this->orderProcessingService->processOrder($order)) {
            return [
                'success' => true,
                'message' => 'Order processed successfully',
                'order_id' => $order->id,
                'status' => 'completed',
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to process order',
            'order_id' => $order->id,
        ];
    }
}
