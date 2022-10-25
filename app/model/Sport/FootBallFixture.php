<?php

namespace App\model\Sport;

use App\Traits\MongoAttribute;
use Carbon\Carbon;
use Dcat\Admin\Traits\HasDateTimeFormatter;
use Jenssegers\Mongodb\Eloquent\Model;

/**
 * 联赛
 * @property integer $id
 * @property string $name
 * @property string $country
 * @property string $logo
 * @property string $flag
 * @property object $status
 * @property object $goals
 * @property object $score
 * @property string $date
 * @property integer $league_id
 * @property boolean $order_handle
 * @property boolean $order_half_handle
 * @property boolean $not_start_handle
 *
 * @property boolean $is_rec 是否推荐
 * @property boolean $is_hot 是否热门
 * @property boolean $has_odds 是否有赔率
 * @property integer $order 排序
 * @property string $live_url 直播地址
 * @property array $goals90
 * @property  $bet_data
 * @property integer $bet_count
 * @property float $bet_amount_count_usdt
 * @property integer $bet_count_robot
 *
 * @property float $bet_amount_count_robot_usdt
 *
 * @property integer $bet_odd_all_quantity 投注总数量
 * @property boolean $manual_operation
 * @property boolean $exception_handling
 *
 * @property array $remark 备注
 *
 */
class FootBallFixture extends Model
{

    use MongoAttribute;

    protected $connection = 'mongodb';
    protected $table = 'foot_ball_fixture';
    protected $guarded = [];

    protected $appends = ['goals90'];

    protected $dates = [
        'date',
        'last_get_at'
    ];

    protected $casts = [
        'id' => 'integer',
        'is_rec' => 'boolean',
        'is_hot' => 'boolean',
        'has_odds' => 'boolean',
        'order_handle' => 'boolean',
        'order_half_handle' => 'boolean',
        'not_start_handle' => 'boolean',
        'order' => 'integer',

        'manual_operation' => 'boolean',
        'exception_handling' => 'boolean',

    ];

    protected $with = ['leagueInfo', 'homeTeam', 'awayTeam'];


    /*public function getGoalsAttribute($value)
    {

        $home = (int)$value['home'];
        $away = (int)$value['away'];

        $penalty = $this->score['penalty'];

        $penalty_home = (int)$penalty['home'];
        $penalty_away = (int)$penalty['away'];

        return
            [
                'home' => $home + $penalty_home,
                'away' => $away + $penalty_away,
            ];
    }*/

    public function getGoals90Attribute($value)
    {


        $penalty = $this->score['fulltime'];

        $penalty_home = (int)$penalty['home'];
        $penalty_away = (int)$penalty['away'];

        return
            [
                'home' => $penalty_home,
                'away' => $penalty_away,
            ];
    }

    public function isStart(): bool
    {
        return Carbon::parse($this->date)->lte(Carbon::now());
    }


    public function odds()
    {
        return $this->hasOne(FootBallOdds::class, 'fixture_id', 'id');
    }
    public function addRemark()
    {
        return $this->hasOne(FootballTeacherFixtureRemark::class, 'fixture_id', 'id');
    }

    public function leagueInfo()
    {
        return $this->belongsTo(FootBallLeague::class, 'league_id', 'id');
    }

    public function homeTeam()
    {
        return $this->belongsTo(FootBallTeam::class, 'teams_home_id', 'id');
    }

    public function awayTeam()
    {
        return $this->belongsTo(FootBallTeam::class, 'teams_away_id', 'id');
    }

    public function teacherFixture()
    {
        return $this->hasMany(FootballTeacherFixture::class, 'fixture_id', 'id')->where('status', '<>', false);
    }

    public function orders(){
        return $this->hasMany(UserFootballOrder::class, 'fixture_id', 'id');
    }

    public function getBetData()
    {

        $bet_data = (array)$this->bet_data;


        $data = [];

        foreach ($bet_data as $key => $value) {



            //$key = str_replace(array('s_', '_'), array('', ':'), $key);

            $count = data_get($value, 'count', 0);
            $amount_usdt = data_get($value, 'amount_usdt', 0);
            $count_robot = data_get($value, 'count_robot', 0);
            $amount_robot_usdt = data_get($value, 'amount_robot_usdt', 0);

            $data[$key] = [
                'count' => $count + $count_robot * 13,
                'amount_usdt' => $amount_usdt + $amount_robot_usdt * 13,
                'all_amount' => 5000000,
                'all_quantity' => data_get($value, 'all_quantity', $this->bet_odd_all_quantity ?? 10000),
            ];

        }

        return $data;

    }


    public const STATUS = [
        'TBD' => '时间待定',
        'NS' => '未开始',
        '1H' => '上半场',
        'HT' => '中场休息',
        '2H' => '下半场',
        'ET' => '加时赛',
        'P' => '处罚进行中',
        'FT' => '比赛结束',
        'AET' => '加时赛结束',
        'PEN' => '点球后比赛结束',
        'BT' => '休息时间（加时赛）',
        'SUSP' => '比赛暂停',
        'INT' => '匹配中断',
        'PST' => '比赛推迟',
        'CANC' => '比赛取消',
        'ABD' => '比赛被放弃',
        'AWD' => '技术损失',
        'WO' => 'WalkOver',
        'LIVE' => 'In Progress',
    ];

    protected static function booted()
    {
        parent::booted();
        static::creating(function (FootBallFixture $model) {
            $model->bet_odd_all_quantity = random_int(1000, 99999);
        });
    }
}
