<?php
use Workerman\Worker;
use Workerman\WebServer;
use Workerman\Lib\Timer;
use PHPSocketIO\SocketIO;


include __DIR__ . '/vendor/autoload.php';

// 全局数组保存uid在线数据
$uidConnectionMap = [];
// PHPSocketIO服务
$context = array(
    'ssl' => array(

        'local_cert'  => '/usr/local/nginx/conf/cert/214555436960853.pem', // 也可以是crt文件
        'local_pk'    => '/usr/local/nginx/conf/cert/214555436960853.key',
        'verify_peer' => false,
    )
);
$sender_io = new SocketIO(2122,$context);
// 客户端发起连接事件时，设置连接socket的各种事件回调
$sender_io->on('connection', function($socket){
	// 当客户端发来登录事件时触发
    $socket->on('login', function ($uid)use($socket){
        global $uidConnectionMap;
        // 已经登录过了
        if(isset($socket->uid)){
            return;
        }
        // 更新对应uid的在线数据
        $uid = (string)$uid;
        $socket->join($uid);
        $socket->uid = $uid;
    });

    // 当客户端断开连接是触发（一般是关闭网页或者跳转刷新导致）
    $socket->on('disconnect', function () use($socket) {
        if(!isset($socket->uid))
        {
             return;
        }
        global $uidConnectionMap, $sender_io;
    });
});
// 当$sender_io启动后监听一个http端口，通过这个端口可以给任意uid或者所有uid推送数据
$sender_io->on('workerStart', function(){
    // 监听一个http端口
    $inner_http_worker = new Worker('http://172.17.70.68:2123');
    $inner_http_worker->onMessage = function($http_connection, $data){
        $allowIps = [
            '49.4.1.131',
            '114.116.97.22',
            '114.116.105.82',
            '27.154.231.158',
            '114.115.148.102',
            '123.56.29.183'
        ];    
        $reIp = getUserIp();
        if(!in_array($reIp, $allowIps)){
            return $http_connection->send('ip鉴权失败！');
        }

        $_POST = $_POST ? $_POST : $_GET;
        switch(@$_GET['type']){
            case 'send_msg':
                global $sender_io;
		        $mid = @$_GET['mid'];
                $ret = postCurl($mid);
                $sender_io->emit('new_msg',$ret);
                return $http_connection->send('ok');
                // http接口返回，如果用户离线socket返回fail
            default:
                return $http_connection->send('type error');
	    }
        return $http_connection->send('no send');
    };  
	// 执行监听
    $inner_http_worker->listen();
    // 一个定时器，定时向所有uid推送
});

if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}

function postCurl($mid){
    $request_url = "https://caipiao.goodluckchina.net/api/cron/data/get-change-schedule-bymid?mid=".$mid;
    $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
}

function getUserIp(){
        $unknown = 'unknown';  
        if ( isset($_SERVER['HTTP_X_FORWARDED_FOR']) 
        && $_SERVER['HTTP_X_FORWARDED_FOR'] 
        && strcasecmp($_SERVER['HTTP_X_FORWARDED_FOR'], 
        $unknown) ) {  
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];  
        } elseif ( isset($_SERVER['REMOTE_ADDR']) 
        && $_SERVER['REMOTE_ADDR'] && 
        strcasecmp($_SERVER['REMOTE_ADDR'], $unknown) ) {  
        $ip = $_SERVER['REMOTE_ADDR'];  
        }  
        /*  
        处理多层代理的情况  
        或者使用正则方式：$ip = preg_match("/[d.]
        {7,15}/", $ip, $matches) ? $matches[0] : $unknown;  
        */  
        if (false !== strpos($ip, ','))  
        $ip = reset(explode(',', $ip));  
        return $ip; 
                         
    }
