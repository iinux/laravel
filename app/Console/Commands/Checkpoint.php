<?php

namespace App\Console\Commands;

use App\Models\Line;
use App\Models\Stop;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use App\Models\Checkpoint as CheckpointModel;

class Checkpoint extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '12306:checkpoint';

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
        $date = date('Y-m-d', strtotime("+1 day"));
        $i = 1;
        while (true) {
            $lines = Line::paginate(100, ['*'], 'page', $i++);
            $count = $lines->count();
            if ($count == 0) {
                break;
            }
            foreach ($lines as $line) {
                $this->output->writeln("processing {$line->train_code} {$line->start_station_name} -> {$line->end_station_name}");
                $fromStop = Stop::where('name', $line->start_station_name)->firstOrFail();
                $endStop = Stop::where('name', $line->end_station_name)->firstOrFail();
                $uri = "https://kyfw.12306.cn/otn/czxx/queryByTrainNo?" .
                    "train_no={$line->train_no}&from_station_telecode={$fromStop->code_12306}&to_station_telecode={$endStop->code_12306}&depart_date=$date";
                $client = new Client();
                $response = $client->request('GET', $uri);
                $fileContent = json_decode($response->getBody()->getContents());

                foreach ($fileContent->data->data as $item) {
                    dump($item->station_name);
                    $stop = Stop::where('name', $item->station_name)->firstOrFail();

                    $data = [
                        'line_id'       => $line->id,
                        'stop_id'       => $stop->id,
                        'arrive_time'   => $item->arrive_time,
                        'start_time'    => $item->start_time,
                        'stopover_time' => $item->stopover_time,

                    ];
                    
                    CheckpointModel::create($data);
                }

                return;
            }

        }
    }
}
