<?php
/**
 * Created by PhpStorm.
 * User: nalux
 * Date: 2019/7/21
 * Time: 19:02
 */

/**
 * @param $var
 */
function dd($var)
{
    var_dump($var);
    die(0);
}

$data = file_get_contents('config.json');
$data = json_decode($data, true);
foreach ($data as &$item) {
    usort($item['proxyMappings'], function ($a, $b){
        return $a['inetPort'] < $b['inetPort'] ? -1 : 1;
    });
}
//dd($data);
file_put_contents('config1.json', json_encode($data));