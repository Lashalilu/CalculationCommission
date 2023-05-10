<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class CommissionFeeCalculation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'commission-fee-calculation {file}';

    protected array $userDetailsForWeek = [];


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';


    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $withDrawCommissionFee = \config("DetailsForCommissionFeeCalculation.withdrawCommissionFee");
        $businessCommissionFee = \config("DetailsForCommissionFeeCalculation.businessCommissionFee");
        $depositeCommissionFee = \config("DetailsForCommissionFeeCalculation.depositeCommissionFee");


        $takeFile = $this->argument('file');
        $file_path = storage_path($takeFile);
        $file = fopen($file_path, 'r');

        $data = [];

        while (($row = fgetcsv($file)) !== false) {
            $data[] = [
                'date' => $row[0],
                'userId' => $row[1],
                'userType' => $row[2],
                'transactionType' => $row[3],
                'amount' => $row[4],
                'currency' => $row[5]
            ];
        }

        fclose($file);

        foreach ($data as $item) {
            switch ($item['transactionType']) {
                case "deposit":
                    $this->deposit($item, $depositeCommissionFee);
                    break;
                case "withdraw":
                    $this->withdraw($item, $withDrawCommissionFee, $businessCommissionFee);
                    break;
            }
        }
        return true;
    }

    public function deposit($item, $depositeCommissionFee): void
    {
        $this->info($this->eurToNeededCurrency(
            sprintf('%0.2f', $item['amount'] * $depositeCommissionFee)
            , $item['currency'])
        );
    }

    public function withdraw($item, $withDrawCommissionFee, $businessCommissionFee): void
    {
        switch ($item['userType']) {
            case "private":
                $this->withdrawPrivate($item, $withDrawCommissionFee);
                break;
            case "business":
                $this->withdrawBusiness($item, $businessCommissionFee);
                break;
        }
    }

    public function withdrawPrivate($item, $withDrawCommissionFee): void
    {

        if (isset($this->userDetailsForWeek[$item['userId']])) {

            if ($this->userDetailsForWeek[$item['userId']]['date']->isSameWeek(Carbon::parse($item['date']))) {
                $this->userDetailsForWeek[$item['userId']]['count']++;
            } else {
                $this->userDetailsForWeek[$item['userId']]['count'] = 1;
                $this->userDetailsForWeek[$item['userId']]['limit'] = 1000;
            }

            $this->userDetailsForWeek[$item['userId']]['date'] = Carbon::parse($item['date']);

        } else {
            $this->userDetailsForWeek[$item['userId']] = [
                'count' => 1,
                'limit' => 1000,
                'date' => Carbon::parse($item['date']),
            ];
        }

        if ($item['currency'] != "EUR") {
            $item['amount'] = $this->convertCurrency($item['amount'], $item['currency']);

            $item['amount'] = number_format((float)$item['amount'], 1, '.', '');

        }

        if ($this->userDetailsForWeek[$item['userId']]['count'] == 1) {
            if ($item['amount'] > 1000) {
                $this->info(
                    $this->eurToNeededCurrency(
                        sprintf('%0.2f', ($item['amount'] - 1000) * $withDrawCommissionFee), $item['currency'])
                );
                $this->userDetailsForWeek[$item['userId']]['limit'] = 0;
            } else {
                $this->info(0.00);
                $this->userDetailsForWeek[$item['userId']]['limit'] = 1000 - $item['amount'];

            }
        } elseif ($this->userDetailsForWeek[$item['userId']]['count'] <= 3) {
            $this->info(
                $this->eurToNeededCurrency(
                    (sprintf('%0.2f', $item['amount'] - $this->userDetailsForWeek[$item['userId']]['limit']) * $withDrawCommissionFee),
                    $item['currency']
                ));

            // check limits
            if ($item['amount'] < $this->userDetailsForWeek[$item['userId']]['limit']) {
                $this->userDetailsForWeek[$item['userId']] = [
                    'limit' => $this->userDetailsForWeek[$item['userId']]['limit'] - $item['amount']
                ];
            } else {
                $this->userDetailsForWeek[$item['userId']]['limit'] = 0;
            }
        } else {

            $this->info(
                $this->eurToNeededCurrency(
                    sprintf('%0.2f', ($item['amount'] - $this->userDetailsForWeek[$item['userId']]['limit']) * $withDrawCommissionFee),
                    $item['currency'])
            );
        }

    }

    public function withdrawBusiness($item, $businessCommissionFee): void
    {
        $this->info(
            $this->eurToNeededCurrency(
                sprintf('%0.2f', $item['amount'] * $businessCommissionFee),
                $item['currency'])
        );
    }

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
