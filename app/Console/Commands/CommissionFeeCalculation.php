<?php

namespace App\Console\Commands;

use App\Services\DepositeService;
use App\Services\WithdrawService;
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
    protected $signature = 'commission-fee-calculation';

    protected array $userDetailsForWeek = [];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    public function __construct(protected DepositeService $depositeService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $file_path = Storage::path('public/input.csv');
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

        $weekCountForUserId = [];
        $userDetailsForWeek = [];
        foreach ($data as $item) {
            switch ($item['transactionType']) {
                case "deposit":
                    $this->info(sprintf('%0.2f', $this->depositeService->depositeCalculation($item)));
                    break;
                case "withdraw":
                    switch ($item['userType']) {
                        case "private":

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
                                $item['amount'] = $this->convertCurrency($item['amount'], $item['currency']);

                                $item['amount'] = number_format((float)$item['amount'], 1, '.', '');

                            }

                            if ($userDetailsForWeek[$item['userId']]['count'] == 1) {
                                if ($item['amount'] > 1000) {
                                    $this->info(sprintf('%0.2f', ($item['amount'] - 1000) * 0.003));
                                    $userDetailsForWeek[$item['userId']]['limit'] = 0;
                                } else {
                                    $this->info(0.00);
                                    $userDetailsForWeek[$item['userId']]['limit'] = 1000 - $item['amount'];

                                }
                            } elseif ($userDetailsForWeek[$item['userId']]['count'] <= 3) {
                                $this->info((sprintf('%0.2f', $item['amount'] - $userDetailsForWeek[$item['userId']]['limit']) * 0.003));

                                // check limits
                                if ($item['amount'] < $userDetailsForWeek[$item['userId']]['limit']) {
                                    $userDetailsForWeek[$item['userId']] = [
                                        'limit' => $userDetailsForWeek[$item['userId']]['limit'] - $item['amount']
                                    ];
                                } else {
                                    $userDetailsForWeek[$item['userId']]['limit'] = 0;
                                }
                            } else {

                                $this->info(sprintf('%0.2f', ($item['amount'] - $userDetailsForWeek[$item['userId']]['limit']) * 0.003));
                            }

                            break;
                        case "business":
                            $this->info(sprintf('%0.2f', $item['amount'] * 0.005));
                            break;
                    }
                    break;
            }
        }
        return true;
    }

    private function convertCurrency($amount, $currency): float
    {
        $values = Http::get("https://developers.paysera.com/tasks/api/currency-exchange-rates")->json();

        return (float)$amount / (float)$values['rates'][$currency];

    }
}
