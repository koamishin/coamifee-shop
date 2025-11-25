# Refund Logic Implementation

## Overview
Updated the refund system to handle specific order states with comprehensive refund button visibility and refund type logic.

## Refund Rules

### 1. Hide Refund Button
- **Condition**: Order is `completed` AND `paid`
- **Action**: Refund button is hidden/disabled with message "Cannot refund a completed and fully paid order"

### 2. Show Refund Button - Full Refund
- **Condition**: Order is `paid` AND products are `in progress` (status = pending)
- **Action**: Show "Refund" button
- **Behavior**: Refunds entire order amount
- **New Status**: `refunded` (both status and payment_status)

### 3. Show Refund Button - Partial Refund (Cancel Unpaid)
- **Condition**: Order has `partially_paid` status
- **Action**: Show "Cancel Unpaid" button
- **Behavior**: Refunds only the unpaid/additional products
- **New Status**: `refund_partial` (payment_status remains same as before)

### 4. Show Refund Button - Partial Refund (Completed Unpaid)
- **Condition**: Order is `completed` but NOT fully `paid` (unpaid or partially_paid)
- **Action**: Show "Refund" or "Cancel Unpaid" button based on refund type
- **Behavior**: Refunds available items
- **New Status**: `refund_partial` (payment_status)

## Implementation Details

### Files Modified

1. **`app/Services/RefundService.php`**
   - Added `canShowRefundButton(Order $order): bool` - Determines if refund button should be visible
   - Added `getRefundableItems(Order $order): array` - Returns refundable items and refund type
   - Updated `processRefund()` - Now handles full and partial refunds with proper status transitions
   - Added `recordRefundMetrics()` - Updated to track specific refunded items

2. **`app/Filament/Pages/OrdersProcessing.php`**
   - Added `canShowRefund(Order $order): bool` - Helper method for blade template
   - Added `getRefundLabel(Order $order): string` - Returns button label ("Refund" or "Cancel Unpaid")
   - Updated refund action to use new service logic

3. **`resources/views/filament/pages/orders-processing.blade.php`**
   - Updated conditional rendering to show/hide refund button based on `canShowRefund()`
   - Added dynamic button label using `getRefundLabel()`
   - Disabled button with gray styling when refund not allowed

4. **`app/Models/RefundLog.php`**
   - Added `refund_type` to fillable array

### Database Migrations

Created: `database/migrations/2025_11_25_120000_add_refund_type_to_refund_logs_and_refund_partial_payment_status.php`

Changes:
- Added `refund_type` column to `refund_logs` table (enum: 'full', 'partial')
- Added `refund_partial` status to `orders.payment_status` enum
- Default `refund_type` is 'full'

### New Payment Status Values

- `paid` - Order fully paid
- `unpaid` - Order not paid
- `partially_paid` - Order partially paid (some items paid, others added later)
- `refunded` - Order fully refunded
- `refund_partial` - Order partially refunded

## Refund Logic Flow

```
Order State Analysis
    ↓
┌─────────────────────────────────────────────────────┐
│ If completed AND paid → Hide Refund Button          │
└─────────────────────────────────────────────────────┘
    ↓ No
┌─────────────────────────────────────────────────────┐
│ If unpaid → Hide Refund Button                      │
└─────────────────────────────────────────────────────┘
    ↓ No
┌─────────────────────────────────────────────────────┐
│ If paid AND pending → Show "Refund" (Full)          │
│ If partially_paid → Show "Cancel Unpaid" (Partial)  │
│ If completed AND not paid → Show "Refund/Cancel"    │
└─────────────────────────────────────────────────────┘
    ↓
Process Refund
    ↓
┌─────────────────────────────────────────────────────┐
│ If Full Refund:                                     │
│   - payment_status = 'refunded'                     │
│   - status = 'refunded'                             │
│                                                      │
│ If Partial Refund:                                  │
│   - payment_status = 'refund_partial'               │
│   - status = (unchanged)                            │
└─────────────────────────────────────────────────────┘
```

## Testing

Comprehensive test suite in `tests/Feature/RefundServiceTest.php`:
- 15 tests covering all refund scenarios
- Tests for button visibility logic
- Tests for refund type determination
- Tests for refund processing and status updates
- Tests for refund log creation

All tests pass ✓

## Usage Example

### In Blade Template
```blade
@if($this->canShowRefund($order))
    <button wire:click="mountAction('refund', { orderId: {{ $order->id }} })">
        {{ $this->getRefundLabel($order) }}
    </button>
@else
    <button disabled>Refund (unavailable)</button>
@endif
```

### In PHP Code
```php
$refundService = app(RefundService::class);

// Check if refund button should show
if ($refundService->canShowRefundButton($order)) {
    // Show refund button
}

// Get refund details
$refundData = $refundService->getRefundableItems($order);
$type = $refundData['type']; // 'full' or 'partial'
$total = $refundData['total']; // Amount to refund
$items = $refundData['items']; // Items being refunded

// Process refund
$result = $refundService->processRefund($order, $user, $pin);
```

## Edge Cases Handled

1. ✓ Orders with no items
2. ✓ Orders with multiple items at different prices
3. ✓ Already refunded orders
4. ✓ Invalid PIN verification
5. ✓ Completed but unpaid orders
6. ✓ Partially paid orders with additional items
7. ✓ Decimal precision in amounts
8. ✓ Proper transaction handling with rollback on error
9. ✓ Refund log tracking with refund type

## Future Enhancements

- Item-level refund selection (refund specific items only)
- Refund approval workflow
- Refund reason tracking
- Integration with payment gateways for automatic refunds
- Refund report generation
