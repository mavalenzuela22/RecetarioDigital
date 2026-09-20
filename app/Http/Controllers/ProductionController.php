<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductionRangeRequest;
use App\Http\Requests\TransitionOrderFulfillmentRequest;
use App\Models\Order;
use App\Services\OperationalSummary;
use App\Services\StartProduction;
use App\Services\TransitionOrderFulfillment;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProductionController extends Controller
{
    public function index(ProductionRangeRequest $request, OperationalSummary $summary): Response
    {
        $range = $summary->normalizeRange($request->input('from'), $request->input('to'));

        return Inertia::render('Production/Index', [
            ...$summary->production($range['from'], $range['to']),
            'backUrl' => route('home'),
            'productionUrl' => route('production.index'),
            'startUrl' => route('production.start'),
            'success' => $request->session()->get('success'),
        ]);
    }

    public function start(ProductionRangeRequest $request, OperationalSummary $summary, StartProduction $startProduction): RedirectResponse
    {
        $range = $summary->normalizeRange($request->input('from'), $request->input('to'));
        $startProduction->start($range['from'], $range['to']);

        return to_route('production.index', $range, 303)->with('success', 'Pedidos en preparación.');
    }

    public function ready(TransitionOrderFulfillmentRequest $request, Order $order, TransitionOrderFulfillment $transition, OperationalSummary $summary): RedirectResponse
    {
        $transition->transition($order, 'ready', $request->validated());
        $range = $summary->normalizeRange($request->input('from') ?: $order->delivery_date, $request->input('to'));

        return to_route('production.index', $range, 303)->with('success', 'Pedido listo para entregar.');
    }
}
