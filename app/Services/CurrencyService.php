<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CurrencyService
{
    public function convertCurrency($amount, $currency): float
    {
        $values = Http::get(\config("DetailsForCommissionFeeCalculation.currencyDomain"))->json();

        return (float)$amount / (float)$values['rates'][$currency];

    }

    public function eurToNeededCurrency($amount, $currency): float
    {
        $values = Http::get(\config("DetailsForCommissionFeeCalculation.currencyDomain"))->json();

        return sprintf('%0.2f', (float)$amount * (float)$values['rates'][$currency]);

    }
}
