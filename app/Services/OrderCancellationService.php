<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\OrderCancellation;
use App\Models\User;
use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class OrderCancellationService
{
    public function verifyPin(User $user, string $pin): bool
    {
        if (! $user->admin_pin) {
            return false;
        }

        return $user->admin_pin === $pin;
    }

    /**
     * Check if an order can be cancelled
     */
    public function canCancelOrder(Order $order): bool
    {
        // Can only cancel if order is still in process and unpaid
        if ($order->status === 'pending' && $order->payment_status === 'unpaid') {
            return true;
        }

        return false;
    }

    /**
     * Get cancellation reason based on order state
     */
    public function getCancellationReason(Order $order): string
    {
        if ($order->status === 'pending' && $order->payment_status === 'unpaid') {
            return 'Order cancelled while still in process and unpaid';
        }

        return 'Order cannot be cancelled in current state';
    }

    /**
     * Process order cancellation
     */
    public function processCancellation(Order $order, Authenticatable|User $user, string $pin, ?string $reason = null): array
    {
        // Verify the PIN
        if (! $this->verifyPin($user, $pin)) {
            return [
                'success' => false,
                'message' => 'Invalid PIN. Cancellation was not processed.',
            ];
        }

        // Check if order can be cancelled
        if (! $this->canCancelOrder($order)) {
            return [
                'success' => false,
                'message' => 'This order cannot be cancelled. Only unpaid orders in progress can be cancelled.',
            ];
        }

        // Check if order is already cancelled
        if ($order->payment_status === 'cancelled') {
            return [
                'success' => false,
                'message' => 'This order has already been cancelled.',
            ];
        }

        try {
            DB::beginTransaction();

            $cancellationAmount = (float) $order->total;

            // Update order status to cancelled
            $order->update([
                'payment_status' => 'cancelled',
                'status' => 'cancelled',
            ]);

            // Log the cancellation
            OrderCancellation::create([
                'order_id' => $order->id,
                'cancelled_by' => $user->id,
                'cancellation_amount' => $cancellationAmount,
                'reason' => $reason ?? $this->getCancellationReason($order),
                'cancelled_at' => now(),
            ]);

            DB::commit();

            Log::info('Order cancelled', [
                'order_id' => $order->id,
                'cancelled_by' => $user->id,
                'cancellation_amount' => $cancellationAmount,
                'reason' => $reason,
            ]);

            return [
                'success' => true,
                'message' => "Order #{$order->id} has been cancelled successfully. Amount: {$cancellationAmount} (Not counted as sales)",
            ];
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Order cancellation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'An error occurred while cancelling the order: '.$e->getMessage(),
            ];
        }
    }
}
