<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;
use App\Models\ChPrice;
use Illuminate\Console\Command;

class Ch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '春秋航空';

    /**
     * Create a new command instance.
     *
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $from = '邯郸';
        $to = '厦门';
        $date = '2020-01-20';

        $uri = 'https://flights.ch.com/Flights/MinPriceTrends';
        $client = new Client();
        $response = $client->request('POST', $uri, [
            'form_params' => [
                'Currency'       => 0,
                'DepartureDate'  => $date,
                'IsShowTaxprice' => false,
                'Departure'      => $from,
                'Arrival'        => $to,
                'SType'          => 130,
                'IsIJFlight'     => false,
                'Days'           => 7,
                'IfRet'          => false,
                'ActId'          => 0,
                'IsReturn'       => false,
                'IsUM'           => false,
            ],
        ]);
        $obj = json_decode($response->getBody()->getContents());
        $now = time();
        $nowFormat = date('Y-m-d H:i:s');
        if (is_array($obj->PriceTrends)) {
            foreach ($obj->PriceTrends as $trend) {
                if ($trend->Price === null) {
                    continue;
                }

                $priceModel = ChPrice::where([
                    'status' => ChPrice::STATUS_NEW,
                    'from'   => $from,
                    'to'     => $to,
                    'date'   => $trend->Date,
                ])->orderBy('id', 'desc')->first();

                if (is_null($priceModel)) {
                    $priceModel = ChPrice::create([
                        'from'      => $from,
                        'to'        => $to,
                        'date'      => $trend->Date,
                        'price'     => $trend->Price,
                        'heartbeat' => $now,
                    ]);
                }

                if ($trend->Price != $priceModel->price) {
                    $priceModel->status = ChPrice::STATUS_HISTORY;
                    $priceModel->save();

                    ChPrice::create([
                        'from'      => $from,
                        'to'        => $to,
                        'date'      => $trend->Date,
                        'price'     => $trend->Price,
                        'heartbeat' => $now,
                    ]);
                } else {
                    $priceModel->heartbeat = $now;
                    $priceModel->save();
                }
                $this->output->writeln(" $nowFormat {$trend->Date} {$trend->Price}");
            }
        }
    }
}
