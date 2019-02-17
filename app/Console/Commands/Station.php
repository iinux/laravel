<?php

namespace App\Console\Commands;

use App\Models\Stop;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

class Station extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '12306:station';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     */
    protected $ak;
    public function __construct()
    {
        parent::__construct();
        $this->ak = env('BAIDU_AK');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $file = base_path('12306/station_name.js');
        if (!file_exists($file)) {
            $fileContent = file_get_contents('https://kyfw.12306.cn/otn/resources/js/framework/station_name.js?station_version=1.9092');
            file_put_contents($file, $fileContent);
        } else {
            $fileContent = file_get_contents($file);
        }
        $fileContent = substr($fileContent, 20, strlen($fileContent) - 20 - 2);
        $fileContent = explode('|', $fileContent);
        $len = count($fileContent);
        if ($len % 5 != 1) {
            $this->output->error("data error");
            // dd($fileContent);
            return;
        }

        for ($i = 0; $i < $len - 1; $i += 5) {
            $data = [
                '12306_id'     => $fileContent[$i],
                '12306_code'   => $fileContent[$i + 2],
                'pinyin'       => $fileContent[$i + 3],
                'pinyin_short' => $fileContent[$i + 4],
                'name'         => $fileContent[$i + 1],
                'lng'          => '',
                'lat'          => '',
                'province'     => '',
                'city'         => '',
                'district'     => '',

            ];
            $data['name'] = str_replace(' ','', $data['name']);

            $stop = Stop::where('12306_code', $data['12306_code'])->first();
            if ($stop) {
                $this->output->writeln("{$stop->province} {$stop->city} {$stop->name} skip");
                continue;
            }

            $locationUri = "http://api.map.baidu.com/place/v2/suggestion?" .
                "query={$data['name']}站" .
                "&region=%E5%85%A8%E5%9B%BD&output=json&ak={$this->ak}";
            // $locationStr = file_get_contents($locationUri);
            $client = new Client();
            $response = $client->request('GET', $locationUri);

            if (false) {
                echo $response->getStatusCode(); # 200
                echo $response->getHeaderLine('content-type'); # 'application/json; charset=utf8'
            }
            $locationStr = $response->getBody();
            $location = json_decode($locationStr);
            if (is_null($location) || $location->status != 0) {
                $this->output->error($locationStr);
                return;
            }
            $location = $location->result[0];
            $data['lng'] = $location->location->lng;
            $data['lat'] = $location->location->lat;
            $data['province'] = $location->province;
            $data['city'] = $location->city;
            $data['district'] = $location->district;

            Stop::create($data);

            $this->output->writeln("{$data['province']} {$data['city']} {$data['name']} ok");
            sleep(1);
        }

        $this->output->success('Okay');
    }
}
