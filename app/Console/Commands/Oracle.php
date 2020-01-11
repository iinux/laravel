<?php

namespace App\Console\Commands;

use App\VParkOrder;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Oracle extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'oracle';

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
        //        return $this->sql();
        return $this->analyze();
        dd(DB::select('select * from v$version'));
        dd(DB::statement('select * from v$version'));
        dd(VParkOrder::count());
        dd(VParkOrder::first());
    }

    public function sql()
    {
        $ret = DB::select("select count(*) from V_PARK_ORDER where in_time >= '2019-05-26' long_time>1440");
        dd($ret);
    }

    public function analyze()
    {
        $startTime = '2019-06-21 00:00:00';
        $endTime = '2019-06-21 00:59:59';
        $endLoopTime = '2019-06-27 23:59:59';
        $statistic = [];
        while (true) {
            $sql = "select * from V_PARK_ORDER where in_time>= '$startTime' and in_time <= '$endTime'";
            echo "run $sql\n";
            $orders = DB::select($sql);
            foreach ($orders as $order) {
                if ($order->in_time && $order->out_time && $order->out_time > $order->in_time) {
                    $parkSecond = strtotime($order->out_time) - strtotime($order->in_time);
                    $hour = intval(ceil($parkSecond / (60 * 60)));
                    if (isset($statistic[$hour])) {
                        $statistic[$hour]++;
                    } else {
                        $statistic[$hour] = 1;
                    }
                    echo "{$order->in_time} {$order->out_time} $parkSecond $hour $statistic[$hour]\n";
                }
            }

            $startTime = date('Y-m-d H:i:s', strtotime("$startTime +1hour"));
            $endTime = date('Y-m-d H:i:s', strtotime("$endTime +1hour"));
            if ($startTime > $endLoopTime) {
                break;
            }
        }
        dd($statistic);
        dd("finished");

    }

    public function analyzeAndImport()
    {
        $startTime = '2019-06-01 00:00:00';
        $endTime = '2019-06-01 00:59:59';
        $endLoopTime = date('Y-m-d H:i:s');
        while (true) {
            $sql = "select * from V_PARK_ORDER where in_time>= '$startTime' and in_time <= '$endTime'";
            echo "run $sql\n";
            $orders = DB::select($sql);
            foreach ($orders as $order) {
                if ($order->in_time && $order->out_time && $order->out_time > $order->in_time) {
                    $parkSecond = strtotime($order->out_time) - strtotime($order->in_time);
                    if ($parkSecond < 24 * 60 * 60) {
                        echo "in_time: $order->in_time, out_time: $order->out_time, parkSecond: $parkSecond skip\n";
                        continue;
                    }
                }

                $orderArr = (array)$order;
                $model = new VParkOrder($orderArr);
                $model->setConnection('mysql2');
                $model->setTable('park_orders');
                $model->save();
            }

            $startTime = date('Y-m-d H:i:s', strtotime("$startTime +1hour"));
            $endTime = date('Y-m-d H:i:s', strtotime("$endTime +1hour"));
            if ($startTime > $endLoopTime) {
                break;
            }
        }
        dd("finished");

    }

    public function createTable()
    {
        $orders = DB::select("select * from V_PARK_ORDER where rownum=1");
        $keys = array_keys((array)$orders[0]);
        Schema::connection('mysql2')->create('park_orders', function (Blueprint $table) use ($keys) {
            foreach ($keys as $key) {
                $table->string($key)->nullable();
            }
        });
        dd('create ok');
    }

    public function analyze1()
    {
        $page = 1;
        while (true) {
            $orders = VParkOrder::paginate(15, ['*'], 'page', $page);
            $count = $orders->count();
            if ($count == 0) {
                break;
            }
            foreach ($orders as $order) {
                dd($order->all());
            }
        }
        dd("finished");
    }
}
