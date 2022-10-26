<?php

namespace App\queue\redis;

use App\Services\RobotPushUserService;
use Webman\RedisQueue\Consumer;

class SendMacth implements Consumer
{
    public $queue = "send-macth";

    public function consume($data)
    {
        RobotPushUserService::make()->pushMacthUserPictureSend($data['userRobotSubscribe'],$data['footBallFixturePush']);
    }
}