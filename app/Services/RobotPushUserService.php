<?php

namespace App\Services;

use App;

use App\jobs\RobotNotification;
use App\model\LanguageConfig;
use App\model\Notification;
use App\model\RejectInfo;
use App\model\Sport\FootBallFixturePush;
use App\model\User;
use App\model\UserData;
use App\model\UserRobotSubscribe;
use Carbon\Carbon;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use support\Log;
use Workerman\Http\Client;
use Workerman\Worker;


class RobotPushUserService extends BaseService
{

    protected string $pushUserUrl='https://api.jooegg.net/api/v1/sendUser';//'http://192.168.6.209/api/v1/sendUser';
    protected $httpWorkerman;
    protected bool $is_push=true; //是否正式推送
    protected bool $is_Debug=false;

    protected string $bot_name='55data';


    public function __construct()
    {
        $this->is_Debug=Worker::$daemonize;//true 正式环境
        if($this->is_Debug){
            $this->pushUserUrl='https://api.jooegg.net/api/v1/sendUser';
        }
        $this->httpWorkerman=new Client();//请求类
    }


    /**
     * 推送用户站内通知
     */
    public function pushMacthNotification()
    {
        $count = 0;
        $ids=UserRobotSubscribe::query()->where('is_bound_robot_subscribe',true)->pluck('user_id');
        Notification::query()
            ->where('is_push', false)
            ->whereIn('user_id', $ids)
            ->whereBetween('created_at', [Carbon::now()->subMinutes(20), Carbon::today()->endOfDay()])
            ->lazyById(1)->each(function ($item) use(&$count) {
                $count ++;
                \Webman\RedisQueue\Client::send("send-notification", $item->getKey());
            });
        Log::info("推送用户站内通知：".$count);
    }
    public function pushMacthNotificationSend($notification_id)
    {
        Log::info("推送用户站内通知-准备",["params"=>$notification_id]);
        $notification=Notification::query()->with(['user'])->find($notification_id);
        $local=$notification->user->local;
        $content=$this->getContent($notification,$local);
        if($content){
            $params=[
                "level"=>0,
                "language"=>(string)$local,
                "text"=>$content,
                "delay"=>0,
                "referral_code"=>"IP1JFHTY",//$notification->user->referral_code
            ];
            Log::info("推送用户站内通知发送",["params"=>$params,"user_id"=>$notification->user_id]);
            if($this->is_push){
                $this->httpWorkerman->post($this->pushUserUrl, $params);
                $notification->is_push_success=true;
                $notification->save();
            }
        }else{
            Log::info("推送用户站内通知发送-没有内容",["id"=>$notification->_id,"user_id"=>$notification->user_id]);
        }

        $notification->is_push=true;
        $notification->push_at=Carbon::now();
        $notification->save();
        return true;
    }

    public function getContent($notification,$local)
    {

        $params =collect($notification->params)->map(function ($value, $key) use ($local) {
            if (is_array($value)) {
                return $value;
            }
//            if (Str::contains($key, ['_lang', '_type'])) {
//                return $this->getLang($value, [], $local);
//            }
            return $value;
        })->toArray();
        $content=$this->getLang($notification->content_slug, $params, $local);
        Log::info("推送用户站内通知发送内容获取",["content"=>$content,"content_slug"=>$notification->content_slug,"local"=>$local]);
        if(!$content){
            if($notification->type=='UserBaseballOrderOverNotification' && $notification->content_slug=='UserBaseballOrderWinNotificationContent'){
                $content=$this->getLang('WIN_FOOTBALL_ORDER_CONTENT', $params, $local);
            }else if($notification->type=='UserBaseballOrderOverNotification' && $notification->content_slug=='UserFootballOrderLoseNotificationContent'){
                $content=$this->getLang('FAIL_FOOTBALL_ORDER_CONTENT', $params, $local);
            }
            if($notification->type=='VipAgentMonthSalaryCheckNotification'){
                $content=$this->getLang('YOUR_APPLY_SALARY_N', $params, $local);
                $pass=data_get($params,'pass',false);
                if($pass){
                    $content=$content."\n".$this->getLang('PASSED', [], $local);
                }else{
                    $content=$content."\n".$this->getLang('UNPASS', [], $local);
                }
            }

        }

        $contents_introduction = RejectInfo::query()->where('group', 'other')->where('slug','OFFICIAL_WEBSITE')->first()->toArray();
        $contents_introduction = data_get($contents_introduction, "title.$local","");
        if($contents_introduction){
            $content=$content."\n".$contents_introduction;
        }
        Log::info("推送用户站内通知发送内容获取-结果",["content"=>$content,"content_slug"=>$notification->content_slug,"local"=>$local]);
        return $content;
    }


    /**
     * 推送用户赛事图片
     */
    public function pushMacthPicture()
    {
        FootBallFixturePush::query()
            ->where('status', true)
            ->where('is_push', true)
            ->where('is_push_user', false)
            //->where('push_time', '>', Carbon::now()->subMinutes(5))//5分钟内推送过的赛事
            ->chunk(50, function ($list) use (&$count) {
                foreach ($list as $item) {
                    $this->pushMacthUserPicture($item);
                }
            });
    }
    public function pushMacthUserPicture(FootBallFixturePush $footBallFixturePush, $counts_second=1)
    {
        try {
            $count=0;
            //获取订阅的用户
            UserRobotSubscribe::query()
                ->where('local',$footBallFixturePush->lang)
                ->where('is_bound_robot_subscribe', true)
                ->chunk(5000, function ($list) use (&$count,$counts_second,$footBallFixturePush) {
                    foreach ($list as $item) {
                        \Webman\RedisQueue\Client::send("send-macth", ["userRobotSubscribe_id"=>$item->getKey(),"footBallFixturePush_id"=>$footBallFixturePush->getKey()]);
                    }
                });
            //更新数据
            $footBallFixturePush->is_push_user=true;
            $footBallFixturePush->push_user_time=Carbon::now();
            $footBallFixturePush->save();
            Log::info("推送赛事给用户：".$count);
            return true;
        } catch (\Exception $exception) {
            Log::error("机器自动推送错误：" . $exception->getMessage());
            return false;
        }
    }

    public function pushMacthUserPictureSend($userRobotSubscribe_id, $footBallFixturePush_id)
    {
        $userRobotSubscribe=UserRobotSubscribe::query()->find($userRobotSubscribe_id);
        $footBallFixturePush=FootBallFixturePush::query()->find($footBallFixturePush_id);
        $referral_code=$userRobotSubscribe->referral_code;
        if($userRobotSubscribe && $footBallFixturePush){
            $sleep_second=1;

            $images=$footBallFixturePush->images;
            if($footBallFixturePush->contents){
                $contents=$footBallFixturePush->contents;

                $params=[
                    "level"=>0,
                    "language"=>$footBallFixturePush->lang,
                    "text"=>$contents,
                    "delay"=>0,
                    "referral_code"=>$referral_code
                ];
                if($footBallFixturePush->country){
                    $params['country']=$footBallFixturePush->country;
                }
                $params['bot_name']=$this->bot_name;//强制设置机器人为55data
                if($this->is_push){
                    $this->httpWorkerman->post($this->pushUserUrl, $params);
                }
            }
            if($images){
                $i=1;
                foreach ($images as $key=>$v){
                    if($v){
                        $delay=$i*$sleep_second - 2;
                        $params=[
                            "level"=>0,
                            "language"=>$footBallFixturePush->lang,
                            "img_url"=>$v,
                            "delay"=>$delay,
                            "referral_code"=>$referral_code
                        ];
                        if($footBallFixturePush->country){
                            $params['country']=$footBallFixturePush->country;
                        }
                        $params['bot_name']=$this->bot_name;//强制设置机器人为55data
                        if($this->is_push){
                            $res=$this->httpWorkerman->post($this->pushUserUrl, $params);
                        }
                        $i++;
                    }
                }
            }
            if($footBallFixturePush->contents_introduction){
                $contents=$footBallFixturePush->contents_introduction;
                $params=[
                    "level"=>0,
                    "language"=>$footBallFixturePush->lang,
                    "text"=>$contents,
                    "delay"=>$sleep_second + 5,
                    "referral_code"=>$referral_code
                ];
                if($footBallFixturePush->country){
                    $params['country']=$footBallFixturePush->country;
                }
                $params['bot_name']=$this->bot_name;//强制设置机器人为55data
                //Log::info("推送赛事给用户-简介",["params"=>$params,"referral_code"=>$referral_code]);
                if($this->is_push){
                    $this->httpWorkerman->post($this->pushUserUrl, $params);
                }
            }
        }else{
            Log::info("推送赛事给用户--数据不存在",["userRobotSubscribe_id"=>$userRobotSubscribe_id,"referral_code"=>$referral_code]);
        }

    }






    public function getLang($slug, array $params = [], $local = null)
    {
        if (empty($slug)) {
            return null;
        }

        $slug = strtoupper($slug);

        $langContent = $this->getLangConfig($local, $slug);
        if (count($params) > 0) {
            foreach ($params as $key => $value) {
                if (is_array($value)) {
                    continue;
                }
                if(is_float($value)){
                    $value = $this->convert_scientific_number_to_normal($value);
                }
                $langContent = str_replace("{" . $key . "}", $value, $langContent);
            }
        }
        $langContent = preg_replace('/{.*}/', '', $langContent);
        return $langContent;
    }



    public function convert_scientific_number_to_normal($number): string
    {
        if (stripos($number, 'e') === false) {
            //判断是否为科学计数法
            return $number;
        }

        if (!preg_match("/^([\\d.]+)[eE]([\\d\\-\\+]+)$/", str_replace(array(" ", ","), "", trim($number)), $matches)) {
            //提取科学计数法中有效的数据，无法处理则直接返回
            return $number;
        }


        //对数字前后的0和点进行处理，防止数据干扰，实际上正确的科学计数法没有这个问题
        $data = preg_replace(array("/^[0]+/"), "", rtrim($matches[1], "0."));
        $length = (int)$matches[2];
        if ($data[0] == ".") {
            //由于最前面的0可能被替换掉了，这里是小数要将0补齐
            $data = "0{$data}";
        }

        //这里有一种特殊可能，无需处理
        if ($length == 0) {
            return $data;
        }
        //记住当前小数点的位置，用于判断左右移动
        $dot_position = strpos($data, ".");
        if ($dot_position === false) {
            $dot_position = strlen($data);
        }
        //正式数据处理中，是不需要点号的，最后输出时会添加上去
        $data = str_replace(".", "", $data);
        if ($length > 0) {
            //如果科学计数长度大于0
            //获取要添加0的个数，并在数据后面补充
            $repeat_length = $length - (strlen($data) - $dot_position);
            if ($repeat_length > 0) {
                $data .= str_repeat('0', $repeat_length);
            }
            //小数点向后移n位
            $dot_position += $length;
            $data = ltrim(substr($data, 0, $dot_position), "0") . "." . substr($data, $dot_position);
        } elseif ($length < 0) {
            //当前是一个负数
            //获取要重复的0的个数
            $repeat_length = abs($length) - $dot_position;
            if ($repeat_length > 0) {
                //这里的值可能是小于0的数，由于小数点过长
                $data = str_repeat('0', $repeat_length) . $data;
            }
            $dot_position += $length;//此处length为负数，直接操作
            if ($dot_position < 1) {
                //补充数据处理，如果当前位置小于0则表示无需处理，直接补小数点即可
                $data = ".{$data}";
            } else {
                $data = substr($data, 0, $dot_position) . "." . substr($data, $dot_position);
            }
        }
        if ($data[0] == ".") {
            //数据补0
            $data = "0{$data}";
        }
        return trim($data, ".");
    }
    public function getLangConfig($local, $slug)
    {

        $allLang = LanguageConfig::query()->pluck('content', 'slug');
        $itemLang = data_get($allLang, $slug);
        return data_get($itemLang, $local);

    }


    /**
     * 获取图片地址
     * @param $path
     * @param int $w
     * @param string|null $style
     * @return mixed
     */
    public function imageUrl($path, int $w = 700, string $style = null): mixed
    {
        if (empty($path)) return $path;
        if (Str::contains($path, '//')) {
            return $path;
        }
        $x_style = "";
        if ($w && !$style) {
            $x_style = "?x-oss-process=image/resize,w_$w/quality,q_90";
        }
        if ($style) {
            $x_style = "?x-oss-process={$style}";
        }
        return Storage::disk("aliyun")->url($path . $x_style);
    }

}
