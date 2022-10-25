<?php

namespace App\Services;

use App;
use App\model\Language;
use App\model\RejectInfo;
use App\model\Sport\FootBallFixture;
use App\model\Sport\FootBallFixturePush;
use App\model\Sport\FootBallFixturePushAll;
use App\model\Sport\FootballTeacherFixture;
use Carbon\Carbon;

use support\Log;
use Workerman\Http\Client;


class RobotPushOldService extends BaseService
{

    //protected string $pushUrl='https://api.jooegg.net/api/v1/send';
    protected string $getUrl='https://snap.yfb.net/snapshot?';

    protected string $pushUrl='http://192.168.6.209/api/v1/send';


    public function robotPushMacth(FootBallFixturePushAll $footBallFixturePushAll)
    {
        $http=new Client();

        //Log::info('log test'.$footBallFixturePushAll->id);
        $key="EN";
//        $text=data_get($footBallFixturePushAll, "config_".$key,"");
//
//        $contents=data_get($text[0], "contents","");
//
//        if($contents){
//            $contents=str_replace("-","\-",$contents);
//            $contents=str_replace("——","\——",$contents);
//            $contents=str_replace(".","\.",$contents);
//
//            $params=Array([
//                "level"=>(int)$footBallFixturePushAll->type,
//                "language"=>(string)$key,
//                "text"=>$contents,
//                "delay"=>3,
//            ]);
//            $res=$http->post($this->pushUrl, $params);
//            Log::info("返回内容：",["res"=>$res]);
//            dd($res);
//        }
//        dd($text);
    }






    public function matchRobotPushHandle(FootBallFixturePush $footBallFixturePush): bool
    {
        set_time_limit(0);
        try {
            $images=$footBallFixturePush->images;
            $level=$footBallFixturePush->type;
            if($level==3){
                $level=2;
            }

            if($footBallFixturePush->contents){
                $contents=$footBallFixturePush->contents;
                $contents=str_replace("-","\-",$contents);
                $contents=str_replace("——","\——",$contents);
                $contents=str_replace(".","\.",$contents);
                $params=[
                    "level"=>$level,
                    "language"=>(string)$footBallFixturePush->lang,
                    "text"=>$contents,
                ];
                $res=\Http::asForm()->post($this->pushUrl, $params);
            }
            if($images){
                $i=1;
                foreach ($images as $key=>$v){
                    if($v){
                        $delay=$i*10;
                        $params=[
                            "level"=>$level,
                            "language"=>(string)$footBallFixturePush->lang,
                            "img_url"=>$v,
                            "delay"=>$delay,
                        ];
                        $res=\Http::asForm()->post($this->pushUrl, $params);
                        $i++;
                    }
                }
            }
            if($footBallFixturePush->contents_introduction){
                $contents=$footBallFixturePush->contents_introduction;
                $contents=str_replace("-","\-",$contents);
                $contents=str_replace("——","\——",$contents);
                $contents=str_replace(".","\.",$contents);
                $params=[
                    "level"=>$level,
                    "language"=>(string)$footBallFixturePush->lang,
                    "text"=>$contents,
                    "delay"=>30,
                ];
                $res=\Http::asForm()->post($this->pushUrl, $params);
            }
            //更新数据
            $footBallFixturePush->status=true;
            $footBallFixturePush->push_time=Carbon::now();
            $footBallFixturePush->save();

            return false;
        } catch (\Exception $exception) {
            Log::error("机器自动推送错误：" . $exception->getMessage());
            return false;
        }
    }


    public function matchRobotPushHandleSend(FootBallFixturePushAll $footBallFixturePushAll)
    {

        if($footBallFixturePushAll->end_at){//已结束
            if(Carbon::make($footBallFixturePushAll->end_at)->lt(Carbon::now())) return false;
        }
        //推送时间间隔为0
        if($footBallFixturePushAll->hours == 0) return false;

        //处理是否到达可推送时间
        if($footBallFixturePushAll->push_time){
            if(Carbon::make($footBallFixturePushAll->push_time)->addHours($footBallFixturePushAll->hours)->gt(Carbon::now())) return false;
        }

        return $this->matchRobotPushHandleAll($footBallFixturePushAll);

    }


    public function matchRobotPushHandleAll(FootBallFixturePushAll $footBallFixturePushAll,$is_myself=0): bool
    {
        set_time_limit(0);
        try {
            $langList = Language::query()->pluck('id','slug')->toArray();
            $arr=[];
            $count=0;
            foreach ($langList as $key=>$value){
                $text=data_get($footBallFixturePushAll, "config_".$key,"");
                if($text){
                    $count=0;
                    foreach ($text as $vinfo){
                        $count++;
                        $delay=$footBallFixturePushAll->sleep_second * $count;
                        $contents=data_get($vinfo, "contents","");

                        if($contents){
                            $contents=str_replace("-","\-",$contents);
                            $contents=str_replace("——","\——",$contents);
                            $contents=str_replace(".","\.",$contents);

                            $params=[
                                "level"=>(int)$footBallFixturePushAll->type,
                                "language"=>(string)$key,
                                "text"=>$contents,
                                "delay"=>$delay,
                            ];
                            $res=Http::asForm()->post($this->pushUrl, $params);
                        }
                        $img=data_get($vinfo, "icon","");
                        if($img){
                            $img=ImageUrl($img);
                            $params=[
                                "level"=>(int)$footBallFixturePushAll->type,
                                "delay"=>$delay,
                                "language"=>(string)$key,
                                "img_url"=>$img,
                            ];
                            $res=Http::asForm()->post($this->pushUrl, $params);
                        }
                    }
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
            //Log::error("机器自定义推送错误：");
            return false;
        }
    }


    public function matchRobotPushGenerateHandle(FootBallFixture $footBallFixture): bool
    {
        set_time_limit(0);
        try {

            /**
             * @var FootballTeacherFixture $teacherFixture
             */
            $teacherFixture = collect($footBallFixture->teacherFixture)->filter(function (FootballTeacherFixture $teacherFixture) {
                return $teacherFixture->teacher?->is_rec;
            })->first();
            $odds=(float)ToDecimal2($teacherFixture?->odds * 100);
            $type=1;
            if($odds>2.5){
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
                        //Log::error("机器获取推广图片地址：" . $url);
                        $res = \Http::asJson()->get($url);
                        $count++;
                    }
                }
            }
            Log::info("获取赛事推广图片-赛事：" . $footBallFixture->id." 总共：$count_all ，成功：".$count);
            return true;
        } catch (\Exception $exception) {
            Log::error("机器获取推广图片：" . $exception->getMessage());
            return false;
        }
    }

    public function matchRobotPushGenerateHandleAll(): bool
    {
        $type=3;
        $langList = Language::query()->get();
        $count=0;
        foreach ($langList as $langv) {
            $lang=$langv->slug;
            $url=$this->getUrl."type=$type&id=0&lang=$lang&odds=0&date=";
            $res = \Http::asJson()->get($url);
            $count++;
        }
        Log::info("获取24小时赛事图片:".$count);
        return true;
    }




    public function addRobotPush($id,$images,$type,$odds,$date,$lang='EN')
    {
        $contents="";
        $contents_introduction="";
        if($type==1){
            $contents = RejectInfo::query()->where('group', 'other')->where('slug','PUSH_ORDINARY_EVENTS')->first()->toArray();
            $contents = data_get($contents, "title.$lang","");
            if($contents==""){
                $contents = data_get($contents, "title.EN","");
            }
            $contents_introduction = RejectInfo::query()->where('group', 'other')->where('slug','PUSH_ORDINARY_EVENTS_END')->first()->toArray();
            $contents_introduction = data_get($contents_introduction, "title.$lang","");
            if($contents_introduction==""){
                $contents_introduction = data_get($contents_introduction, "title.EN","");
            }
        }

        $is_push=true;//自动开启

        if($type<3){
            $footBallFixturePush=FootBallFixturePush::query()->where('id',$id)->where('lang',$lang)->where('type',$type)->get()->first();
            if(!$footBallFixturePush){
                $footBallFixturePush= FootBallFixturePush::query()
                    ->Create([
                        'id' => $id,
                        'lang' => $lang,
                        'type' => $type,
                        'images' => $images,
                        'odds' => $odds,
                        'status' => false,
                        'is_push' => $is_push,
                        'date'=>$date,
                        'contents' => $contents,
                        'contents_introduction' =>$contents_introduction,
                    ]);
                return $footBallFixturePush;
            }
        }else{
            //10分钟内不再更新
            $footBallFixturePush=FootBallFixturePush::query()->where('created_at','>',Carbon::now()->subMinutes(10))->where('type',$type)->get()->first();
            if(!$footBallFixturePush){
                $footBallFixturePush= FootBallFixturePush::query()
                    ->Create([
                        'id' => $id,
                        'lang' => $lang,
                        'type' => $type,
                        'images' => $images,
                        'odds' => $odds,
                        'status' => false,
                        'is_push' => $is_push,
                        'date'=>$date,
                        'contents' => $contents,
                        'contents_introduction' =>$contents_introduction,
                    ]);
            }
            return $footBallFixturePush;
        }
    }


}
