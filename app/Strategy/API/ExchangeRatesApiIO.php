<?php

namespace App\Strategy\API;

use App\Strategy\Base\ExchangeRatesService;

class ExchangeRatesApiIO implements ExchangeRatesService
{
    public function getRate(string $from, string $to): float
    {
        $rate = 10.0;
        return $rate;
    }
}
