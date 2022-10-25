<?php

namespace App\model\Sport;

use App\Traits\MongoAttribute;
use Jenssegers\Mongodb\Eloquent\Model;

/**
 * @property string $_id ID
 * @property string $fixture_id 比赛ID
 * @property string $fixture_date 比赛时间
 * @property string $teacher_id 老师ID
 * @property integer $forecast_home_score 预测主队比分
 * @property integer $forecast_away_score 预测客队比分
 * @property boolean $show_home_score 是否显示主队比分,否则显示客队比分
 * @property boolean $coerce_win 是否强制胜利
 * @property integer $all_count  总数量
 * @property integer $ai_count AI数量
 * @property integer $order_count 跟单数量
 * @property float $order_amount_usdt 跟单金额(USDT)
 * @property float $order_amount_robot_usdt AI跟单金额(USDT)
 * @property float $compensate_amount_usdt 赔偿金额(USDT)
 * @property boolean $complete 是否完成
 * @property boolean $is_win 是否赢
 * @property boolean $status 状态
 * @property float $odds 赔率
 * @property string $created_at
 *
 * @property FootballTeacher $teacher
 * @property FootballFixture $match
 */
class FootballTeacherFixture extends Model
{
    use  MongoAttribute;

    protected $table = "football_teacher_fixture";
    protected $connection = "mongodb";
    protected $guarded = [];
    protected $dates = [
        'fixture_date'
    ];
    protected $with = [
        'teacher'
    ];
    protected $casts = [
        'forecast_home_score' => 'integer',
        'forecast_away_score' => 'integer',
        'coerce_win' => 'boolean',
        'all_count' => 'integer',
        'ai_count' => 'integer',
        'order_count' => 'integer',
        'order_amount_usdt' => 'float',
        'compensate_amount_usdt' => 'float',
        'complete' => 'boolean',
        'is_win' => 'boolean',
        'odds' => 'float',
        'status' => 'boolean',
    ];

    public function teacher()
    {
        return $this->belongsTo(FootballTeacher::class, 'teacher_id', '_id');
    }

    public function match()
    {
        return $this->belongsTo(FootBallFixture::class, 'fixture_id', 'id');
    }

}
