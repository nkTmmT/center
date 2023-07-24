<?php

use app\job\GenerateModel;
use think\facade\Queue;
use Workerman\Worker;
use Workerman\Lib\Timer;
use Workerman\Connection\AsyncTcpConnection;
require_once __DIR__ . '/vendor/autoload.php';

function curlRequest($url, $is_post = false, $params = [], $header = false, $time_out = 60){

    $ch = curl_init();//初始化curl
    if ($is_post) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
    }else{
        $url = $url.'?'.http_build_query($params);
    }
    // URL
    curl_setopt($ch, CURLOPT_URL, $url);
    // 设置是否返回response header
    curl_setopt($ch, CURLOPT_HEADER, 0);
    // 要求结果为字符串且输出到屏幕上
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //当需要通过curl_getinfo来获取发出请求的header信息时，该选项需要设置为true
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $time_out);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $time_out);
    if ($is_post){
        curl_setopt($ch, CURLOPT_POST, $is_post);
    }
    curl_setopt($ch, CURLOPT_FAILONERROR, false);
    if (1 == strpos('$'.$url, "https://")) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }

    if ($header) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    }
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

$worker = new Worker();

$worker->onWorkerStart = function($worker){
    // 连接远程websocket服务器
    $con = new AsyncTcpConnection('ws://test.pro.fujiuni.com/ws?type=outapi&token=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJ0ZXN0LnByby5mdWppdW5pLmNvbSIsImF1ZCI6InRlc3QucHJvLmZ1aml1bmkuY29tIiwiaWF0IjoxNjg5OTAyMzM0LCJuYmYiOjE2ODk5MDIzMzQsImV4cCI6MTY4OTk4ODczNCwianRpIjp7ImlkIjo4LCJ0eXBlIjoib3V0In19.PB_hvEqJ2uGPgB7mq7MNNKv6sXSVV7Ezx7wThc-1qSI');
    // websocket握手成功后
    $con->onWebSocketConnect = function(AsyncTcpConnection $connection) use ($con) {
        echo 'connect!'.PHP_EOL;
        $con->timer_id = Timer::add(10, function()use($connection)
        {
            $heartbeat = '{"type":"ping"}';
            echo 'send heartbeat: '.$heartbeat.PHP_EOL;
            $connection->send($heartbeat);
        });
        echo "add timer: ".$con->timer_id.PHP_EOL;
    };

    // 当收到消息时
    $con->onMessage = function(AsyncTcpConnection $connection, $data) {
        echo $data;
        $arrayData = json_decode($data, true);
        if (!empty($arrayData)){
            if (!empty($arrayData['type'])){
                if ($arrayData['type'] == 'CAPTURE_END'){
                    $data = $arrayData['data'];
                    // 进行命令处理
                    curlRequest('http://localhost:8899/handle', false, $data);
//                    Queue::push(GenerateModel::class, $data);
                }
            }

        }
        echo PHP_EOL;
    };
    // 当连接远程websocket服务器的连接断开时
    $con->onClose = function(AsyncTcpConnection $connection) use ($con){
        echo 'connection closed !';
        echo PHP_EOL;
        echo "delete timer: ".$con->timer_id;
        echo PHP_EOL;
        // 删除定时器
        Timer::del($con->timer_id);
        echo "try to reconnect";
        echo PHP_EOL;
        // 如果连接断开，1秒后重连
        $connection->reConnect(1);
    };

    $con->connect();
};

Worker::runAll();