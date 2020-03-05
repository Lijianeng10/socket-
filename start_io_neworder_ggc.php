<?php

use Workerman\Worker;
use Workerman\WebServer;
use Workerman\Lib\Timer;
use PHPSocketIO\SocketIO;

#include_once __DIR__ . '/JPush/autoload.php';
include __DIR__ . '/vendor/autoload.php';

// 全局数组保存uid在线数据
$uidConnectionMap = [];
// 记录最后一次广播的在线用户数
// $last_online_count = 0;
// 记录最后一次广播的在线页面数
// $last_online_page_count = 0;

// PHPSocketIO服务
$sender_io = new SocketIO(2120);

// 客户端发起连接事件时，设置连接socket的各种事件回调
$sender_io->on('connection', function ($socket) {

    // 当客户端发来登录事件时触发
    $socket->on('login', function ($uid) use ($socket) {
        global $uidConnectionMap;
        // 已经登录过了
        if (isset($socket->uid)) {
            return;
        }
        // 更新对应uid的在线数据
        $uid = (string)$uid;
        $socket->join($uid);
        $socket->uid = $uid;
    });

    // 当客户端断开连接是触发（一般是关闭网页或者跳转刷新导致）
    $socket->on('disconnect', function () use ($socket) {
        if (!isset($socket->uid)) {
            return;
        }
        global $uidConnectionMap, $sender_io;
    });
});

// 当$sender_io启动后监听一个http端口，通过这个端口可以给任意uid或者所有uid推送数据
$sender_io->on('workerStart', function () {
    // 监听一个http端口
    $inner_http_worker = new Worker('http://localhost:2121');
    // 当http客户端发来数据时触发
    $inner_http_worker->onMessage = function ($http_connection, $data) {
        global $uidConnectionMap;
        $data = $_POST ? $_POST : $_GET;

        // 推送数据的url格式 type=publish&to=uid&content=xxxx

        // switch($data['type']){
        //     case 'publish':
        //         global $sender_io;
        //         $to = $data['to'];
        //         $data['content'] = htmlspecialchars($data['content']);
        //         // 有指定uid则向uid所在socket组发送数据
        //         if($to){
        //             $sender_io->to($to)->emit('new_msg', $data['content']);
        //         // 否则向所有uid推送数据
        //         }else{
        //             $sender_io->emit('new_msg', $data['content']);
        //         }
        //         // http接口返回，如果用户离线socket返回fail
        //         if($to && !isset($uidConnectionMap[$to])){
        //             return $http_connection->send('offline');
        //         }else{
        //             return $http_connection->send('ok');
        //         }
        // }
        return $http_connection->send('fail');
    };
    // 执行监听
    $inner_http_worker->listen();


    global $redis;

    // 一个定时器，定时向所有uid推送当前uid在线数及在线页面数
    Timer::add(30, function () {
        global $sender_io;
        $redis = new Redis();
        $redis->connect('39.106.53.207', 6379);
        $redis->auth('Goodluck59@');
        $redis->select(10);
        $unames = $redis->SMEMBERS('sockets:new_order_list');
        $ulist[] = 'sockets:new_order_list';
        # $sender_io->emit('new_msg', '您有新的订单，请及时处理'.time());
        if (!empty($unames)) {
            foreach ($unames as $uname) {
                $sender_io->to($uname)->emit('new_orders', '您有新的订单，请及时处理');
                $ret = $redis->srem('sockets:new_order_list', $uname);
            }
        }
        //$ret = postCurl();
    });
});

if (!defined('GLOBAL_START')) {
    Worker::runAll();
}


function postCurl()
{
    $request_url = "https://ggc.goodluckchina.net/toolsmod/jpush/new-order-push";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $request_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}
