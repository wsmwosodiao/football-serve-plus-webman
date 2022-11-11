<?php

namespace app\controller;

use support\Request;
use Webman\RedisQueue\Client;

class Index
{
    public function index(Request $request)
    {
        //发送队列 队列名称，数据，延迟时间
        //Client::send("send-message", ['name' => 'webman'], 10);

        return response('hello webman');
    }

    public function aftersend(Request $request)
    {
        return response('hello webman');
    }
}
