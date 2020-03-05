<?php
use Workerman\Worker;
use Workerman\WebServer;
use PHPSocketIO\SocketIO;
use JPush\Jpush;

include __DIR__ . '/vendor/autoload.php';

// PHPSocketIO服务
$io = new SocketIO(2124);
// 客户端发起连接事件时，设置连接socket的各种事件回调
$io->on('connection', function($connection)use($io){
    $connection->on('join_group',function($msg)use($io,$connection){
        $connection->join($msg);
    });
});
    // 当$sender_io启动后监听一个http端口，通过这个端口可以给任意uid或者所有uid推送数据
    $io->on('workerStart', function()use($io){
        // 监听一个http端口
        $inner_http_worker = new Worker('http://123.56.29.183:2125');
        $inner_http_worker->onMessage = function($http_connection, $data){
            global $io;
            $code = $_POST['lottery_code'];
	    $openNumber = $_POST['open_number'];
            $io->to($code)->emit('open_number',$openNumber);
            return $http_connection->send('ok');
	};
	// 执行监听
        $inner_http_worker->listen();
        // 一个定时器，定时向所有uid推送
   	// Timer::add(5, function(){
     	//   global  $io ;       
   	//     // $ret = postCurl(99896);
        // $io->to(2001)->emit('new_msg', '我是群组发来的消息');
   	 });
if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}

