<?php

namespace App\Console\Commands;

use App\Models\YeLine;
use App\Models\YeStop as Stop;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class YeBus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ye-bus';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ye Bus';
    protected $ak;

    /**
     * Create a new command instance.
     *
     */
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
        $list = $this->request('http://www.bjbus.com/api/api_night_time.php?key=%E5%A4%9C', true);
        foreach ($list->data as $item) {
            $line = YeLine::firstOrCreate([
                'id'   => $item->lineid,
                'name' => $item->linename,
            ]);
            $lineData = $this->request('http://www.bjbus.com/api/api_night_detail.php?lineid=' . $line->id, true);
            preg_match_all('/<div class="ng_station">&nbsp;&nbsp;&nbsp;\d+.(.*?)<\/div>/', $lineData->data, $matches);

            $stationNumber = 1;
            foreach ($matches[1] as $stopName) {
                $stop = Stop::where([
                    'ye_line_id' => $line->id,
                    'name'       => $stopName,
                ])->first();
                if ($stop) {
                    $this->output->writeln("{$stop->ye_line_id} {$stop->name} skip");
                    continue;
                }

                $data = [
                    'ye_line_id'     => $line->id,
                    'name'           => $stopName,
                    'station_number' => $stationNumber++,
                ];

                $locationUri = "http://api.map.baidu.com/place/v2/suggestion?" .
                    "query={$stopName}公交站" .
                    "&region=北京市&output=json&ak={$this->ak}";
                $client = new Client();
                sleep(1);
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
                dump($location);
                $location = $location->result[0];
                $data['lng'] = $location->location->lng;
                $data['lat'] = $location->location->lat;
                $data['province'] = $location->province;
                $data['city'] = $location->city;
                $data['district'] = $location->district;

                Stop::create($data);
            }
        }
    }

    public function request($uri, $json = false)
    {
        $key = "requestCache:$uri";
        if ($res = Redis::get($key)) {
        } else {
            $res = file_get_contents($uri);
            Redis::set($key, $res);
        }

        if ($json) {
            return json_decode($res);
        } else {
            return $res;
        }
    }
}
