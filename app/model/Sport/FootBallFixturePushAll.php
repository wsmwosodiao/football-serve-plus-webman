<?php

namespace App\model\Sport;

use App\Traits\MongoAttribute;
use Carbon\Carbon;
use Dcat\Admin\Traits\HasDateTimeFormatter;
use Jenssegers\Mongodb\Eloquent\Model;

/**
 * 机器人推送
 * @property integer $type 类型1-2-3
 * @property integer $push_count 推送次数
 * @property integer $hours 间隔小时
 * @property integer $sleep_second 睡眠多少秒
 * @property boolean $status //是否推送成功
 * @property boolean $is_push //是否可以推送
 * @property boolean $is_push_user //是否是用户类型
 * @property boolean $is_top //是否置顶
 */
class FootBallFixturePushAll extends Model
{

    use MongoAttribute;

    protected $connection = 'mongodb';
    protected $table = 'foot_ball_fixture_push_all';
    protected $guarded = [];


    protected $dates = [
        'date',
        'push_time',
    ];

    protected $casts = [
        'type' => 'integer',
        'push_count' => 'integer',
        'hours' => 'integer',
        'sleep_second'=> 'integer',
        'status' => 'boolean',
        'is_push' => 'boolean',
        'is_push_user' => 'boolean',
        'is_top'=> 'boolean',
    ];

    public const PUSHTYPE = [
        1 => "普通群",
        2 => "VIP群",
    ];
}
