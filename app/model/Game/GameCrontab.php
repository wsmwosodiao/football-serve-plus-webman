<?php

namespace App\model\Game;

use App\Traits\MongoAttribute;
use Jenssegers\Mongodb\Eloquent\Model;

/**
 * @property string $_id 主键
 * @property string $shot_round 射击游戏上次执行场次
 * @property string $meta_round 元宇宙上次执行场次
 * @property string $meta_end_round 元宇宙结果上次执行场次
 */
class GameCrontab extends Model
{
    use MongoAttribute;
    protected $connection = 'mongodb';
    protected $collection = 'game_crontab';
    protected $guarded = [];
    protected $dates = [
        'group1',
        'group2',
    ];
    protected $casts = [];
}