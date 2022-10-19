<?php

namespace process;

use Workerman\Crontab\Crontab;

class Task
{

    public function onWorkerStart()
    {
        // 每秒钟执行一次
        new Crontab('*/1 * * * * *', function(){
            //echo date('Y-m-d H:i:s')."\n";
        });
    }

}