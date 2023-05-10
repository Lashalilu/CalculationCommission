<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Console\Commands\CommissionFeeCalculation;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;

class CommissionFeeCalculationTest extends TestCase
{
    public function testCommission()
    {
        Storage::shouldReceive('path')->with('public/input.csv')->andReturn('public/input.csv');

        $data = [
            [
                'date' => '2014-12-31',
                'userId' => 4,
                'userType' => 'private',
                'transactionType' => 'withdraw',
                'amount' => 1200.00,
                'currency' => 'EUR',
            ],
        ];
        $fileData = [];
        foreach ($data as $item) {
            $fileData[] = implode(',', array_values($item));
        }
        $fileContent = implode("\n", $fileData);
        $expectedRow = $data[0];

        Storage::shouldReceive('parse')->with($expectedRow['date'])->andReturn(Carbon::parse($expectedRow['date']));

        $expectedAmount = 1200.00;

        ob_start();
        $this->withoutExceptionHandling();
        Artisan::call('commission-fee-calculation app/public/input.csv');
        $output = Artisan::output();
        ob_end_clean();

        $this->assertEquals(\config("DetailsForCommissionFeeCalculation.resultForTest"), $output);

    }

}
