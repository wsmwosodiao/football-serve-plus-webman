<?php

namespace app\controller;

use App\model\Sport\FootBallAfterImgPush;
use App\model\Sport\FootBallFixturePushAll;
use App\Services\RobotPushService;
use Carbon\Carbon;
use support\Log;
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
        Log::info('info',$request->all());
        $id = $request->input('id');
        $url = $request->input('url');
        $footBallAfterImgPush = FootBallAfterImgPush::query()->where('_id', $id)->first();
        $post_data = data_get($footBallAfterImgPush, 'post_data');
        if ($footBallAfterImgPush) {
            $d = new FootBallFixturePushAll(json_decode(data_get($post_data, 'footBallFixturePushAll')));
            Log::info('FootBallFixturePushAll',[$d]);
            RobotPushService::make()->pushSend($d, data_get($post_data, 'content'), data_get($post_data, 'language'), data_get($post_data, 'key'), data_get($post_data, 'referral_code'),data_get($post_data,'map'), true, $url);
            $footBallAfterImgPush->is_send = true;
            $footBallAfterImgPush->send_at = Carbon::now();
            $footBallAfterImgPush->save();
        }else{
            Log::error('no id',[$request]);
        }
        return response('success');
    }
}
