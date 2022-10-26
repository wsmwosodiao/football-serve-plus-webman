<?php

namespace App\queue\redis;

use App\Services\RobotPushUserService;
use Webman\RedisQueue\Consumer;

class SendMessage implements Consumer
{
    public $queue = "send-message";

    public function consume($data)
    {
        RobotPushUserService::make()->pushMacthNotificationSend($data);
    }
}