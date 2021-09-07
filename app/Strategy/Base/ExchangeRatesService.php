<?php

namespace App\Strategy\Base;

interface ExchangeRatesService
{
    public function getRate(string $from, string $to): float;
}
