<?php

include_once 'DqLoader.php';
include_once 'DqClient.php';
include_once 'DqComm.php';   

/**
 * 性能测试 需要安装pthreads拓展
 * 测试原理：使用多线程模拟并发，在1s内能成功返回请求成功的个数
 * php DqBench  concurrency  requests
 * concurrency:并发数
 * requests： 每个并发产生的请求数
 */
class DqBench extends Thread{
    private $name;
    public function __construct($name){
        $this->name = $name;
    }
    static  $concurrency=10;
    static  $nums = 2;

    function run(){
        $server=array(
            '10.13.131.116:6789',
        );
        $time = self::msectime();
        $dqClient = new DqClient();
        $dqClient->addServer($server);

        $topic ='order_openvip_checker'; //topic在后台注册
        for($i=0;$i<self::$nums;$i++) {
            $id = uniqid();
            $data = array(
                'id' => $id,
                'body' => array(
                    'a' => 1,
                    'b' => 2,
                    'c' => 3,
                    'ext' => str_repeat('a', 128),
                ),
                //可选，设置后以这个通知时间为准，默认延时时间在注册topic的时候指定
                //'fix_time' => date('Y-m-d 23:50:50'),
            );
            $boolRet = $dqClient->add($topic, $data);
            echo 'add耗时:'.(self::msectime() - $time)."ms\n";
        }
    }

    static function msectime() {
        list($msec, $sec) = explode(' ', microtime());
        $msectime =  (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
        return $msectime;
    }
}

DqBench::$concurrency  =$_SERVER['argv'][1];
DqBench::$nums = $_SERVER['argv'][2];




for($i=0;$i<DqBench::$concurrency;$i++){
    $pool[] = new DqBench("name:".$i);
}

$start = DqBench::msectime();
foreach($pool as $worker){
    $worker->start();
}


foreach($pool as $worker) {
    $worker->join();//等待执行完成
}

echo "总耗时:".(DqBench::msectime()-$start)."ms\n";

// php DqBench 100 2 



