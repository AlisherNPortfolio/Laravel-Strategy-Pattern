<?php

namespace App\Strategy\API;

use App\Strategy\Base\ExchangeRatesService;

class FixerIO implements ExchangeRatesService
{
    public function getRate(string $from, string $to): float
    {
        $rate = 15.0;
        return $rate;
    }
}
