<?php

namespace App\queue\redis;

use Webman\RedisQueue\Consumer;

class SendMessage implements Consumer
{
    public $queue = "send-message";

    public function consume($data)
    {
        var_export($data);
    }
}