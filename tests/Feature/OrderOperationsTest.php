<?php

use App\Models\Order;
use App\Models\OrderFulfillmentEvent;
use App\Models\OrderPayment;
use App\Models\Product;
use App\Services\ChangeProductPrice;
use App\Services\RecordIngredientPurchase;
use App\Services\RecordOrderPayment;
use App\Services\SaveOrder;
use App\Services\SaveProductProfile;
use App\Services\SaveRecipe;
use App\Services\TransitionOrderFulfillment;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(ValidateCsrfToken::class);
});

function operationsProduct(string $name = 'Producto de operaciones'): Product
{
    $purchase = app(RecordIngredientPurchase::class)->record([
        'ingredient_name' => $name.' ingrediente', 'presentation' => 'Bolsa de 1 kg',
        'purchase_quantity' => '1', 'purchase_unit' => 'kg', 'total_paid' => '42.00',
        'purchased_on' => '2026-09-18', 'store' => null, 'note' => null,
        'request_key' => (string) Str::uuid(),
    ]);
    $version = app(SaveRecipe::class)->save([
        'name' => $name, 'expected_yield' => '10', 'instructions' => null, 'notes' => null,
        'ingredients' => [['ingredient_id' => $purchase->ingredient_id, 'quantity' => '1', 'unit' => 'kg']],
        'request_key' => (string) Str::uuid(),
    ]);
    $product = app(SaveProductProfile::class)->create([
        'recipe_id' => $version->recipe_id, 'active' => true, 'reference_order_quantity' => '10',
        'components' => [], 'request_key' => (string) Str::uuid(),
    ]);
    app(ChangeProductPrice::class)->change($product, [
        'mode' => 'manual', 'price' => '25.00', 'confirmed' => true, 'request_key' => (string) Str::uuid(),
    ]);

    return $product->fresh();
}

function operationsOrder(Product $product, array $overrides = []): Order
{
    return app(SaveOrder::class)->save(array_replace([
        'customer_name' => 'Ana Operaciones',
        'lines' => [['product_id' => $product->id, 'quantity' => '2', 'agreed_price' => '25.00']],
        'delivery_date' => '2026-09-22', 'delivery_time' => '15:30', 'notes' => null,
        'advance' => '10.00', 'request_key' => (string) Str::uuid(),
    ], $overrides));
}

function operationRequest(): string
{
    return (string) Str::uuid();
}

it('records exact append-only collections with local date and idempotent replay', function (): void {
    $order = operationsOrder(operationsProduct());
    $key = operationRequest();
    $recorder = app(RecordOrderPayment::class);
    $payment = $recorder->record($order, ['amount' => '15.25', 'payment_date' => '2026-09-23', 'request_key' => $key]);

    expect($payment->fresh()->amount_minor)->toBe('1525')
        ->and($payment->fresh()->local_payment_date)->toBe('2026-09-23')
        ->and($order->fresh()->paid_minor)->toBe('2525')
        ->and($order->fresh()->balance_minor)->toBe('2475')
        ->and($order->fresh()->payment_state)->toBe('partial')
        ->and($recorder->record($order->fresh(), ['amount' => '15.25', 'payment_date' => '2026-09-23', 'request_key' => $key])->id)->toBe($payment->id)
        ->and(OrderPayment::where('order_id', $order->id)->count())->toBe(2);

    expect(fn () => $recorder->record($order->fresh(), ['amount' => '16.25', 'payment_date' => '2026-09-23', 'request_key' => $key]))->toThrow(ValidationException::class)
        ->and(fn () => $payment->fresh()->update(['amount_minor' => '1']))->toThrow(\LogicException::class)
        ->and(fn () => $payment->fresh()->delete())->toThrow(\LogicException::class);
});

it('sets paid on an exact final collection and rejects invalid amounts atomically', function (): void {
    $product = operationsProduct('Cobro final');
    $order = operationsOrder($product);
    $recorder = app(RecordOrderPayment::class);
    $recorder->record($order, ['amount' => '40.00', 'payment_date' => '2026-09-23', 'request_key' => operationRequest()]);
    expect($order->fresh()->paid_minor)->toBe('5000')->and($order->fresh()->balance_minor)->toBe('0')->and($order->fresh()->payment_state)->toBe('paid');

    $second = operationsOrder($product, ['advance' => '0.00']);
    expect(fn () => $recorder->record($second, ['amount' => '51.00', 'payment_date' => '2026-09-23', 'request_key' => operationRequest()]))->toThrow(ValidationException::class)
        ->and(fn () => $recorder->record($second, ['amount' => '0.00', 'payment_date' => '2026-09-23', 'request_key' => operationRequest()]))->toThrow(ValidationException::class)
        ->and(fn () => $recorder->record($second, ['amount' => '-1.00', 'payment_date' => '2026-09-23', 'request_key' => operationRequest()]))->toThrow(ValidationException::class);
    expect($second->fresh()->paid_minor)->toBe('0')->and($second->fresh()->balance_minor)->toBe('5000')->and(OrderPayment::where('order_id', $second->id)->count())->toBe(0);
});

it('allows collection after delivery without changing fulfillment', function (): void {
    $order = operationsOrder(operationsProduct('Cobro después de entrega'));
    $transition = app(TransitionOrderFulfillment::class);
    $transition->transition($order, 'delivered', ['request_key' => operationRequest()]);
    app(RecordOrderPayment::class)->record($order->fresh(), ['amount' => '40.00', 'payment_date' => '2026-09-24', 'request_key' => operationRequest()]);

    expect($order->fresh()->fulfillment_state)->toBe('delivered')->and($order->fresh()->payment_state)->toBe('paid')->and(OrderFulfillmentEvent::where('order_id', $order->id)->count())->toBe(1);
});

it('transitions confirmed, in preparation and ready orders with immutable idempotent events', function (): void {
    $transition = app(TransitionOrderFulfillment::class);
    foreach (['confirmed', 'in_preparation', 'ready'] as $state) {
        $order = operationsOrder(operationsProduct('Entrega '.$state));
        $order->update(['fulfillment_state' => $state]);
        $key = operationRequest();
        $transition->transition($order, 'delivered', ['request_key' => $key]);
        $transition->transition($order->fresh(), 'delivered', ['request_key' => $key]);
        expect($order->fresh()->fulfillment_state)->toBe('delivered')->and(OrderFulfillmentEvent::where('order_id', $order->id)->count())->toBe(1);
    }

    $event = OrderFulfillmentEvent::first();
    expect(fn () => $event->update(['to_state' => 'cancelled']))->toThrow(\LogicException::class)
        ->and(fn () => $event->delete())->toThrow(\LogicException::class);
});

it('rejects forbidden fulfillment transitions and preserves cancellation history without refund', function (): void {
    $transition = app(TransitionOrderFulfillment::class);
    $order = operationsOrder(operationsProduct('Pedido cancelado'));
    $line = $order->lines->sole();
    $paymentCount = $order->payments->count();
    $transition->transition($order, 'cancelled', ['request_key' => operationRequest()]);
    $cancelled = $order->fresh(['lines', 'payments']);

    expect($cancelled->paid_minor)->toBe('1000')->and($cancelled->balance_minor)->toBe('4000')->and($cancelled->payment_state)->toBe('partial')
        ->and($cancelled->lines->sole()->agreed_unit_price_minor)->toBe($line->agreed_unit_price_minor)
        ->and($cancelled->payments)->toHaveCount($paymentCount)
        ->and(OrderPayment::where('order_id', $order->id)->where('kind', 'collection')->count())->toBe(0)
        ->and(fn () => app(RecordOrderPayment::class)->record($cancelled, ['amount' => '1.00', 'payment_date' => '2026-09-24', 'request_key' => operationRequest()]))->toThrow(ValidationException::class)
        ->and(fn () => $transition->transition($cancelled, 'delivered', ['request_key' => operationRequest()]))->toThrow(ValidationException::class);

    $delivered = operationsOrder(operationsProduct('Pedido entregado'));
    $transition->transition($delivered, 'delivered', ['request_key' => operationRequest()]);
    expect(fn () => $transition->transition($delivered->fresh(), 'cancelled', ['request_key' => operationRequest()]))->toThrow(ValidationException::class);
});

it('rejects collection on paid orders and exposes operational Inertia facts', function (): void {
    $product = operationsProduct('Superficie de cobro');
    $order = operationsOrder($product, ['advance' => '50.00']);
    expect(fn () => app(RecordOrderPayment::class)->record($order, ['amount' => '1.00', 'payment_date' => '2026-09-24', 'request_key' => operationRequest()]))->toThrow(ValidationException::class);

    $this->get(route('orders.show', $order))->assertInertia(fn ($page) => $page
        ->component('Orders/Show')
        ->where('order.payment_state', 'paid')
        ->has('order.payments', 1)
        ->has('paymentUrl')
        ->has('deliveryUrl')
        ->has('cancellationUrl')
        ->has('paymentRequestKey')
        ->has('deliveryRequestKey')
        ->has('cancellationRequestKey'));
});
