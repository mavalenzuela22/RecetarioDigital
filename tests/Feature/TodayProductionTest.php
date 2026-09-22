<?php

use App\Models\Ingredient;
use App\Models\Order;
use App\Models\OrderFulfillmentEvent;
use App\Models\Product;
use App\Services\ChangeProductPrice;
use App\Services\OperationalSummary;
use App\Services\RecordOrderPayment;
use App\Services\RecordIngredientPurchase;
use App\Services\SaveOrder;
use App\Services\SaveProductProfile;
use App\Services\SaveRecipe;
use App\Services\StartProduction;
use App\Services\TransitionOrderFulfillment;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(ValidateCsrfToken::class);
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-19 09:00:00', 'America/Monterrey'));
});

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

function today_complete_product(string $name = 'Producto de hoy'): Product
{
    $purchase = app(RecordIngredientPurchase::class)->record([
        'ingredient_name' => $name.' ingrediente', 'presentation' => 'Bolsa de 1 kg', 'purchase_quantity' => '1', 'purchase_unit' => 'kg', 'total_paid' => '42.00', 'purchased_on' => '2026-09-18', 'store' => null, 'note' => null, 'request_key' => (string) Str::uuid(),
    ]);
    $version = app(SaveRecipe::class)->save([
        'name' => $name, 'expected_yield' => '10', 'instructions' => null, 'notes' => null, 'ingredients' => [['ingredient_id' => $purchase->ingredient_id, 'quantity' => '1', 'unit' => 'kg']], 'request_key' => (string) Str::uuid(),
    ]);
    $product = app(SaveProductProfile::class)->create(['recipe_id' => $version->recipe_id, 'active' => true, 'reference_order_quantity' => '10', 'components' => [], 'request_key' => (string) Str::uuid()]);
    app(ChangeProductPrice::class)->change($product, ['mode' => 'manual', 'price' => '25.00', 'confirmed' => true, 'request_key' => (string) Str::uuid()]);

    return $product->fresh();
}

function today_incomplete_product(string $name = 'Producto incompleto de hoy'): Product
{
    $ingredient = Ingredient::create(['name' => $name.' ingrediente', 'name_key' => hash('sha256', $name), 'canonical_unit' => 'g']);
    $version = app(SaveRecipe::class)->save([
        'name' => $name, 'expected_yield' => '10', 'instructions' => null, 'notes' => null, 'ingredients' => [['ingredient_id' => $ingredient->id, 'quantity' => '10', 'unit' => 'g']], 'request_key' => (string) Str::uuid(),
    ]);
    $product = app(SaveProductProfile::class)->create(['recipe_id' => $version->recipe_id, 'active' => true, 'reference_order_quantity' => '10', 'components' => [], 'request_key' => (string) Str::uuid()]);
    app(ChangeProductPrice::class)->change($product, ['mode' => 'manual', 'price' => '25.00', 'confirmed' => true, 'request_key' => (string) Str::uuid()]);

    return $product->fresh();
}

function today_order(Product $product, string $customer, string $date = '2026-09-19', string $quantity = '1', string $advance = '0.00'): Order
{
    return app(SaveOrder::class)->save([
        'customer_name' => $customer, 'lines' => [['product_id' => $product->id, 'quantity' => $quantity, 'agreed_price' => '25.00']], 'delivery_date' => $date, 'delivery_time' => '15:30', 'notes' => null, 'advance' => $advance, 'request_key' => (string) Str::uuid(),
    ]);
}

it('uses the configured local business date and keeps delivered debt in today', function (): void {
    $product = today_complete_product();
    $confirmed = today_order($product, 'Confirmada', quantity: '2');
    $preparing = today_order($product, 'Preparando', quantity: '3');
    $ready = today_order($product, 'Lista', quantity: '4');
    $delivered = today_order($product, 'Entregada', quantity: '5');
    $cancelled = today_order($product, 'Cancelada', quantity: '6');
    $preparing->update(['fulfillment_state' => 'in_preparation']);
    $ready->update(['fulfillment_state' => 'ready']);
    $delivered->update(['fulfillment_state' => 'delivered']);
    $cancelled->update(['fulfillment_state' => 'cancelled']);

    $summary = app(OperationalSummary::class)->today();

    expect($summary['business_date'])->toBe('2026-09-19')
        ->and($summary['production']['units_to_prepare'])->toBe('5')
        ->and($summary['deliveries'])->toHaveCount(3)
        ->and(collect($summary['collections'])->pluck('customer_name')->all())->toContain('Entregada')
        ->and($summary['money']['expected_revenue_minor'])->toBe('35000')
        ->and($summary['money']['balance_minor'])->toBe('35000')
        ->and($summary['money']['expected_revenue_label'])->toBe('$350.00 MXN')
        ->and($summary['money']['balance_label'])->toBe('$350.00 MXN')
        ->and($summary['money']['estimated_cost_label'])->toBe('$58.80 MXN')
        ->and($summary['money']['estimated_profit_label'])->toBe('$291.20 MXN')
        ->and($summary['money']['estimated_profit_micros'])->toBe('291200000')
        ->and(collect($summary['deliveries'])->pluck('customer_name')->all())->not->toContain('Cancelada');
});

it('keeps a completed fully paid day in Today with exact economics', function (): void {
    $product = today_complete_product('Completado');
    $order = today_order($product, 'Cliente completado');
    app(StartProduction::class)->start('2026-09-19', '2026-09-19');

    $transition = app(TransitionOrderFulfillment::class);
    $transition->transition($order->fresh(), 'ready', ['request_key' => (string) Str::uuid()]);
    $transition->transition($order->fresh(), 'delivered', ['request_key' => (string) Str::uuid()]);
    app(RecordOrderPayment::class)->record($order->fresh(), [
        'amount' => '25.00',
        'payment_date' => '2026-09-19',
        'request_key' => (string) Str::uuid(),
    ]);

    $summary = app(OperationalSummary::class)->today();

    expect($order->fresh()->fulfillment_state)->toBe('delivered')
        ->and($order->fresh()->payment_state)->toBe('paid')
        ->and($summary['has_orders'])->toBeTrue()
        ->and($summary['production']['groups'])->toBe([])
        ->and($summary['deliveries'])->toBe([])
        ->and($summary['collections'])->toBe([])
        ->and($summary['money']['expected_revenue_minor'])->toBe('2500')
        ->and($summary['money']['balance_minor'])->toBe('0')
        ->and($summary['money']['estimated_cost_micros'])->toBe('4200000')
        ->and($summary['money']['estimated_profit_micros'])->toBe('20800000')
        ->and($summary['money']['expected_revenue_label'])->toBe('$25.00 MXN')
        ->and($summary['money']['balance_label'])->toBe('$0.00 MXN')
        ->and($summary['money']['estimated_cost_label'])->toBe('$4.20 MXN')
        ->and($summary['money']['estimated_profit_label'])->toBe('$20.80 MXN');

    $this->get('/')->assertInertia(fn ($page) => $page
        ->component('Home')
        ->where('has_orders', true)
        ->where('production.groups', [])
        ->where('deliveries', [])
        ->where('collections', [])
        ->where('money.expected_revenue_minor', '2500')
        ->where('money.balance_minor', '0')
        ->where('money.estimated_profit_micros', '20800000'));
});

it('keeps revenue available and profit pending when a snapshot cost is incomplete', function (): void {
    $order = today_order(today_incomplete_product(), 'Sin costo');
    $summary = app(OperationalSummary::class)->today();

    expect($order->fresh()->lines->sole()->cost_complete)->toBeFalse()
        ->and($summary['money']['expected_revenue_minor'])->toBe('2500')
        ->and($summary['money']['cost_complete'])->toBeFalse()
        ->and($summary['money']['estimated_cost_micros'])->toBeNull()
        ->and($summary['money']['estimated_profit_micros'])->toBeNull()
        ->and($summary['money']['profit_pending_label'])->toBe('Ganancia pendiente de calcular.');
});

it('normalizes an inclusive production range and excludes inactive work', function (): void {
    $product = today_complete_product('Rango');
    $confirmed = today_order($product, 'Confirmado rango', '2026-09-18', '2');
    $preparing = today_order($product, 'Preparando rango', '2026-09-19', '3');
    $ready = today_order($product, 'Listo rango', '2026-09-20', '4');
    $delivered = today_order($product, 'Entregado rango', '2026-09-19', '5');
    $cancelled = today_order($product, 'Cancelado rango', '2026-09-19', '6');
    today_order($product, 'Fuera rango', '2026-09-21', '7');
    $preparing->update(['fulfillment_state' => 'in_preparation']);
    $ready->update(['fulfillment_state' => 'ready']);
    $delivered->update(['fulfillment_state' => 'delivered']);
    $cancelled->update(['fulfillment_state' => 'cancelled']);

    $summary = app(OperationalSummary::class)->production('2026-09-18', '2026-09-20');
    $orders = collect($summary['groups'])->flatMap(fn (array $group): array => $group['orders']);

    expect($summary['from'])->toBe('2026-09-18')->and($summary['to'])->toBe('2026-09-20')
        ->and($summary['groups'])->toHaveCount(1)
        ->and($summary['groups'][0]['total_units'])->toBe('9')
        ->and($summary['groups'][0]['units_to_prepare'])->toBe('5')
        ->and($summary['groups'][0]['ready_units'])->toBe('4')
        ->and($orders->pluck('customer_name')->all())->toBe(['Confirmado rango', 'Preparando rango', 'Listo rango'])
        ->and($summary['money']['expected_revenue_minor'])->toBe('22500')
        ->and($orders->pluck('product_name')->all())->toBe(['Rango', 'Rango', 'Rango']);

    expect(app(OperationalSummary::class)->production('2026-09-18')['to'])->toBe('2026-09-18')
        ->and(fn () => app(OperationalSummary::class)->normalizeRange('2026-09-20', '2026-09-19'))->toThrow(ValidationException::class);
});

it('starts preparation atomically once and appends one event per transition', function (): void {
    $product = today_complete_product('Inicio');
    $first = today_order($product, 'Primero');
    $second = today_order($product, 'Segundo');
    $already = today_order($product, 'Ya preparando');
    $already->update(['fulfillment_state' => 'in_preparation']);
    $paid = today_order($product, 'Con pago', advance: '10.00');

    $startProduction = app(StartProduction::class);
    expect($startProduction->start('2026-09-19', '2026-09-19'))->toBe(3)
        ->and($startProduction->start('2026-09-19', '2026-09-19'))->toBe(0);
    expect($first->fresh()->fulfillment_state)->toBe('in_preparation')
        ->and($second->fresh()->fulfillment_state)->toBe('in_preparation')
        ->and($paid->fresh()->paid_minor)->toBe('1000')
        ->and(OrderFulfillmentEvent::where('to_state', 'in_preparation')->count())->toBe(3)
        ->and(OrderFulfillmentEvent::where('to_state', 'in_preparation')->pluck('request_hash')->first())->toBe(hash('sha256', json_encode(['order_id' => $first->id, 'from_state' => 'confirmed', 'to_state' => 'in_preparation'], JSON_THROW_ON_ERROR)));
});

it('only marks in-preparation orders ready and preserves payment facts', function (): void {
    $product = today_complete_product('Listo');
    $order = today_order($product, 'Pedido listo', advance: '10.00');
    app(StartProduction::class)->start('2026-09-19', '2026-09-19');
    $transition = app(TransitionOrderFulfillment::class);
    $key = (string) Str::uuid();
    $transition->transition($order->fresh(), 'ready', ['request_key' => $key]);
    $transition->transition($order->fresh(), 'ready', ['request_key' => $key]);
    $other = today_order($product, 'Otro pedido');

    expect($order->fresh()->fulfillment_state)->toBe('ready')
        ->and($order->fresh()->paid_minor)->toBe('1000')
        ->and($order->fresh()->balance_minor)->toBe('1500')
        ->and(OrderFulfillmentEvent::where('order_id', $order->id)->where('to_state', 'ready')->count())->toBe(1)
        ->and(fn () => $transition->transition($other, 'ready', ['request_key' => $key]))->toThrow(ValidationException::class)
        ->and(fn () => $transition->transition($order->fresh(), 'delivered', ['request_key' => $key]))->toThrow(ValidationException::class)
        ->and(fn () => $transition->transition(today_order($product, 'No salto'), 'ready', ['request_key' => (string) Str::uuid()]))->toThrow(ValidationException::class);
});

it('marks every line of a mixed order ready through the order-level transition', function (): void {
    $firstProduct = today_complete_product('Mixto primero');
    $secondProduct = today_complete_product('Mixto segundo');
    $order = app(SaveOrder::class)->save([
        'customer_name' => 'Pedido mixto',
        'lines' => [
            ['product_id' => $firstProduct->id, 'quantity' => '2', 'agreed_price' => '25.00'],
            ['product_id' => $secondProduct->id, 'quantity' => '3', 'agreed_price' => '25.00'],
        ],
        'delivery_date' => '2026-09-19',
        'delivery_time' => '15:30',
        'notes' => null,
        'advance' => '0.00',
        'request_key' => (string) Str::uuid(),
    ]);
    app(StartProduction::class)->start('2026-09-19', '2026-09-19');

    $groups = collect(app(OperationalSummary::class)->production('2026-09-19', '2026-09-19')['groups']);
    expect($groups)->toHaveCount(2)
        ->and($groups->flatMap(fn (array $group): array => $group['orders'])->pluck('id')->unique()->all())->toBe([$order->id]);

    $this->post(route('production.ready', $order), [
        'request_key' => (string) Str::uuid(),
        'from' => '2026-09-19',
        'to' => '2026-09-19',
    ])->assertSessionHas('success', 'Pedido completo listo para entregar.');

    expect($order->fresh()->fulfillment_state)->toBe('ready')
        ->and($order->fresh()->lines)->toHaveCount(2)
        ->and(OrderFulfillmentEvent::where('order_id', $order->id)->where('to_state', 'ready')->count())->toBe(1);
});

it('exposes presentation-ready Today and Production Inertia payloads', function (): void {
    $product = today_complete_product('Payload');
    today_order($product, 'Cliente payload');

    $this->get('/')->assertInertia(fn ($page) => $page->component('Home')->where('title', 'Hoy en tu cocina')->where('money.expected_revenue_label', '$25.00 MXN')->has('productionUrl'));
    $this->get('/produccion')->assertInertia(fn ($page) => $page->component('Production/Index')->where('from', '2026-09-19')->where('to', '2026-09-19')->where('groups.0.orders.0.customer_name', 'Cliente payload')->where('groups.0.orders.0.product_name', 'Payload')->has('groups.0.orders.0.url'));
    $this->get('/produccion?from=2026-09-20&to=2026-09-19')->assertSessionHasErrors('to');
});
