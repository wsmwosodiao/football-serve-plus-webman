<?php

namespace App\model\Game;

use App\Traits\MongoAttribute;
use Dcat\Admin\Traits\HasDateTimeFormatter;
use Jenssegers\Mongodb\Eloquent\Model;

/**
 * @property string $_id 主键
 * @property int $round 第几轮
 * @property string $start_time 开始时间
 * @property string $end_time 结束时间
 * @property boolean $game_over 是否结束
 * @property string $shot_direction 击打方向
 * @property string $shot_result 击打结果
 * @property string $guard_direction 防守方向
 * @property float $bet_y
 * @property float $bet_n
 */
class ShotGame extends Model
{
    use MongoAttribute;

    protected $connection = 'mongodb';
    protected $collection = 'shot_game';

    protected $dates = [
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'round' => 'integer',
        'game_over' => 'boolean',
        'bet_y' => 'float',
        'bet_n' => 'float',
    ];


    public const shootDirection = ['l', 't', 'r'];
    public const shootResult = ['y', 'n'];

    //下注详情
    public function getBetSumAttribute()
    {
        $bet_data = (array)$this->bet_data;
        $data = [];
        foreach ($bet_data as $key => $value) {
            $count = data_get($value, 'count', 0);
            $amount_money = data_get($value, 'amount_money', 0);


            $data[$key] = [
                'count' => $count,
                'amount_money' => $amount_money,
            ];
        }
        return $data;
    }

}
