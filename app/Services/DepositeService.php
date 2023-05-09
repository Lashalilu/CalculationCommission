<?php

namespace App\Services;


class  DepositeService
{
    public function depositeCalculation($data): float
    {
        return $data['amount'] * 0.0003;
    }
}

//$weekOperation['totalAmount'] += $amountInEur;
//
//if ($weekOperation['totalAmount'] > 1000) {
//    $chargeAmount = ($weekOperation['totalAmount'] - 1000);
//
//    if (!$isEuro) {
//        $chargeAmount = $exchangeRateService->convertFromEur(
//            $chargeAmount,
//            $exchangeRates[$currency]
//        );
//    }
//} else {
//    $chargeAmount = 0;
//}
//}
//
//$weekOperation['operationCount']++;
//
//$userWithdraws[$userId]['operationDates'][$startOfWeek] = $weekOperation;
//}
