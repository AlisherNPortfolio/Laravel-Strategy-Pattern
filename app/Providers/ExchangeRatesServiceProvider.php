<?php

namespace App\Providers;

use App\Strategy\API\ExchangeRatesApiIO;
use App\Strategy\API\FixerIO;
use App\Strategy\Base\ExchangeRatesService;
use Illuminate\Support\ServiceProvider;

class ExchangeRatesServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(ExchangeRatesService::class, function ($app) {
            if (config('services.exchange-rates-driver') === 'exchangeratesapiio') {
                return new ExchangeRatesApiIO();
            }

            if (config('services.exchange-rates-driver') === 'fixerio') {
                return new FixerIO();
            }

            throw new \Exception("Exchange rates uchun noto'g'ri driver turi berilgan!");
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
