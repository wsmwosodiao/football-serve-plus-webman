<?php

namespace App\model\Game;

use App\Traits\MongoAttribute;
use Carbon\Carbon;
use Dcat\Admin\Traits\HasDateTimeFormatter;
use Jenssegers\Mongodb\Eloquent\Model;

/**
 * @property string $_id
 * @property string $round 轮次
 * @property string $start_time 开始时间
 * @property string $end_time 结束时间
 * @property string $home_team_id 主队ID
 * @property string $home_team_name 主队名称
 * @property string $away_team_id 客队ID
 * @property string $away_team_name 客队名称
 * @property boolean $game_over 是否结束
 * @property string $game_score 比分
 * @property integer $home_team_score 主队比分
 * @property integer $away_team_score 客队比分
 * @property string $event_id 事件ID
 * @property integer $version 版本
 * @property boolean $order_handle
 * @property boolean $set_score
 * @property integer $set_home_team_score
 * @property integer $set_away_team_score
 *
 * @property array $bet_data
 *
 * @property string $ai_score 推荐比分
 * @property array $top_three 推荐前三名
 */
class MetaFootballGame extends Model
{
    use MongoAttribute;

    protected $connection = 'mongodb';
    protected $collection = 'meta_football_game';
    protected $guarded = [];
    protected $dates = [
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'game_over' => 'boolean',
        'home_team_score' => 'integer',
        'away_team_score' => 'integer',
        'version' => 'integer',
        'order_handle' => 'boolean',
        'set_score' => 'boolean',
        'set_home_team_score' => 'integer',
        'set_away_team_score' => 'integer',
    ];







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

    public function getBetData()
    {
        $bet_data = (array)$this->bet_data;
        $data = [];
        foreach ($bet_data as $key => $value) {
            $count = data_get($value, 'count', 0);
            $amount_usdt = data_get($value, 'amount_usdt', 0);
            $count_robot = data_get($value, 'count_robot', 0);
            $amount_robot_usdt = data_get($value, 'amount_robot_usdt', 0);

            $data[$key] = [
                'count' => $count + $count_robot * 13,
                'amount_usdt' => $amount_usdt + $amount_robot_usdt * 13,
                'all_amount' => 100000,
                'all_quantity' => data_get($value, 'all_quantity', $this->bet_odd_all_quantity ?? 10000),
            ];
        }
        return $data;

    }

}
