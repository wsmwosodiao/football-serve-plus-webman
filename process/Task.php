<?php

namespace process;


use App\model\Sport\FootBallFixturePushAll;
use App\Services\RobotPushService;
use App\Services\RobotPushUserService;
use Illuminate\Support\Facades\Log;
use Workerman\Crontab\Crontab;

class Task
{

    public function onWorkerStart()
    {
        // 每3小时执行(整点)【获取24小时赛事图片】
//        new Crontab('0 */3 * * *', function(){
//            RobotPushService::make()->getTodayMacth();
//            echo "获取24小时赛事图片：".date('Y-m-d H:i:s')."\n";
//        });

        // 每5分钟执行一次【2小时内开赛小时赛事图片】
//        new Crontab('*/5 * * * *', function(){
//            RobotPushService::make()->getTodayMacthTwoHoursList();
//            echo "获取2小时内开赛赛事图片：".date('Y-m-d H:i:s')."\n";
//        });


        // 每3分钟的10秒分钟执行一次【赛事图片推送】
//        new Crontab('10 */2 * * * *', function(){
//            RobotPushService::make()->pushMacth();
//            echo "机器人赛事图片推送：".date('Y-m-d H:i:s')."\n";
//        });


        // 每2分钟的20秒分钟执行一次【机器人自定义推送】
        new Crontab('20 */1 * * * *', function(){
            RobotPushService::make()->pushMacthTiming();
            echo "机器人自定义推送：".date('Y-m-d H:i:s')."\n";
        });

        //每整点半小时红包
        new Crontab('*/30 * * * *', function(){
            RobotPushService::make()->pushMacthTimingRed();
            echo "机器人整点红包推送：".date('Y-m-d H:i:s')."\n";
        });

        // 每1小时整点推送  0 */1 * * *
        new Crontab('0 */1 * * *', function(){
            RobotPushService::make()->pushMacthTimingHour();
            echo "每1小时整点推送-昨日收益：".date('Y-m-d H:i:s')."\n";
        });



        // 每1分钟的1秒分钟执行一次【用户站内信】
        new Crontab('1 */2 * * * *', function(){
            RobotPushUserService::make()->pushMacthNotification();
            echo "机器人用户站内信推送：".date('Y-m-d H:i:s')."\n";
        });



        // 每1分钟的1秒分钟执行一次【用户站内信】
        new Crontab('18 */1 * * * *', function(){
            RobotPushService::make()->pushMacthSlug();
            echo "16进球推送推送：".date('Y-m-d H:i:s')."\n";
        });
        // 每1分钟的1秒分钟执行一次【用户站内信】
        new Crontab('38 */1 * * * *', function(){
            RobotPushService::make()->pushMacthSlug();
            echo "36进球推送推送：".date('Y-m-d H:i:s')."\n";
        });
        // 每1分钟的1秒分钟执行一次【用户站内信】
        new Crontab('58 */1 * * * *', function(){
            RobotPushService::make()->pushMacthSlug();
            echo "56进球推送推送：".date('Y-m-d H:i:s')."\n";
        });

//pushMetaGameSend
        // 每分钟执行一次
        new Crontab('18 */1 * * * *', function(){
            RobotPushService::make()->pushMetaGameSend();
            echo "元宇宙推送：".date('Y-m-d H:i:s')."\n";
        });
        // 每分钟执行一次
        new Crontab('18 */1 * * * *', function(){
            RobotPushService::make()->pushMetaGameEndSend();
            echo "元宇宙结果推送：".date('Y-m-d H:i:s')."\n";
        });
//* 10/20 * * *
        // 每20分钟执行一次
        new Crontab('1 * 20/1 * * *', function(){
            RobotPushService::make()->pushDeposit('group1');
            echo "充值推送：".date('Y-m-d H:i:s')."\n";
        });
        // 每20分钟执行一次
        new Crontab('1 * 20/11 * * *', function(){
            RobotPushService::make()->pushDeposit('group2');
            echo "活动推送：".date('Y-m-d H:i:s')."\n";
        });
    }

}