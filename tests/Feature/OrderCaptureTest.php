<?php

use App\Models\Ingredient;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\OrderPayment;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Services\ChangeProductPrice;
use App\Services\RecordIngredientPurchase;
use App\Services\SaveOrder;
use App\Services\SaveProductProfile;
use App\Services\SaveRecipe;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(ValidateCsrfToken::class);
});

function orderPurchase(array $overrides = []): array
{
    return array_replace([
        'ingredient_name' => 'Harina de pedido', 'presentation' => 'Bolsa de 1 kg',
        'purchase_quantity' => '1', 'purchase_unit' => 'kg', 'total_paid' => '42.00',
        'purchased_on' => '2026-09-18', 'store' => null, 'note' => null,
        'request_key' => (string) Str::uuid(),
    ], $overrides);
}

function orderProduct(string $name = 'Producto de pedido', bool $priced = true): array
{
    $purchase = app(RecordIngredientPurchase::class)->record(orderPurchase([
        'ingredient_name' => $name.' ingrediente',
        'request_key' => (string) Str::uuid(),
    ]));
    $version = app(SaveRecipe::class)->save([
        'name' => $name, 'expected_yield' => '10', 'instructions' => null, 'notes' => null,
        'ingredients' => [['ingredient_id' => $purchase->ingredient_id, 'quantity' => '1', 'unit' => 'kg']],
        'request_key' => (string) Str::uuid(),
    ]);
    $product = app(SaveProductProfile::class)->create([
        'recipe_id' => $version->recipe_id, 'active' => true, 'reference_order_quantity' => '10',
        'components' => [], 'request_key' => (string) Str::uuid(),
    ]);
    if ($priced) {
        app(ChangeProductPrice::class)->change($product, [
            'mode' => 'manual', 'price' => '25.00', 'confirmed' => true, 'request_key' => (string) Str::uuid(),
        ]);
    }

    return [$product->fresh(), $version, $purchase];
}

function orderInput(Product $first, array $overrides = []): array
{
    return array_replace([
        'customer_name' => '  Ana   López  ',
        'lines' => [['product_id' => $first->id, 'quantity' => '2', 'agreed_price' => '20.00']],
        'delivery_date' => '2026-09-20', 'delivery_time' => '15:30', 'notes' => 'Dejar en recepción.',
        'advance' => '10.00', 'request_key' => (string) Str::uuid(),
    ], $overrides);
}

it('captures multiple lines with exact agreed-price and current-cost snapshots', function (): void {
    [$first, $version, $purchase] = orderProduct('Pan de canela');
    [$second] = orderProduct('Pan de guayaba');
    $input = orderInput($first, ['lines' => [
        ['product_id' => $first->id, 'quantity' => '2', 'agreed_price' => '20.00'],
        ['product_id' => $second->id, 'quantity' => '3', 'agreed_price' => '30.50'],
    ]]);
    $order = app(SaveOrder::class)->save($input);

    expect($order->customer_name)->toBe('Ana López')
        ->and($order->total_minor)->toBe('13150')
        ->and($order->paid_minor)->toBe('1000')
        ->and($order->balance_minor)->toBe('12150')
        ->and($order->fulfillment_state)->toBe('confirmed')
        ->and($order->payment_state)->toBe('partial')
        ->and($order->lines)->toHaveCount(2)
        ->and($order->lines->first()->product_price_id)->toBeNull()
        ->and($order->lines->first()->recipe_version_id)->toBe($version->id)
        ->and($order->lines->first()->attributable_unit_cost_micros)->toBe('4200000')
        ->and($order->lines->first()->attributable_line_cost_micros)->toBe('8400000')
        ->and($order->payments)->toHaveCount(1)
        ->and($purchase->fresh()->normalized_unit_cost_micros)->toBe('42000');
});

it('derives pending, partial and paid states with an append-only advance fact', function (): void {
    [$product] = orderProduct('Estados de pago');
    $service = app(SaveOrder::class);
    $pending = $service->save(orderInput($product, ['advance' => '0.00']));
    $partial = $service->save(orderInput($product, ['advance' => '10.00']));
    $paid = $service->save(orderInput($product, ['advance' => '40.00']));

    expect($pending->payment_state)->toBe('pending')->and($partial->payment_state)->toBe('partial')->and($paid->payment_state)->toBe('paid')
        ->and(OrderPayment::where('order_id', $pending->id)->count())->toBe(0)
        ->and(OrderPayment::where('order_id', $partial->id)->count())->toBe(1)
        ->and(OrderPayment::where('order_id', $paid->id)->sole()->kind)->toBe('advance')
        ->and(fn () => $paid->payments()->sole()->update(['amount_minor' => 1]))->toThrow(\LogicException::class)
        ->and(fn () => $service->save(orderInput($product, ['advance' => '40.01'])))->toThrow(ValidationException::class);
});

it('rechecks active eligibility atomically and rejects duplicate lines', function (): void {
    [$product] = orderProduct('Producto que se desactiva');
    $product->update(['active' => false]);
    expect(fn () => app(SaveOrder::class)->save(orderInput($product)))->toThrow(ValidationException::class);
    expect(Order::count())->toBe(0)->and(OrderLine::count())->toBe(0);

    [$active] = orderProduct('Producto duplicado');
    $duplicate = orderInput($active, ['lines' => [
        ['product_id' => $active->id, 'quantity' => '1', 'agreed_price' => '20.00'],
        ['product_id' => $active->id, 'quantity' => '2', 'agreed_price' => '20.00'],
    ]]);
    expect(fn () => app(SaveOrder::class)->save($duplicate))->toThrow(ValidationException::class);
    expect(Order::count())->toBe(0);
});

it('keeps incomplete cost null and preserves snapshots after later changes', function (): void {
    $ingredient = Ingredient::create(['name' => 'Canela sin precio', 'name_key' => hash('sha256', 'canela-sin-precio'), 'canonical_unit' => 'g']);
    $version = app(SaveRecipe::class)->save([
        'name' => 'Producto incompleto', 'expected_yield' => '10', 'instructions' => null, 'notes' => null,
        'ingredients' => [['ingredient_id' => $ingredient->id, 'quantity' => '10', 'unit' => 'g']],
        'request_key' => (string) Str::uuid(),
    ]);
    $product = app(SaveProductProfile::class)->create([
        'recipe_id' => $version->recipe_id, 'active' => true, 'reference_order_quantity' => '10',
        'components' => [], 'request_key' => (string) Str::uuid(),
    ]);
    app(ChangeProductPrice::class)->change($product, ['mode' => 'manual', 'price' => '20.00', 'confirmed' => true, 'request_key' => (string) Str::uuid()]);
    $order = app(SaveOrder::class)->save(orderInput($product, ['advance' => '0']));
    $line = $order->lines->sole();
    expect($line->cost_complete)->toBeFalse()->and($line->attributable_unit_cost_micros)->toBeNull()->and($line->attributable_line_cost_micros)->toBeNull();

    app(ChangeProductPrice::class)->change($product, ['mode' => 'manual', 'price' => '99.00', 'confirmed' => true, 'request_key' => (string) Str::uuid()]);
    expect($line->fresh()->agreed_unit_price_minor)->toBe('2000')->and($line->fresh()->attributable_line_cost_micros)->toBeNull();
    $this->get(route('orders.show', $order))->assertInertia(fn ($page) => $page->component('Orders/Show')->where('order.profit.complete', false));
});

it('replays an identical request and rejects reuse with a different normalized payload', function (): void {
    [$product] = orderProduct('Pedido idempotente');
    $input = orderInput($product);
    $first = app(SaveOrder::class)->save($input);
    $replay = app(SaveOrder::class)->save($input);
    expect($replay->id)->toBe($first->id)->and(Order::count())->toBe(1)->and(OrderLine::count())->toBe(1)->and(OrderPayment::count())->toBe(1);

    expect(fn () => app(SaveOrder::class)->save(array_replace($input, ['customer_name' => 'Otra persona'])))->toThrow(ValidationException::class);
    expect(Order::count())->toBe(1);
});

it('rejects invalid quantities and signed-64-bit revenue overflow before persistence', function (): void {
    [$product] = orderProduct('Pedido exacto');
    expect(fn () => app(SaveOrder::class)->save(orderInput($product, ['lines' => [['product_id' => $product->id, 'quantity' => '1.5', 'agreed_price' => '20.00']]])))->toThrow(ValidationException::class);
    $overflow = orderInput($product, ['lines' => [['product_id' => $product->id, 'quantity' => '999999999', 'agreed_price' => '9999999999999.99']]]);
    expect(fn () => app(SaveOrder::class)->save($overflow))->toThrow(ValidationException::class);
    expect(Order::count())->toBe(0);
});

it('renders the Orders index and create surfaces', function (): void {
    [$product] = orderProduct('Superficie de pedidos');
    $this->get(route('orders.index'))->assertInertia(fn ($page) => $page->component('Orders/Index')->has('orders'));
    $this->get(route('orders.create'))->assertInertia(fn ($page) => $page->component('Orders/Create')->has('products', 1)->where('products.0.name', 'Superficie de pedidos'));
    $order = app(SaveOrder::class)->save(orderInput($product));
    $this->get(route('orders.show', $order))->assertInertia(fn ($page) => $page->component('Orders/Show')->where('order.customer_name', 'Ana López')->where('order.payment_state', 'partial'));
});
