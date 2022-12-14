<?php

namespace App\Services;

use App;

use App\model\Game\GameCrontab;
use App\model\Game\MetaFootballGame;
use App\model\Language;
use App\model\Notification;
use App\model\RedPacket;
use App\model\RobotPushLog;
use App\model\Sport\FootBallAfterImgPush;
use App\model\Sport\FootBallFixture;
use App\model\Sport\FootBallFixturePush;
use App\model\Sport\FootBallFixturePushAll;
use App\model\Sport\FootballTeacherFixture;
use App\model\UserRechargeOrder;
use App\model\UserRobotSubscribe;
use App\model\WalletLogDayDataMongo;
use App\model\Game\ShotGame;
use Carbon\Carbon;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use mysql_xdevapi\Exception;
use support\Log;
use Workerman\Http\Client;
use Workerman\Protocols\Http;
use Workerman\Worker;
use WebmanTech\LaravelHttpClient\Facades\Http as LHttp;


class RobotPushService extends BaseService
{

    protected string $getUrl = 'https://snap.yfb.net/snapshot?';
    protected string $pushUserUrl = 'https://api.jooegg.net/api/v1/sendUser';
    protected string $pushUrl = 'https://api.jooegg.net/api/v1/send';//'http://192.168.6.209/api/v1/send';
    protected $httpWorkerman;
    protected bool $is_push = true; //是否正式推送
    protected bool $is_Debug = false;

    protected string $bot_name = '55data';


    public function __construct()
    {
        $this->is_Debug = Worker::$daemonize;//true 正式环境
        if ($this->is_Debug) {
            $this->pushUrl = 'https://api.jooegg.net/api/v1/send';
        }
        $this->httpWorkerman = new Client();//请求类
    }

    /**
     * 获取24小时赛事图片
     */
    public function getTodayMacth()
    {
        $type = 3;
        $langList = Language::query()->get();
        $count = 0;
        foreach ($langList as $item) {
            $lang = $item->slug;
            $id = Carbon::now()->format('YmdH');
            if ($this->is_Debug) {
                $url = $this->getUrl . "type=$type&id=$id&lang=$lang&odds=0&date=";
                //Log::info("获取24小时赛事图片处理:".$url);
                $this->httpWorkerman->get($url);

            }
            $count++;
        }
        Log::info("获取24小时赛事图片:" . $count);
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

            if (Carbon::make($footBallFixture->date)->lt(Carbon::now())) return false;

            /**
             * @var FootballTeacherFixture $teacherFixture
             */
            $teacherFixture = collect($footBallFixture->teacherFixture)->filter(function (FootballTeacherFixture $teacherFixture) {
                return $teacherFixture->teacher?->is_rec;
            })->first();
            $odds = (float)$this->toDecimal2($teacherFixture?->odds * 100);
            $type = 1;
            if ($odds > 2) {
                $type = 2;
            }
            $count = 0;
            $count_all = 0;
            foreach ((array)$footBallFixture->remark as $key => $value) {
                $count_all++;
                if ($value) {
                    $lang = $key;
                    $footBallFixturePush = FootBallFixturePush::query()->where('type', $type)->where('lang', $lang)->where('id', $footBallFixture->id)->first();
                    if (!$footBallFixturePush) {
                        $url = $this->getUrl . "type=$type&id=$footBallFixture->id&lang=$lang&odds=$odds&date=$footBallFixture->date";
                        //Log::info("获取2小时赛事图片处理:".$url);
                        if ($this->is_Debug) {
                            //Log::info("获取2小时赛事图片处理-请求:".$url);
                            $this->httpWorkerman->get($url);
                        }
                        $count++;
                    }
                }
            }
            Log::info("获取2小时赛事推广图片-赛事：" . $footBallFixture->id . " 总共：$count_all ，成功：" . $count);
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
                    $this->upPushMacth($item, $count);
                }
            });
    }

    public function upPushMacth(FootBallFixturePush $footBallFixturePush, $counts_second = 1): bool
    {
        set_time_limit(0);
        try {

            $is_send = true;
            $level = $footBallFixturePush->type;
            if ($level > 2) {//发送VIP
                $level = 2;
            }
            $sleep_second = 3 * $counts_second;
            if ($is_send) {
                $images = $footBallFixturePush->images;
                if ($footBallFixturePush->contents) {
                    $contents = $footBallFixturePush->contents;
//                    $contents=str_replace("-","\-",$contents);
//                    $contents=str_replace("——","\——",$contents);
//                    $contents=str_replace(".","\.",$contents);
//                    $contents=str_replace("+","\+",$contents);
                    $params = [
                        "level" => $level,
                        "language" => $footBallFixturePush->lang,
                        "text" => $contents,
                        "delay" => 0,
                    ];
                    if ($footBallFixturePush->country) {
                        $params['country'] = $footBallFixturePush->country;
                    }
                    $params['bot_name'] = $this->bot_name;//强制设置机器人为55data
                    if ($this->is_push) {
                        $this->httpWorkerman->post($this->pushUrl, $params);
                    }
                }
                if ($images) {
                    $i = 1;
                    foreach ($images as $key => $v) {
                        if ($v) {
                            $delay = $i * $sleep_second - 2;
                            $params = [
                                "level" => $level,
                                "language" => $footBallFixturePush->lang,
                                "img_url" => $v,
                                "delay" => $delay,
                            ];
                            if ($footBallFixturePush->country) {
                                $params['country'] = $footBallFixturePush->country;
                            }
                            $params['bot_name'] = $this->bot_name;//强制设置机器人为55data
                            if ($this->is_push) {
                                $this->httpWorkerman->post($this->pushUrl, $params);
                            }
                            $i++;
                        }
                    }
                }
                if ($footBallFixturePush->contents_introduction) {
                    $contents = $footBallFixturePush->contents_introduction;
//                    $contents=str_replace("-","\-",$contents);
//                    $contents=str_replace("——","\——",$contents);
//                    $contents=str_replace(".","\.",$contents);
//                    $contents=str_replace("+","\+",$contents);
                    $params = [
                        "level" => $level,
                        "language" => $footBallFixturePush->lang,
                        "text" => $contents,
                        "delay" => $sleep_second + 5,
                    ];
                    if ($footBallFixturePush->country) {
                        $params['country'] = $footBallFixturePush->country;
                    }
                    $params['bot_name'] = $this->bot_name;//强制设置机器人为55data
                    if ($this->is_push) {
                        $this->httpWorkerman->post($this->pushUrl, $params);
                    }
                }
                //更新数据
                $footBallFixturePush->status = true;
                $footBallFixturePush->push_time = Carbon::now();
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
            ->whereNull('slug')
            ->where('date', '<', Carbon::now())//开始时间
            ->chunk(5, function ($list) use (&$count) {
                $count = $count + count($list);
                foreach ($list as $item) {
                    $this->upPushMacthTiming($item);
                }
            });
        if ($count > 0) {
            Log::info("自定义推送任务：" . $count);
        }

    }

    /**
     * 推送自定义推送内容
     */
    public function pushMacthTimingHour()
    {
        $count = 0;
        FootBallFixturePushAll::query()
            ->where('is_push', true)
            ->where('slug', 'COMMISSION')
            ->where('date', '<', Carbon::now())//开始时间
            ->lazyById(1)->each(function ($item) use (&$count) {
                $count++;
                $this->upPushMacthTiming($item, 1);
            });
        if ($count > 0) {
            Log::info("整点收益推送：" . $count);
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
            ->where('slug', 'RED')
            ->where('date', '<', Carbon::now())//开始时间
            ->where('is_push_user', false)
            ->chunk(1, function ($list) use (&$count) {
                $count = $count + count($list);
                foreach ($list as $item) {
                    $this->upPushMacthTiming($item, 1);
                }
            });
        Log::info("红包自定义推送任务：" . $count);

    }

    public function upPushMacthTiming(FootBallFixturePushAll $footBallFixturePushAll, $type = 0)
    {

        if ($footBallFixturePushAll->end_at) {//已结束
            if (Carbon::make($footBallFixturePushAll->end_at)->lt(Carbon::now())) return false;
        }
        if ($footBallFixturePushAll->slug == 'RED' && $type == 1) {
            return $this->pushMacthTimingSendRed($footBallFixturePushAll);//口令红包推送到群
        }

        if ($footBallFixturePushAll->slug == 'COMMISSION' && $type == 1) {
            $hour = (int)Carbon::now()->format('H');//整点对应时间--小时计算
            Log::info("自定义推送任务执行小时：" . $hour . " 实际发放小时：" . $footBallFixturePushAll->hours);
            if ($hour == $footBallFixturePushAll->hours) {//$hour==$footBallFixturePushAll->hours
                return $this->pushMacthTimingSendCommission($footBallFixturePushAll);//收益推送
            } else {
                return false;
            }
        }
        //推送时间间隔为0
        if ($footBallFixturePushAll->hours == 0) return false;
        //处理是否到达可推送时间
        if ($footBallFixturePushAll->push_time) {
            if (Carbon::make($footBallFixturePushAll->push_time)->addMinutes($footBallFixturePushAll->hours)->gt(Carbon::now())) return false;
        }
        if ($footBallFixturePushAll->is_push_user) {
            if ($footBallFixturePushAll->slug == 'NORECHARGE') {//未充值订单
                return $this->pushMacthTimingSendNoRecharge($footBallFixturePushAll);
            }
        } else {
            return $this->pushMacthTimingSend($footBallFixturePushAll);
        }
    }

    public function pushMacthTimingSendCommission(FootBallFixturePushAll $footBallFixturePushAll, $is_myself = 0): bool
    {
        try {
            $count = 0;

            //获取最近未支付成功订单
            $ids = UserRobotSubscribe::query()->where('is_bound_robot_subscribe', true)->pluck('user_id');
            $time = Carbon::yesterday();
            if ($footBallFixturePushAll->other_slug && $footBallFixturePushAll->other_slug == 'today') {
                $time = Carbon::today();
            }
            WalletLogDayDataMongo::query()
                ->whereIn('user_id', $ids)
                ->whereNull('is_push')
                ->where('day', $time)
                ->limit(1)
                ->lazyById(1)->each(function ($item) use (&$count, $footBallFixturePushAll, $time) {
                    $count++;
                    //\Webman\RedisQueue\Client::send("send-commission", ["id"=>$item->getKey(),"content_id"=>$footBallFixturePushAll->getKey()]); //链接redis 超时
                    $this->pushMacthTimingQueue($item->getKey(), $footBallFixturePushAll->getKey());
                });
            Log::error("推送用户昨日收益：" . $footBallFixturePushAll->slug . " 执行订单：" . $count);
            //更新数据
            if ($is_myself == 0) {
                $footBallFixturePushAll->push_time = Carbon::now();
                $footBallFixturePushAll->status = true;
                $footBallFixturePushAll->push_count = $footBallFixturePushAll->push_count + 1;
                $footBallFixturePushAll->save();
            }
            return true;
        } catch (\Exception $exception) {
            Log::error("pushMacthTimingSendCommission 机器自定义推送错误：" . $exception->getMessage());
            return false;
        }
    }

    public function pushMacthTimingQueue($id, $content_id)
    {
        $walletLogDayDataMongo = WalletLogDayDataMongo::query()->with(['user'])->find($id);
        $footBallFixturePushAll = FootBallFixturePushAll::query()->find($content_id);
        if ($walletLogDayDataMongo && $footBallFixturePushAll) {
            $key = $walletLogDayDataMongo->user->local;
            $text = data_get($footBallFixturePushAll, "config_" . $key, "");
            $usdt = $walletLogDayDataMongo->USDT_commission ?: 0;
            $usdt = sprintf("%.2f", substr(sprintf("%.3f", $usdt), 0, -2));
            if ($text && $usdt > 0) {
                $this->pushSend($footBallFixturePushAll, $text, $key, $usdt, $walletLogDayDataMongo->user->referral_code);//$walletLogDayDataMongo->user->referral_code
            } else {
                Log::error($walletLogDayDataMongo->user_id . " 用户 " . $walletLogDayDataMongo->user->referral_code . " 收益金额：" . $usdt . " - 不推送");
            }
            $walletLogDayDataMongo->is_push = true;
            $walletLogDayDataMongo->push_at = Carbon::now();
            $walletLogDayDataMongo->save();
        }
    }


    public function pushMacthTimingSendRed(FootBallFixturePushAll $footBallFixturePushAll, $is_myself = 0): bool
    {
        try {

            $set_red_command = $footBallFixturePushAll->red_command;
            if ($footBallFixturePushAll->push_count % 2 === 0 && $footBallFixturePushAll->red_command1) {
                $set_red_command = $footBallFixturePushAll->red_command1;
            }
            $redPacket = RedPacket::query()->where('command', $set_red_command)->first();
            if (!$redPacket) return false;

            $newRedPacket = $redPacket->replicate();
            if ($newRedPacket->name) $newRedPacket->name .= "-机器人复制";
            if ($newRedPacket->status) $newRedPacket->status = true;
            if ($newRedPacket->slug) $newRedPacket->slug = strtoupper(Str::random(6));

            if ($newRedPacket->start_at) $newRedPacket->start_at = Carbon::now();
            if ($newRedPacket->end_at) $newRedPacket->end_at = Carbon::now()->addMinutes(30);
            $newRedPacket->save();

            $red_command = $newRedPacket->command;
            $langList = Language::query()->pluck('id', 'slug')->toArray();
            foreach ($langList as $key => $value) {
                $text = data_get($footBallFixturePushAll, "config_" . $key, "");
                if ($text) {
                    $this->pushSend($footBallFixturePushAll, $text, $key, $red_command);
                }
            }
            //更新数据
            if ($is_myself == 0) {
                $footBallFixturePushAll->push_time = Carbon::now();
                $footBallFixturePushAll->status = true;
                $footBallFixturePushAll->push_count = $footBallFixturePushAll->push_count + 1;
                $footBallFixturePushAll->save();
            }
            return true;
        } catch (\Exception $exception) {
            Log::error("pushMacthTimingSendRed 机器自定义推送错误：" . $exception->getMessage());
            return false;
        }
    }


    /**
     * 推送自定义推送内容-Slug
     */
    public function pushMacthSlug($slug = "FOOTY")
    {
        $footBallFixturePushAll = FootBallFixturePushAll::query()
            ->where('is_push', true)
            ->where('date', '<', Carbon::now())//开始时间
            ->where('slug', $slug)->first();

        if ($footBallFixturePushAll) {
            $list = ShotGame::query()->where('game_over', true)->orderByDesc('round')->take(20)->get();
            /** @var ShotGame $last */
            $last = $list->first();
            //结果类型 y进球 n未进球
            $result = $last->shot_result;
            //连续次数
            $round = "";
            $count = 0;
            /** @var ShotGame $item */
            foreach ($list as $item) {
                if ($result == $item->shot_result) {
                    $round .= $item->round . '
                    ';
                    $count++;
                } else {
                    break;
                }
            }

            if ($result == 'n' && $count > 2) {
                $slug = "FOOTN";
                $footBallFixturePushAll = FootBallFixturePushAll::query()
                    ->where('is_push', true)
                    ->where('slug', $slug)->first();
            }

            if ($count > 2 && $footBallFixturePushAll) {
                $map = ['num' => $count, 'number_info' => $round];
                $this->pushMacthTimingSend($footBallFixturePushAll, 0, $round, $map, 1);
                Log::info("自定义推送任务Slug：" . $slug);
            }

        }


    }


    public function pushMetaGameSend($slug = 'META0')
    {
        $footBallFixturePushAll = FootBallFixturePushAll::query()
            ->where('is_push', true)
            ->where('date', '<', Carbon::now())//开始时间
            ->where('slug', $slug)->first();

        if ($footBallFixturePushAll) {
            $list = MetaFootballGame::query()->where('game_over', true)->orderByDesc('end_time')->take(20)->get();
            /** @var MetaFootballGame $last */
            $last = $list->first();
            /** @var GameCrontab $game */
            $game = GameCrontab::query()->first();
            if (!$game) {
                $game = GameCrontab::query()->create(['meta_round' => '1', 'shot_round' => '1']);
            }
            if ($game->meta_round == $last->round) {
                return;
            }
            $game->meta_round = $last->round;
            $game->save();

            $result = $this->getMetaResult($last->home_team_score, $last->away_team_score);
            //连续次数
            $round = "";
            $count = 0;
            /** @var MetaFootballGame $item */
            foreach ($list as $item) {
                if ($result == $this->getMetaResult($item->home_team_score, $item->away_team_score)) {
                    $round .= $item->round . '
                    ';
                    $count++;
                } else {
                    break;
                }
            }

            if ($count > 2) {
                if ($result != '0') {
                    if ($result == '1') {
                        $slug = "META1";
                    } elseif ($result == '2') {
                        $slug = "META2";
                    }
                    $footBallFixturePushAll = FootBallFixturePushAll::query()
                        ->where('is_push', true)
                        ->where('slug', $slug)->first();
                }
                $map = ['num' => $count, 'number_info' => $round];
                $this->pushMacthTimingSend($footBallFixturePushAll, 0, $round, $map, 1);
                Log::info("自定义推送任务Slug：" . $slug);
            }

        }
    }

    public function pushMetaGameEndSend($slug = 'META4')
    {
        $footBallFixturePushAll = FootBallFixturePushAll::query()
            ->where('is_push', true)
            ->where('date', '<', Carbon::now())//开始时间
            ->where('slug', $slug)->first();

        if ($footBallFixturePushAll) {
            /** @var MetaFootballGame $last */
            $last = MetaFootballGame::query()->where('game_over', true)->orderByDesc('end_time')->first();
            /** @var GameCrontab $game */
            $game = GameCrontab::query()->first();
            if (!$game) {
                $game = GameCrontab::query()->create(['meta_round' => '1', 'shot_round' => '1']);
            }
            if ($game->meta_end_round == $last->round) {
                return;
            }
            $game->meta_end_round = $last->round;
            $game->save();


            $round = $last->game_score;
            $count = $last->home_team_name . ' VS ' . $last->away_team_name;

            $map = ['num' => $count, 'number_info' => $round];
            $this->pushMacthTimingSend($footBallFixturePushAll, 0, $round, $map, 1);
            Log::info("自定义推送任务Slug：" . $slug);
        }
    }

    public function pushDepositSend()
    {
        /** @var GameCrontab $game */
        $game = GameCrontab::query()->first();
        $footBallFixturePushAll = FootBallFixturePushAll::query()
            ->where('is_push', true)
            ->where('date', '<', Carbon::now())//开始时间
            ->where('slug', $game->group_type)->first();
        try {
            if ($footBallFixturePushAll) {
                if ($game->group_type == 'GROUP1') {
                    $timediff = strtotime(Carbon::now()) - strtotime($game->group1);
                    $game->group_type = 'GROUP2';
                } else {
                    $timediff = strtotime(Carbon::now()) - strtotime($game->group2);
                    $game->group_type = 'GROUP1';
                }
                //计算小时数
                $remain = $timediff % 86400;
                $hours = intval($remain / 60);
                //Log::info('hour',['hour'=>$hours,'now'=>Carbon::now(),'game'=>$game]);
                if ($hours < 10) {
                    return;
                }
                if ($game->group_type == 'GROUP1') {
                    $game->group1 = Carbon::now();
                } else {
                    $game->group2 = Carbon::now();
                }
                $game->save();
                $this->pushMacthTimingSend($footBallFixturePushAll);
                Log::info("自定义推送任务Slug：" . $game->group_type);
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }

    public function getMetaResult(int $a, int $b)
    {
        if ($a == $b) {
            return 0;
        } else {
            if ($a > $b) {
                return 1;
            } else {
                return 2;
            }
        }
    }

    public function pushMacthTimingSend(FootBallFixturePushAll $footBallFixturePushAll, $is_myself = 0, $keys = "", $map = [], $waiter = 0): bool
    {
        try {
            $langList = Language::query()->pluck('id', 'slug')->toArray();
            foreach ($langList as $key => $value) {
                $text = data_get($footBallFixturePushAll, "config_" . $key, "");
                if ($text) {
                    $this->pushSend($footBallFixturePushAll, $text, $key, $keys, '', $map, $waiter);
//                    if($keys){
//                        Log::info("推送值：".$keys.", Slug:".$footBallFixturePushAll->slug);
//                    }
                }
            }
            //更新数据
            if ($is_myself == 0) {
                $footBallFixturePushAll->push_time = Carbon::now();
                $footBallFixturePushAll->status = true;
                $footBallFixturePushAll->push_count = $footBallFixturePushAll->push_count + 1;
                $footBallFixturePushAll->save();
            }
            return true;
        } catch (\Exception $exception) {
            Log::error("pushMacthTimingSend 机器自定义推送错误：" . $exception->getMessage());
            return false;
        }
    }


    public function pushMacthTimingSendNoRecharge(FootBallFixturePushAll $footBallFixturePushAll, $is_myself = 0): bool
    {
        try {
            $count = 0;

            //获取最近未支付成功订单
            $ids = UserRobotSubscribe::query()->where('is_bound_robot_subscribe', true)->pluck('user_id');
            //排出处理过的数据
            $order_sns = RobotPushLog::query()->where('type', $footBallFixturePushAll->slug)->pluck('id');
            UserRechargeOrder::query()
                ->where('is_pay', 0)
                ->where('order_status', 0)
                ->whereIn('user_id', $ids)
                ->whereNotIn('order_sn', $order_sns)
                ->where('created_at', ">", Carbon::now()->subMinutes(20))
                ->with(['user'])->lazyById(10)->each(function ($item) use (&$count, $footBallFixturePushAll) {
                    if (Carbon::make($item->created_at)->addMinutes(10)->lt(Carbon::now())) {
                        $count++;
                        $key = $item->user->local;
                        $text = data_get($footBallFixturePushAll, "config_" . $key, "");
                        if ($text) {
                            $this->pushSend($footBallFixturePushAll, $text, $key, $item->order_sn, $item->user->referral_code);
                        }
                        //插入执行过的订单
                        RobotPushLog::query()
                            ->firstOrCreate([
                                'id' => $item->order_sn,
                                'type' => $footBallFixturePushAll->slug,
                            ], [
                                'user_id' => $item->user_id,
                                'referral_code' => $item->user->referral_code,
                                'local' => $key,
                                'push_at' => Carbon::now(),
                            ]);
                    }
                });
            Log::error("推送用户类型：" . $footBallFixturePushAll->slug . " 执行订单：" . $count);
            //更新数据
            if ($is_myself == 0) {
                $footBallFixturePushAll->push_time = Carbon::now();
                $footBallFixturePushAll->status = true;
                $footBallFixturePushAll->push_count = $footBallFixturePushAll->push_count + 1;
                $footBallFixturePushAll->save();
            }
            return true;
        } catch (\Exception $exception) {
            Log::error("pushMacthTimingSendNoRecharge 机器自定义推送错误：" . $exception->getMessage());
            return false;
        }
    }


    //wait 0正常逻辑，1等待图片生成，2图片已生成
    public function pushSend(FootBallFixturePushAll $footBallFixturePushAll, $content, $language, $key = "", $referral_code = "", $map = [], $wait = 0, $after_img = '')
    {
        $count = 1;
        $imgjson = '{"shot_game":{"CN":6,"EN":7,"TH":8,"VI":9,"ID":10,"PT":11,"KR":12,"MY":13,"ES":14,"JP":16,"TR":17,"RU":18,"IT":19,"FR":20,"AR":21},"meta_game":{"CN":22,"EN":23,"TH":24,"VI":25,"ID":26,"PT":27,"KR":28,"MY":29,"ES":30,"JP":31,"TR":32,"RU":33,"IT":34,"FR":35,"AR":36}}';
        $imgjson = json_decode($imgjson);
        $people_num = LHttp::get('http://172.19.122.84/api/v1/commonData')->json();
        foreach ($content as $vinfo) {
            if ($wait == 1) {
                try {

                    if (str_contains($footBallFixturePushAll->slug, 'META')) {
                        $img_id = data_get($imgjson, 'meta_game.' . $language);
                        $num = data_get($people_num, 'data.meta_amount');
                    } else {
                        $img_id = data_get($imgjson, 'shot_game.' . $language);
                        $num = data_get($people_num, 'data.shot_amount');
                    }

                    Log::info("data", [
                        "num" => $num,
                        "id" => $img_id,
                    ]);

                    $tu = LHttp::withHeaders([
                        'token' => 'ApfrIzxCoK1DwNZOEJCwlrnv6QZ0PCdv',
                        'Content-Type' => 'application/json'
                    ])->post('http://172.28.237.28/api/link', ['text' => (string)$num, 'id' => $img_id]);
                    $url = data_get($tu, 'data.url');
                    $after_img = str_replace('127.0.0.1:5000', '8.219.142.190', $url);
                } catch (\Exception $e) {
                    Log::error('waiter1:' . $e->getMessage());
                }

            }
            $count++;
            $delay = $footBallFixturePushAll->sleep_second * $count;
            $contents = data_get($vinfo, "contents", "");
            if ($contents) {
                if ($wait == 1) {
                    $contents = str_replace("{num}", data_get($map, 'num'), $contents);
                    $contents = str_replace("{number_info}", data_get($map, 'number_info'), $contents);
                } else {
                    $contents = str_replace("{red}", $key, $contents);
                    $contents = str_replace("{order_sn}", $key, $contents);
                    $contents = str_replace("{commission}", $key, $contents);
                    $contents = str_replace("{count}", $key, $contents);
                }
                $contents = str_replace('\\n', '\n', $contents);
                $contents = str_replace("                    ", '', $contents);
                $contents = str_replace("                   ", '', $contents);

                $params = [
                    "level" => $footBallFixturePushAll->type,
                    "language" => (string)$language,
                    "text" => $contents,
                    "delay" => $delay,
                ];
                if ($footBallFixturePushAll->country) {
                    $params['country'] = $footBallFixturePushAll->country;
                }
                if ($footBallFixturePushAll->bot_name) {
                    $params['bot_name'] = $footBallFixturePushAll->bot_name;
                }
                if ($footBallFixturePushAll->is_top) {
                    $params['is_top'] = $footBallFixturePushAll->is_top;
                }
                if ($referral_code) {
                    $params['referral_code'] = $referral_code;
                }
                if ($this->is_push) {
                    if ($referral_code) {
                        $this->httpWorkerman->post($this->pushUserUrl, $params);
                    } else {
                        $this->httpWorkerman->post($this->pushUrl, $params);
                    }
                }

                if ($footBallFixturePushAll->slug == "FOOTN" || $footBallFixturePushAll->slug == "FOOTY") {
                    if ($this->is_push) {
                        $type = (int)$footBallFixturePushAll->type;
                        if ($type == 2) {
                            $params['level'] = 1;
                        } else {
                            $params['level'] = 2;
                        }
                        //Log::info("再次推送推送任务Slug：".$footBallFixturePushAll->slug,["params"=>$params]);
                        if ($referral_code) {
                            $this->httpWorkerman->post($this->pushUserUrl, $params);
                        } else {
                            $this->httpWorkerman->post($this->pushUrl, $params);
                            // Log::info("1再次推送推送任务Slug：".$footBallFixturePushAll->slug,["params"=>$params]);
                        }
                    }
                }


            }
            $img = data_get($vinfo, "icon", "");
            if ($img || $wait == 1) {
                if ($wait == 1) {
                    $img = $after_img;
                    Log::info('after_img' . $after_img);
                } else {
                    $img = "https://yfbyfb.oss-ap-southeast-1.aliyuncs.com/" . $img;
                }
                $params = [
                    "level" => $footBallFixturePushAll->type,
                    "delay" => $delay - 3,
                    "language" => (string)$language,
                    "img_url" => $img,
                ];
                if ($footBallFixturePushAll->country) {
                    $params['country'] = $footBallFixturePushAll->country;
                }
                if ($footBallFixturePushAll->bot_name) {
                    $params['bot_name'] = $footBallFixturePushAll->bot_name;
                }
                if ($footBallFixturePushAll->is_top) {
                    $params['is_top'] = $footBallFixturePushAll->is_top;
                }
                if ($referral_code) {
                    $params['referral_code'] = $referral_code;
                }
                if ($this->is_push) {
                    if ($referral_code) {
                        $this->httpWorkerman->post($this->pushUserUrl, $params);
                    } else {
                        $this->httpWorkerman->post($this->pushUrl, $params);
                    }

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
