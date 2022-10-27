<?php

namespace App\Services;

use App;

use App\model\Language;
use App\model\Notification;
use App\model\RedPacket;
use App\model\RobotPushLog;
use App\model\Sport\FootBallFixture;
use App\model\Sport\FootBallFixturePush;
use App\model\Sport\FootBallFixturePushAll;
use App\model\Sport\FootballTeacherFixture;
use App\model\UserRechargeOrder;
use App\model\UserRobotSubscribe;
use Carbon\Carbon;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use support\Log;
use Workerman\Http\Client;
use Workerman\Worker;


class RobotPushService extends BaseService
{

    protected string $getUrl='https://snap.yfb.net/snapshot?';

    protected string $pushUrl='https://api.jooegg.net/api/v1/send';//'http://192.168.6.209/api/v1/send';
    protected $httpWorkerman;
    protected bool $is_push=true; //是否正式推送
    protected bool $is_Debug=false;

    protected string $bot_name='55data';


    public function __construct()
    {
        $this->is_Debug=Worker::$daemonize;//true 正式环境
        if($this->is_Debug){
            $this->pushUrl='https://api.jooegg.net/api/v1/send';
        }
        $this->httpWorkerman=new Client();//请求类
    }

    /**
     * 获取24小时赛事图片
     */
    public function getTodayMacth()
    {
        $type=3;
        $langList = Language::query()->get();
        $count=0;
        foreach ($langList as $item) {
            $lang=$item->slug;
            $id=Carbon::now()->format('YmdH');
            if ($this->is_Debug) {
                $url=$this->getUrl."type=$type&id=$id&lang=$lang&odds=0&date=";
                //Log::info("获取24小时赛事图片处理:".$url);
                $this->httpWorkerman->get($url);

            }
            $count++;
        }
        Log::info("获取24小时赛事图片:".$count);
    }


    /**
     * 获取最近2小时开始赛事
     */
    public function getTodayMacthTwoHoursList()
    {
        FootBallFixture::query()
            ->whereNotNull('remark')
            ->where('has_odds', true)
            ->where('date', '<', Carbon::now()->addMinutes(130))
            ->where('date', '>', Carbon::now()->subMinutes(130))
            ->where('status', '<>', false)
            ->chunk(15, function ($list) use (&$count) {
                $count = $count + count($list);
                foreach ($list as $item) {
                    $this->getTodayMacthTwoHours($item);
                }
            });
    }

    /**
     * 获取最近2小时开始赛事图片
     */
    public function getTodayMacthTwoHours(FootBallFixture $footBallFixture): bool
    {
        try {

            if(Carbon::make($footBallFixture->date)->lt(Carbon::now())) return false;

            /**
             * @var FootballTeacherFixture $teacherFixture
             */
            $teacherFixture = collect($footBallFixture->teacherFixture)->filter(function (FootballTeacherFixture $teacherFixture) {
                return $teacherFixture->teacher?->is_rec;
            })->first();
            $odds=(float)$this->toDecimal2($teacherFixture?->odds * 100);
            $type=1;
            if($odds>2){
                $type=2;
            }
            $count=0;
            $count_all=0;
            foreach ((array)$footBallFixture->remark as $key => $value) {
                $count_all++;
                if($value){
                    $lang=$key;
                    $footBallFixturePush=FootBallFixturePush::query()->where('type',$type)->where('lang',$lang)->where('id',$footBallFixture->id)->first();
                    if(!$footBallFixturePush){
                        $url=$this->getUrl."type=$type&id=$footBallFixture->id&lang=$lang&odds=$odds&date=$footBallFixture->date";
                        //Log::info("获取2小时赛事图片处理:".$url);
                        if ($this->is_Debug) {
                            //Log::info("获取2小时赛事图片处理-请求:".$url);
                            $this->httpWorkerman->get($url);
                        }
                        $count++;
                    }
                }
            }
            Log::info("获取2小时赛事推广图片-赛事：" . $footBallFixture->id." 总共：$count_all ，成功：".$count);
            return true;
        } catch (\Exception $exception) {
            Log::error("机器获取推广图片：" . $exception->getMessage());
            return false;
        }
    }


    public function toDecimal2($v): string
    {
        $v = (floor((int)($v * 100)) / 100);

        return sprintf("%01.2f", $v);
    }
    /**
     * 推送赛事图片
     */
    public function pushMacth()
    {
        $count = 0;
        FootBallFixturePush::query()
            ->where('status', false)
            ->where('is_push', true)
            ->where('date', '>', Carbon::now())
            ->chunk(15, function ($list) use (&$count) {
                $count = $count + count($list);
                foreach ($list as $item) {
                    $this->upPushMacth($item,$count);
                }
            });
    }

    public function upPushMacth(FootBallFixturePush $footBallFixturePush, $counts_second=1): bool
    {
        set_time_limit(0);
        try {

            $is_send=true;
            $level=$footBallFixturePush->type;
            if($level>2){//发送VIP
                $level=2;
            }
            $sleep_second=3 * $counts_second;
            if($is_send){
                $images=$footBallFixturePush->images;
                if($footBallFixturePush->contents){
                    $contents=$footBallFixturePush->contents;
//                    $contents=str_replace("-","\-",$contents);
//                    $contents=str_replace("——","\——",$contents);
//                    $contents=str_replace(".","\.",$contents);
//                    $contents=str_replace("+","\+",$contents);
                    $params=[
                        "level"=>$level,
                        "language"=>$footBallFixturePush->lang,
                        "text"=>$contents,
                        "delay"=>0,
                    ];
                    if($footBallFixturePush->country){
                        $params['country']=$footBallFixturePush->country;
                    }
                    $params['bot_name']=$this->bot_name;//强制设置机器人为55data
                    if($this->is_push){
                        $this->httpWorkerman->post($this->pushUrl, $params);
                    }
                }
                if($images){
                    $i=1;
                    foreach ($images as $key=>$v){
                        if($v){
                            $delay=$i*$sleep_second - 2;
                            $params=[
                                "level"=>$level,
                                "language"=>$footBallFixturePush->lang,
                                "img_url"=>$v,
                                "delay"=>$delay,
                            ];
                            if($footBallFixturePush->country){
                                $params['country']=$footBallFixturePush->country;
                            }
                            $params['bot_name']=$this->bot_name;//强制设置机器人为55data
                            if($this->is_push){
                                $this->httpWorkerman->post($this->pushUrl, $params);
                            }
                            $i++;
                        }
                    }
                }
                if($footBallFixturePush->contents_introduction){
                    $contents=$footBallFixturePush->contents_introduction;
//                    $contents=str_replace("-","\-",$contents);
//                    $contents=str_replace("——","\——",$contents);
//                    $contents=str_replace(".","\.",$contents);
//                    $contents=str_replace("+","\+",$contents);
                    $params=[
                        "level"=>$level,
                        "language"=>$footBallFixturePush->lang,
                        "text"=>$contents,
                        "delay"=>$sleep_second + 5,
                    ];
                    if($footBallFixturePush->country){
                        $params['country']=$footBallFixturePush->country;
                    }
                    $params['bot_name']=$this->bot_name;//强制设置机器人为55data
                    if($this->is_push){
                        $this->httpWorkerman->post($this->pushUrl, $params);
                    }
                }
                //更新数据
                $footBallFixturePush->status=true;
                $footBallFixturePush->push_time=Carbon::now();
                $footBallFixturePush->save();
            }
            return false;
        } catch (\Exception $exception) {
            Log::error("机器自动推送错误：" . $exception->getMessage());
            return false;
        }
    }



    /**
     * 推送自定义推送内容
     */
    public function pushMacthTiming()
    {
        $count = 0;
        FootBallFixturePushAll::query()
            ->where('is_push', true)
            ->where(function (Builder $q) {
                $q->whereNull('slug')->orWhere('slug', 'NORECHARGE');
            })
            //->where('is_push_user', false)
            ->where('date', '<', Carbon::now())//开始时间
            ->chunk(20, function ($list) use (&$count) {
                $count = $count + count($list);
                foreach ($list as $item) {
                    $this->upPushMacthTiming($item);
                }
            });
        if($count>0){
            Log::info("自定义推送任务：".$count);
        }

    }



    /**
     * 推送自定义推送内容-红包
     */
    public function pushMacthTimingRed()
    {
        $count = 0;
        FootBallFixturePushAll::query()
            ->where('is_push', true)
            ->where('slug','RED')
            ->where('date', '<', Carbon::now())//开始时间
            ->where('is_push_user', false)
            ->chunk(1, function ($list) use (&$count) {
                $count = $count + count($list);
                foreach ($list as $item) {
                    $this->upPushMacthTiming($item,1);
                }
            });
        Log::info("红包自定义推送任务：".$count);

    }

    public function upPushMacthTiming(FootBallFixturePushAll $footBallFixturePushAll , $type=0)
    {

        if($footBallFixturePushAll->end_at){//已结束
            if(Carbon::make($footBallFixturePushAll->end_at)->lt(Carbon::now())) return false;
        }
        if($footBallFixturePushAll->slug=='RED' && $type==1){
            return $this->pushMacthTimingSendRed($footBallFixturePushAll);//口令红包推送到群
        }
        //推送时间间隔为0
        if($footBallFixturePushAll->hours == 0) return false;
        //处理是否到达可推送时间
        if($footBallFixturePushAll->push_time){
            if(Carbon::make($footBallFixturePushAll->push_time)->addMinutes($footBallFixturePushAll->hours)->gt(Carbon::now())) return false;
        }
        if($footBallFixturePushAll->is_push_user){
            if($footBallFixturePushAll->slug=='NORECHARGE'){//未充值订单
                return $this->pushMacthTimingSendNoRecharge($footBallFixturePushAll);
            }
        }else{
            return $this->pushMacthTimingSend($footBallFixturePushAll);
        }
    }

    public function pushMacthTimingSendRed(FootBallFixturePushAll $footBallFixturePushAll,$is_myself=0): bool
    {
        try {

            $set_red_command=$footBallFixturePushAll->red_command;
            if($footBallFixturePushAll->push_count%2===0 && $footBallFixturePushAll->red_command1){
                $set_red_command=$footBallFixturePushAll->red_command1;
            }
            $redPacket= RedPacket::query()->where('command',$set_red_command)->first();
            if(!$redPacket) return false;

            $newRedPacket = $redPacket->replicate();
            if ($newRedPacket->name) $newRedPacket->name .= "-机器人复制";
            if ($newRedPacket->status) $newRedPacket->status = true;
            if ($newRedPacket->slug) $newRedPacket->slug = strtoupper(Str::random(6));

            if ($newRedPacket->start_at) $newRedPacket->start_at =Carbon::now();
            if ($newRedPacket->end_at) $newRedPacket->end_at = Carbon::now()->addHours($newRedPacket->valid_hour);
            $newRedPacket->save();

            $red_command=$newRedPacket->command;
            $langList = Language::query()->pluck('id','slug')->toArray();
            foreach ($langList as $key=>$value){
                $text=data_get($footBallFixturePushAll, "config_".$key,"");
                if($text){
                    $this->pushSend($footBallFixturePushAll,$text,$key,$red_command);
                }
            }
            //更新数据
            if($is_myself==0){
                $footBallFixturePushAll->push_time=Carbon::now();
                $footBallFixturePushAll->status=true;
                $footBallFixturePushAll->push_count=$footBallFixturePushAll->push_count+1;
                $footBallFixturePushAll->save();
            }
            return true;
        } catch (\Exception $exception) {
            Log::error("机器自定义推送错误：" . $exception->getMessage());
            return false;
        }
    }

    public function pushMacthTimingSend(FootBallFixturePushAll $footBallFixturePushAll,$is_myself=0): bool
    {
        try {
            $langList = Language::query()->pluck('id','slug')->toArray();
            foreach ($langList as $key=>$value){
                $text=data_get($footBallFixturePushAll, "config_".$key,"");
                if($text){
                    $this->pushSend($footBallFixturePushAll,$text,$key);
                }
            }
            //更新数据
            if($is_myself==0){
                $footBallFixturePushAll->push_time=Carbon::now();
                $footBallFixturePushAll->status=true;
                $footBallFixturePushAll->push_count=$footBallFixturePushAll->push_count+1;
                $footBallFixturePushAll->save();
            }
            return true;
        } catch (\Exception $exception) {
            Log::error("机器自定义推送错误：" . $exception->getMessage());
            return false;
        }
    }



    public function pushMacthTimingSendNoRecharge(FootBallFixturePushAll $footBallFixturePushAll,$is_myself=0): bool
    {
        try {
            $count=0;

            //获取最近未支付成功订单
            $ids=UserRobotSubscribe::query()->where('is_bound_robot_subscribe',true)->pluck('user_id');
            //排出处理过的数据
            $order_sns=RobotPushLog::query()->where('type',$footBallFixturePushAll->slug)->pluck('id');
            Log::info("推送用户类型：".$footBallFixturePushAll->slug,["订阅ID"=>$ids,"处理记录ID"=>$order_sns]);
            UserRechargeOrder::query()
                ->where('is_pay', 0)
                ->where('order_status', 0)
                ->whereIn('user_id', $ids)
                ->whereNotIn('order_sn', $order_sns)
                ->where('created_at', '<', Carbon::now()->subMinutes($footBallFixturePushAll->hours))
                ->where('created_at', '>', Carbon::now()->subMinutes(120))
                ->with(['user'])->lazyById(1, function ($list) use (&$count,$footBallFixturePushAll) {
                    $count = $count + count($list);
                    foreach ($list as $item) {
                        $key=$item->user->local;
                        $text=data_get($footBallFixturePushAll, "config_".$key,"");
                        if($text){
                            $this->pushSend($footBallFixturePushAll,$text,$key,$item->order_sn);
                        }
                        //插入执行过的订单
                        RobotPushLog::query()
                            ->firstOrCreate([
                                'id' => $item->order_sn,
                                'type' => $footBallFixturePushAll->slug,
                            ],[
                                'user_id' => $item->user_id,
                                'local' => $key,
                                'push_at' =>Carbon::now(),
                            ]);
                    }
                });
            Log::error("推送用户类型：".$footBallFixturePushAll->slug." 执行订单：".$count);
            //更新数据
            if($is_myself==0){
                $footBallFixturePushAll->push_time=Carbon::now();
                $footBallFixturePushAll->status=true;
                $footBallFixturePushAll->push_count=$footBallFixturePushAll->push_count+1;
                $footBallFixturePushAll->save();
            }
            return true;
        } catch (\Exception $exception) {
            Log::error("机器自定义推送错误：" . $exception->getMessage());
            return false;
        }
    }




    public function pushSend(FootBallFixturePushAll $footBallFixturePushAll,$content,$language,$key="")
    {
        $count=1;
        foreach ($content as $vinfo){
            $count++;
            $delay=$footBallFixturePushAll->sleep_second * $count;
            $contents=data_get($vinfo, "contents","");

            if($contents){
                $contents=str_replace("{red}",$key,$contents);
                $contents=str_replace("{order_sn}",$key,$contents);
                $params=[
                    "level"=>$footBallFixturePushAll->type,
                    "language"=>(string)$language,
                    "text"=>$contents,
                    "delay"=>$delay,
                ];
                if($footBallFixturePushAll->country){
                    $params['country']=$footBallFixturePushAll->country;
                }
                if($footBallFixturePushAll->bot_name){
                    $params['bot_name']=$footBallFixturePushAll->bot_name;
                }
                if($footBallFixturePushAll->is_top){
                    $params['is_top']=$footBallFixturePushAll->is_top;
                }
                if($this->is_push){
                    $this->httpWorkerman->post($this->pushUrl, $params);
                }
            }
            $img=data_get($vinfo, "icon","");
            if($img){
                $img="https://yfbyfb.oss-ap-southeast-1.aliyuncs.com/".$img;
                $params=[
                    "level"=>$footBallFixturePushAll->type,
                    "delay"=>$delay-3,
                    "language"=>(string)$language,
                    "img_url"=>$img,
                ];
                if($footBallFixturePushAll->country){
                    $params['country']=$footBallFixturePushAll->country;
                }
                if($footBallFixturePushAll->bot_name){
                    $params['bot_name']=$footBallFixturePushAll->bot_name;
                }
                if($footBallFixturePushAll->is_top){
                    $params['is_top']=$footBallFixturePushAll->is_top;
                }

                if($this->is_push){
                    $this->httpWorkerman->post($this->pushUrl, $params);
                }
            }
        }
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
