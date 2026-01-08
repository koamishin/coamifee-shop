<?php

declare(strict_types=1);

use App\Filament\Pages\PosPage;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    actingAs($this->user);

    // Create a category and product for testing
    $this->category = Category::factory()->create(['is_active' => true]);
    $this->product = Product::factory()->create([
        'category_id' => $this->category->id,
        'price' => 100.00,
        'is_active' => true,
    ]);
});

it('can place order with a past date', function (): void {
    $pastDate = Carbon::now()->subDays(5)->setSeconds(0)->toDateTimeString();

    Livewire::test(PosPage::class)
        ->set('orderType', 'dine_in')
        ->set('cartItems', [
            [
                'product_id' => $this->product->id,
                'variant_id' => null,
                'variant_name' => null,
                'name' => $this->product->name,
                'price' => 100.00,
                'quantity' => 1,
                'subtotal' => 100.00,
            ],
        ])
        ->set('totalAmount', 100.00)
        ->mountAction('placeOrder')
        ->fillForm([
            'tableNumber' => 'table_1',
            'paymentTiming' => 'pay_later',
            'creationDate' => $pastDate,
        ])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    expect(Order::query()->count())->toBe(1);

    $order = Order::query()->first();

    // Allow for small time difference due to execution time,
    // but since we are passing a string, it should be exact if parsed correctly by Laravel's cast/Eloquent
    // However, the database stores it with seconds precision.
    // Let's compare timestamps or formatted strings.

    expect($order->created_at->toDateTimeString())->toBe($pastDate);
    expect($order->updated_at->toDateTimeString())->toBe($pastDate);

    $orderItem = OrderItem::query()->first();
    // Order items might have seconds if created_at was passed directly without seconds stripping?
    // But we passed $pastDate which has 00 seconds now.
    expect($orderItem->created_at->toDateTimeString())->toBe($pastDate);
    expect($orderItem->updated_at->toDateTimeString())->toBe($pastDate);
});

it('defaults to current date if creation date is not provided', function (): void {
    // We need to freeze time to verify "now"
    $now = Carbon::now()->setSeconds(0);
    Carbon::setTestNow($now);

    Livewire::test(PosPage::class)
        ->set('orderType', 'dine_in')
        ->set('cartItems', [
            [
                'product_id' => $this->product->id,
                'variant_id' => null,
                'variant_name' => null,
                'name' => $this->product->name,
                'price' => 100.00,
                'quantity' => 1,
                'subtotal' => 100.00,
            ],
        ])
        ->set('totalAmount', 100.00)
        ->mountAction('placeOrder')
        ->fillForm([
            'tableNumber' => 'table_2',
            'paymentTiming' => 'pay_later',
            // creationDate omitted, should default to now
        ])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    expect(Order::query()->count())->toBe(1);

    $order = Order::query()->first();

    // In the actual implementation, the default is set in the form schema default(now())
    // and then passed to the action.
    // If we omit it in fillForm, the default might not be applied if we are not simulating the frontend interaction fully?
    // Livewire `fillForm` simulates filling the form. If we don't provide a value,
    // the default value from schema should be used.

    // However, depending on how `fillForm` works in tests, it might not trigger the default value evaluation
    // if the key is missing from the input array, unless we explicitly verify that default is present.
    // But let's assume standard behavior.

    // The implementation has $this->creationDate ?? now() fallback in the action handler too.

    expect($order->created_at->setSeconds(0)->toDateTimeString())->toBe($now->toDateTimeString());
});
