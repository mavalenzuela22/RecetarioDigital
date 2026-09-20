<?php

namespace App\Http\Controllers;

use App\Services\OperationalSummary;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(OperationalSummary $summary): Response
    {
        return Inertia::render('Home', [
            ...$summary->today(),
            'productionUrl' => route('production.index'),
            'orderUrl' => route('orders.create'),
            'purchaseUrl' => route('purchases.create'),
        ]);
    }
}
