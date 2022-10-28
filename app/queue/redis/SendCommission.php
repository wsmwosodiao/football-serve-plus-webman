<?php

namespace App\queue\redis;

use App\Services\RobotPushService;
use Webman\RedisQueue\Consumer;

class SendCommission implements Consumer
{
    public $queue = "send-commission";

    public function consume($data)
    {
        RobotPushService::make()->pushMacthTimingQueue($data['id'],$data['content_id']);
    }
}