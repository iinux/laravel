<?php

namespace App\Console\Commands;

use App\Models\Line;
use Illuminate\Console\Command;

class TrainList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '12306:train-list';

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
        ini_set('memory_limit', '256M');
        $file = base_path('12306/train_list.js');
        if (!file_exists($file)) {
            $fileContent = file_get_contents('https://kyfw.12306.cn/otn/resources/js/query/train_list.js?scriptVersion=1.0');
            file_put_contents($file, $fileContent);
        } else {
            $fileContent = file_get_contents($file);
        }
        $fileContent = substr($fileContent, 16);
        $fileContent = json_decode($fileContent);
        foreach ($fileContent as $date => $all) {
            foreach ($all as $prefix => $list) {
                foreach ($list as $item) {
                    $this->output->writeln($item->station_train_code);
                    preg_match('/^(\w*)\((.*)-(.*)\)$/', $item->station_train_code, $match);
                    if (count($match) != 4) {
                        dd($match);
                    }
                    $data = [
                        'train_class'        => $prefix,
                        'train_code'         => $match[1],
                        'start_station_name' => $match[2],
                        'end_station_name'   => $match[3],
                        'train_no'           => $item->train_no,
                        // 'date'               => $date,
                    ];
                    $line = Line::firstOrCreate($data);
                }
            }
        }
    }
}
