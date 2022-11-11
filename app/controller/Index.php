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
        $id = $request->input('id');
        $url = $request->input('url');
        $footBallAfterImgPush = FootBallAfterImgPush::query()->where('_id', $id)->first();
        $post_data = data_get($footBallAfterImgPush, 'post_data');
        if ($footBallAfterImgPush) {
            $ob = json_decode($post_data,true);
            $d = data_get($ob,'footBallFixturePushAll');
            $footBallFixturePushAll = FootBallFixturePushAll::query()->where('_id',$d)->first();
            RobotPushService::make()->pushSend($footBallFixturePushAll, data_get($ob, 'content'), data_get($ob, 'language'), data_get($ob, 'key'), data_get($ob, 'referral_code'),data_get($ob,'map'), 2, $url);
            $footBallAfterImgPush->is_send = true;
            $footBallAfterImgPush->send_at = Carbon::now();
            $footBallAfterImgPush->save();
        }else{
            Log::error('no id',[$request]);
        }
        return response('success');
    }
}
