<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Models\Product;
use App\Services\ProductCosting;
use App\Services\SaveOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(): Response
    {
        $orders = Order::with('lines')->orderByDesc('delivery_date')->orderByDesc('delivery_time')->orderByDesc('id')->get()->map(fn (Order $order): array => [
            'id' => $order->id,
            'customer_name' => $order->customer_name,
            'delivery_date' => $order->delivery_date,
            'delivery_time' => substr($order->delivery_time, 0, 5),
            'line_count' => $order->lines->count(),
            'total_minor' => $order->total_minor,
            'balance_minor' => $order->balance_minor,
            'fulfillment_state' => $order->fulfillment_state,
            'payment_state' => $order->payment_state,
            'url' => route('orders.show', $order),
        ]);

        return Inertia::render('Orders/Index', ['orders' => $orders, 'createUrl' => route('orders.create')]);
    }

    public function create(ProductCosting $costing): Response
    {
        $products = Product::with(['recipe.latestVersion', 'latestProfile.components', 'latestPrice'])
            ->where('active', true)->get()->sortBy(fn (Product $product): string => $this->productName($product))->values()->map(function (Product $product) use ($costing): array {
                $current = $costing->current($product);

                return [
                    'id' => $product->id,
                    'name' => $this->productName($product),
                    'sale_unit' => $product->sale_unit,
                    'current_price_minor' => $current['current_price_minor'],
                    'cost_complete' => $current['complete'],
                ];
            });

        return Inertia::render('Orders/Create', [
            'products' => $products,
            'storeUrl' => route('orders.store'),
            'indexUrl' => route('orders.index'),
            'requestKey' => (string) Str::uuid(),
            'defaultDate' => now()->format('Y-m-d'),
            'defaultTime' => now()->format('H:i'),
        ]);
    }

    public function store(StoreOrderRequest $request, SaveOrder $saver): RedirectResponse
    {
        $order = $saver->save($request->validated());

        return to_route('orders.show', $order, 303)->with('success', 'Pedido registrado.');
    }

    public function show(Request $request, Order $order): Response
    {
        $order->load(['lines', 'payments']);
        $allCostsComplete = $order->lines->every(fn ($line): bool => $line->cost_complete);
        $totalCost = null;
        $profit = null;
        if ($allCostsComplete) {
            $totalCost = 0;
            foreach ($order->lines as $line) {
                $totalCost = ProductCosting::safeAdd($totalCost, (int) $line->attributable_line_cost_micros, 'La ganancia del pedido excede el límite exacto permitido.');
            }
            $profit = ProductCosting::safeAdd(ProductCosting::minorToMicros((int) $order->total_minor, 'El ingreso del pedido excede el límite exacto permitido.'), -$totalCost, 'La ganancia del pedido excede el límite exacto permitido.');
        }

        return Inertia::render('Orders/Show', [
            'order' => [
                'id' => $order->id,
                'customer_name' => $order->customer_name,
                'delivery_date' => $order->delivery_date,
                'delivery_time' => substr($order->delivery_time, 0, 5),
                'notes' => $order->notes,
                'fulfillment_state' => $order->fulfillment_state,
                'payment_state' => $order->payment_state,
                'total_minor' => $order->total_minor,
                'paid_minor' => $order->paid_minor,
                'balance_minor' => $order->balance_minor,
                'lines' => $order->lines->map(fn ($line): array => [
                    'id' => $line->id,
                    'product_name' => $line->product_name,
                    'quantity' => $line->quantity,
                    'sale_unit' => $line->sale_unit,
                    'agreed_unit_price_minor' => $line->agreed_unit_price_minor,
                    'line_revenue_minor' => $line->line_revenue_minor,
                    'attributable_unit_cost_micros' => $line->attributable_unit_cost_micros,
                    'attributable_line_cost_micros' => $line->attributable_line_cost_micros,
                    'cost_complete' => $line->cost_complete,
                ])->values(),
                'profit' => [
                    'complete' => $allCostsComplete,
                    'total_cost_micros' => $totalCost === null ? null : (string) $totalCost,
                    'profit_micros' => $profit === null ? null : (string) $profit,
                ],
            ],
            'success' => $request->session()->get('success'),
            'indexUrl' => route('orders.index'),
        ]);
    }

    private function productName(Product $product): string
    {
        return $product->recipe?->latestVersion?->name ?? $product->recipe?->name ?? 'Producto';
    }
}
