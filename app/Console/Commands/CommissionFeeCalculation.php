<?php

namespace App\Console\Commands;

use App\Services\DepositeService;
use App\Services\withdrawService;
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

    protected array $test = [];
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';


    public function __construct(protected DepositeService $depositeService, protected withdrawService $withdrawService)
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
                    $this->info($this->depositeService->depositeCalculation($item, $depositeCommissionFee, $this->test));
                    break;
                case "withdraw":
                    $this->info(
                        $this->withdrawService
                            ->withdrawCalculation($item, $withDrawCommissionFee, $businessCommissionFee, $this->userDetailsForWeek)
                    );
                    break;
            }
        }
        return true;
    }
}
