# Add Products with Predefined Discounts Implementation

## Overview
When adding products to an existing order in the Orders Processing page (`/cashier/orders-processing`), users can now select predefined discounts (PWD & Senior Citizen) for each product, and these discounts are automatically calculated and applied.

## Changes Made

### 1. Database Migration
**File:** `database/migrations/2025_11_29_000001_add_discount_type_to_order_items_table.php`
- Added `discount_type` column to `order_items` table
- Stores the type of discount applied (e.g., 'pwd', 'senior')

### 2. Models

#### OrderItem Model
**File:** `app/Models/OrderItem.php`
- Added `discount_type` to `$fillable` array
- Allows storing the discount type on each order item

### 3. OrdersProcessing Page Component
**File:** `app/Filament/Pages/OrdersProcessing.php`

#### Key Methods Updated:

**`addToCart()` method**
- Now initializes cart items with discount fields:
  - `discount_type`: null initially
  - `discount_percentage`: 0
  - `discount_amount`: 0

**New method: `updateCartItemDiscount()`**
- Handles discount type changes from the dropdown
- Automatically fills the discount percentage based on the selected discount type:
  - PWD: 20%
  - Senior Citizen: 20%
- Clears the discount if "None" is selected

**`getCartItemsHtml()` method**
- Enhanced to display discount dropdown for each cart item
- Shows calculated discount amount and percentage
- Displays final subtotal after discount

**`getCartTotalHtml()` method**
- Now calculates and displays total discount across all items
- Shows subtotal, discount breakdown, and final total

**`addProductAction()` action method**
- Passes discount information to the service when adding items:
  - `discount_type`
  - `discount_percentage`

### 4. OrderModificationService
**File:** `app/Services/OrderModificationService.php`

#### Updated Processing:

**Item Validation Loop**
- Calculates item-level discount amount based on discount percentage
- Stores discount information in the items array

**OrderItem Creation**
- Creates items with discount fields:
  - `discount_type`
  - `discount_percentage`
  - `discount_amount`

**`recalculateOrderTotals()` method**
- Calculates total item-level discounts
- Properly accounts for both item-level and order-level discounts
- Formula: `Final Total = Subtotal - Item Discounts - Order Discount + Add-ons`

### 5. Tests
**File:** `tests/Feature/AddProductsWithDiscountTest.php`

Comprehensive test coverage for:
- Adding products with PWD discount (20%)
- Adding products with Senior Citizen discount (20%)
- Adding multiple products with different discounts
- Adding products without discount
- Proper calculation of totals with discounts applied

## Features

### UI Changes
When adding products via the "Add Products - Order #X" modal:

1. **Discount Selection Dropdown**
   - Each cart item shows a "Discount" dropdown
   - Options: "None", "PWD (Person with Disability)", "Senior Citizen"

2. **Automatic Calculation**
   - Selecting a discount type automatically fills the percentage (20%)
   - Shows the discount amount (e.g., "-₱20.00")

3. **Cart Summary**
   - Displays subtotal
   - Shows total discount across all items
   - Shows final total after all discounts

### Discount Types
Currently supports:
- **PWD (Person with Disability)**: 20% discount
- **Senior Citizen**: 20% discount

These are defined in the `DiscountType` enum and can be easily modified.

## Calculation Example

### Single Item with Discount
- Product: ₱100.00 × 1
- Discount: PWD (20%)
- Discount Amount: ₱100.00 × 20% = ₱20.00
- Final Price: ₱100.00 - ₱20.00 = ₱80.00

### Multiple Items with Different Discounts
- Item 1: ₱100.00 (PWD 20%) = ₱80.00
- Item 2: ₱50.00 (Senior 20%) = ₱40.00
- Subtotal: ₱150.00
- Total Discount: ₱30.00
- Final Total: ₱120.00

## Data Flow

1. User adds product in modal via "Add" button
2. Product added to cart with discount fields
3. User selects discount type from dropdown
4. Discount percentage auto-fills and amount is calculated
5. Cart total updates to reflect discounts
6. User clicks "Add Products to Order"
7. OrderModificationService processes items with discounts
8. Order items are created with discount information
9. Order totals are recalculated with item-level discounts

## Files Modified/Created

### Created:
- `database/migrations/2025_11_29_000001_add_discount_type_to_order_items_table.php`
- `tests/Feature/AddProductsWithDiscountTest.php`

### Modified:
- `app/Filament/Pages/OrdersProcessing.php`
- `app/Services/OrderModificationService.php`
- `app/Models/OrderItem.php`

## Testing
Run the tests with:
```bash
php artisan test tests/Feature/AddProductsWithDiscountTest.php
```

Or test the entire order modification suite:
```bash
php artisan test tests/Feature/OrderModificationTest.php
```

## Notes
- Item-level discounts are independent of order-level discounts
- Both types of discounts are applied in the calculation
- Discount information is persisted to the database for reporting
- The implementation follows existing discount calculation patterns in the POS system
