<?php

namespace App\Console\Commands;

use App\Enums\CheckValueType;;
use App\Models\Settings;
use Carbon\Carbon;
use Illuminate\Console\Command;
use DB;
use App\Models\Currency;
use App\Models\CurrencyHistory;
use Illuminate\Support\Str;
use App\Mail\currencyRatesImportEmail;


class currencyRatesImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:currencyRatesImport';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command:currencyRatesImport description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $searchWebUrl = config('app.currency_rates_import_url');

        $siteName = Settings::getValue('site_name', CheckValueType::cvtString, '');
        $supportSignature = 'Yours truly, ' . $siteName . ' support';
        $myEmail = 'nilovsergey@yahoo.com';
        $additiveVars = [
            'support_signature' => $supportSignature,
        ];
        $ccEmail= 'nilov@softreactor.com';
        $title        = 'Currency rates import run at ' . $siteName;
        $baseCurrencyCode       = Settings::getValue('base_currency', CheckValueType::cvtString, '');

        if( empty($baseCurrencyCode) ) {
            echo "Main currency is not set.  Check Settings page ! \n";
            \Mail::to($myEmail)->cc([$ccEmail])->send(new currencyRatesImportEmail($title .' with errors ', false, 'Main currency is not set.  Check Settings page !', $additiveVars));
            return -1;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $searchWebUrl . $baseCurrencyCode);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json","charset=utf-8"));
        $resp = curl_exec($ch);

        if (false === $resp) {
            $err = curl_error($ch);
            curl_close($ch);

            return response()->json([
                'message'       => 'Error on remote server : ' . $err,
            ], 500);
        }
        curl_close($ch);
        $currencyRateRowData = json_decode($resp)->rates;
        $currencyRateRows= $currencyRateRowData;
        if( gettype($currencyRateRowData) === 'string' ) {
            $currencyRateRows = json_decode($currencyRateRowData, true);
        }
        if( gettype($currencyRateRowData) === 'object' ) {
            $currencyRateRows = objectIntoArray($currencyRateRowData, true);
        }
        $operation_date = Carbon::now(config('app.timezone'))->format('Y-m-d' );

        if(empty($operation_date)) {
            abort(403, 'Operation Date is not set.');
        }
        try {
            $newCurrencyAdded= 0;
            $newCurrencyRateAdded= 0;
            DB::beginTransaction();
            foreach ($currencyRateRows as $currencyRateRowCharCode => $currencyRateRowValue) { // all currency rate rows
                $currency = Currency
                    ::getByCharCode($currencyRateRowCharCode)
                    ->select('id')
                    ->first();
                if(empty($currency)) { // add new currency
                    $maxOrdering            = Currency::max('ordering');
                    $currency               = new Currency();
                    $currency->name         = 'currency name created ' . Carbon::now(config('app.timezone'));
                    $currency->num_code     = $this->getUnused3NumCode();
                    $currency->ordering     = $maxOrdering + 1;
                    $currency->char_code    = $currencyRateRowCharCode;
                    $currency->save();
                    $newCurrencyAdded++;
                } // if(empty($currency)) { // add new currency

                $currencyHistory = CurrencyHistory
                    ::getByDay($operation_date)
                    ->getByCurrencyId($currency->id)
                    ->first();
                if(!empty($currencyHistory)) continue;

                $currencyHistory               = new CurrencyHistory();
                $currencyHistory->currency_id  = $currency->id;
                $currencyHistory->day          = $operation_date;
                $currencyHistory->nominal      =  1;
                $currencyHistory->value        = str_replace(',','.',(string)$currencyRateRowValue);
                $currencyHistory->save();
                $newCurrencyRateAdded++;
            } // foreach ($currencyRateRows as $currencyRateRowCharCode => $currencyRateRowValue) { // all currency rate rows

            $successMsg= "New currencies added  : " . $newCurrencyAdded . ', new currency rates added : ' . $newCurrencyRateAdded . "\n";
            echo $successMsg;
            \Mail::to($myEmail)->send(new currencyRatesImportEmail($title .' with success ', true, $successMsg, $additiveVars));

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['message' => $e->getMessage(), 'tag' => null], 500);
        }

        return 0;
    }

    private function getUnused3NumCode() {
        while( true ) {
            $numCode= strtolower(Str::random(3));
            $currency = Currency::getByNumCode($numCode)->select('id')->first();
            if($currency===null) {
                return $numCode;
            }
        }
        return false;
    }
}
