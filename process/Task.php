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
        // 每1小时第2分执行【获取24小时赛事图片】
        new Crontab('2 */1 * * *', function(){
            RobotPushService::make()->getTodayMacth();
            echo "获取获取24小时赛事图片：".date('Y-m-d H:i:s')."\n";
        });

        // 每5分钟执行一次【2小时内开赛小时赛事图片】
        new Crontab('*/5 * * * *', function(){
            RobotPushService::make()->getTodayMacthTwoHoursList();
            echo "获取2小时内开赛赛事图片：".date('Y-m-d H:i:s')."\n";
        });


        // 每5分钟的1秒分钟执行一次【赛事图片推送】
        new Crontab('10 */3 * * * *', function(){
            RobotPushService::make()->pushMacth();
            echo "机器人赛事图片推送：".date('Y-m-d H:i:s')."\n";
        });


        // 每2分钟的20秒分钟执行一次【机器人自定义推送】
        new Crontab('20 */2 * * * *', function(){
            RobotPushService::make()->pushMacthTiming();
            echo "机器人自定义推送：".date('Y-m-d H:i:s')."\n";
        });


//        // 每1秒执行
//        new Crontab('*/1 * * * * *', function(){
//            RobotPushUserService::make()->pushMacthNotification();
//            echo "每1秒执行：".date('Y-m-d H:i:s')."\n";
//        });





        // 每1分钟的1秒分钟执行一次【用户站内信】
        new Crontab('1 */1 * * * *', function(){
            RobotPushUserService::make()->pushMacthNotification();
            echo "机器人用户站内信推送：".date('Y-m-d H:i:s')."\n";
        });

        // 每50分钟的40秒分钟执行一次【用户赛事信息】
        new Crontab('30 */3 * * * *', function(){
            RobotPushUserService::make()->pushMacthPicture();
            echo "机器人用户赛事信息推送：".date('Y-m-d H:i:s')."\n";
        });


    }

}