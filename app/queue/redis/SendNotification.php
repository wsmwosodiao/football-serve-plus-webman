<?php

namespace App\queue\redis;

use App\Services\RobotPushUserService;
use Webman\RedisQueue\Consumer;

class SendNotification implements Consumer
{
    public $queue = "send-notification";

    public function consume($notification)
    {
        RobotPushUserService::make()->pushMacthNotificationSend($notification);
    }
}