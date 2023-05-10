<?php

namespace App\Services;


use Illuminate\Support\Facades\Http;

class  DepositeService
{
    public function __construct(protected CurrencyService $currencyService)
    {
    }

    public function depositeCalculation($item, $depositeCommissionFee, &$test): float
    {
        $test [] = 'lasha';
        return $this->currencyService->eurToNeededCurrency(
            sprintf('%0.2f', $item['amount'] * $depositeCommissionFee)
            , $item['currency']);
    }
}
