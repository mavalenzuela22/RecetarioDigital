<?php

namespace App\Http\Controllers;

use App\Services\OperationalSummary;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(Request $request, OperationalSummary $summary): Response
    {
        return Inertia::render('Home', [
            ...$summary->today(),
            'productionUrl' => route('production.index'),
            'orderUrl' => route('orders.create'),
            'purchaseUrl' => route('purchases.create'),
            'recipeUrl' => route('recipes.index'),
            'productUrl' => route('products.index'),
            'isAdmin' => $request->user()?->is_admin === true,
            'accessUrl' => route('access.index'),
        ]);
    }
}
