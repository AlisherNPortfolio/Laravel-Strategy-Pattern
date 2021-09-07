<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Strategy\Base\ExchangeRatesService;
use Illuminate\Http\JsonResponse;

class CurrencyController extends Controller
{
    public function __invoke(Request $request, ExchangeRatesService $exchangeRatesService): JsonResponse
    {
        $rate = $exchangeRatesService->getRate(
            $request->from,
            $request->to,
        );

        return response()->json(['rate' => $rate]);
    }
}
