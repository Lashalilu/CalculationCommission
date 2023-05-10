<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class withdrawService
{

    public function __construct(protected CurrencyService $currencyService)
    {
    }

    public function withdrawCalculation($item, $withDrawCommissionFee, $businessCommissionFee, &$userDetailsForWeek): float
    {
        return match ($item['userType']) {
          "private" => $this->withdrawPrivate($item, $withDrawCommissionFee, $userDetailsForWeek),
          "business" => $this->withdrawBusiness($item, $businessCommissionFee),
        };
    }

    public function withdrawPrivate($item, $withDrawCommissionFee, &$userDetailsForWeek): float
    {

        if (isset($userDetailsForWeek[$item['userId']])) {

            if ($userDetailsForWeek[$item['userId']]['date']->isSameWeek(Carbon::parse($item['date']))) {
                $userDetailsForWeek[$item['userId']]['count']++;
            } else {
                $userDetailsForWeek[$item['userId']]['count'] = 1;
                $userDetailsForWeek[$item['userId']]['limit'] = 1000;
            }

            $userDetailsForWeek[$item['userId']]['date'] = Carbon::parse($item['date']);

        } else {
            $userDetailsForWeek[$item['userId']] = [
                'count' => 1,
                'limit' => 1000,
                'date' => Carbon::parse($item['date']),
            ];
        }

        if ($item['currency'] != "EUR") {
            $item['amount'] = $this->currencyService->convertCurrency($item['amount'], $item['currency']);

            $item['amount'] = number_format((float)$item['amount'], 1, '.', '');

        }

        if ($userDetailsForWeek[$item['userId']]['count'] == 1) {
            if ($item['amount'] > 1000) {

                $userDetailsForWeek[$item['userId']]['limit'] = 0;

                return $this->currencyService->eurToNeededCurrency(
                    sprintf('%0.2f', ($item['amount'] - 1000) * $withDrawCommissionFee), $item['currency']
                );
            } else {
                $userDetailsForWeek[$item['userId']]['limit'] = 1000 - $item['amount'];

                return 0.00;

            }
        } elseif ($userDetailsForWeek[$item['userId']]['count'] <= 3) {

            $fee = $this->currencyService->eurToNeededCurrency(
                (sprintf('%0.2f', $item['amount'] - $userDetailsForWeek[$item['userId']]['limit']) * $withDrawCommissionFee),
                $item['currency']
            );

            // check limits
            if ($item['amount'] < $userDetailsForWeek[$item['userId']]['limit']) {
                $userDetailsForWeek[$item['userId']] = [
                    'limit' => $userDetailsForWeek[$item['userId']]['limit'] - $item['amount']
                ];

            } else {
                $userDetailsForWeek[$item['userId']]['limit'] = 0;
            }

            return $fee;
        } else {

            return $this->currencyService->eurToNeededCurrency(
                sprintf('%0.2f', ($item['amount'] - $userDetailsForWeek[$item['userId']]['limit']) * $withDrawCommissionFee),
                $item['currency']);
        }

    }

    public function withdrawBusiness($item, $businessCommissionFee): float
    {
        return $this->currencyService->eurToNeededCurrency(
            sprintf('%0.2f', $item['amount'] * $businessCommissionFee),
            $item['currency']);
    }


}
