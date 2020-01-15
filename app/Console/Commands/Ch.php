<?php

namespace App\Console\Commands;

use Exception;
use GuzzleHttp\Client;
use App\Models\ChPrice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

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
        $hd = '邯郸';
        $xm = '厦门';
        $this->handleFun($hd, $xm, '2020-01-20');
        $this->handleFun($xm, $hd, '2020-02-02');
    }

    public function handleFun($from, $to, $date)
    {
        $threeDay = date("Y-m-d", strtotime("+3 days"));
        if ($threeDay > $date) {
            $date = $threeDay;
        }

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

                // $trend->Price = 1511;
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

                    $text = "{$priceModel->price}=>{$trend->Price}";
                    $this->mail($text, $trend->Date);
                } else {
                    $priceModel->heartbeat = $now;
                    $priceModel->save();
                }
                $text = "$nowFormat {$trend->Date} {$priceModel->price} {$trend->Price}";
                $this->output->writeln($text);
            }
        }
    }

    public function mail($text, $title = 'laravel', $retry = 2)
    {
        $this->output->writeln("mail $title $text");
        while ($retry >= 0) {
            try {
                $to = 'iinux@139.com';
                // Mail::send()的返回值为空，所以可以其他方法进行判断
                Mail::send('emails.test', ['text' => $text], function ($message) use ($title, $to) {
                    $message->to($to)->subject($title);
                });
                // 返回的一个错误数组，利用此可以判断是否发送成功
                dump(Mail::failures());
                break;
            } catch (Exception $e) {
                dump($e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine());
                $retry--;
                sleep(6);
            }
        }
    }
}
