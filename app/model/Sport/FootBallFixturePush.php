<?php

namespace App\model\Sport;

use App\Traits\MongoAttribute;
use Jenssegers\Mongodb\Eloquent\Model;

/**
 * 机器人推送
 * @property integer $id 比赛ID
 * @property integer $type 类型1-2-3
 * @property float $odds 赔率
 * @property string $lang 语言标识
 * @property boolean $status //是否推送成功
 * @property boolean $is_push //是否可以推送
 */
class FootBallFixturePush extends Model
{

    use MongoAttribute;

    protected $connection = 'mongodb';
    protected $table = 'foot_ball_fixture_push';
    protected $guarded = [];


    protected $dates = [
        'date',
        'push_time',
        'push_user_time'
    ];

    protected $casts = [
        'id' => 'integer',
        //'images' => 'array',
        'type' => 'integer',
        'odds' => 'float',
        'status' => 'boolean',
        'is_push' => 'boolean',
        'lang'=>'string',
    ];

    public const PUSHTYPE = [
        1 => "普通",
        2 => "VIP > 2.5",
        3 => "VIP-24小时赛事",
    ];
}
