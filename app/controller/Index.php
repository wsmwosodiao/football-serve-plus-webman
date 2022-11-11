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
        Log::info('yuncallback',[$request]);
        $id = $request->input('id');
        $url = $request->input('url');
        $footBallAfterImgPush = FootBallAfterImgPush::query()->where('_id',$id)->first();
        $post_data = $footBallAfterImgPush->post_data;
        if($footBallAfterImgPush) {
            RobotPushService::make()->pushSend($post_data->footBallFixturePushAll,$post_data->content,$post_data->language,$post_data->key,$post_data->referral_code,true,$url);
            $footBallAfterImgPush->is_send = true;
            $footBallAfterImgPush->send_at = Carbon::now();
            $footBallAfterImgPush->save();
        }
        return response('success');
    }
}
