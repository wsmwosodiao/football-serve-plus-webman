<?php

namespace App\model;


use App\Traits\MongoAttribute;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Jenssegers\Mongodb\Eloquent\Model;



/**
 * @property string $command 口令
 * @property string $name 名称
 * @property string $start_at 开始时间
 * @property string $end_at 结束时间
 * @property integer $valid_hour 有效期
 * @property string $type 红包类型
 * @property string $country_code 国家码
 * @property array $exclude_country_code 排除国家
 * @property boolean $status 状态
 * @property integer $amount 红包金额 分
 * @property string $wallet_type 金额类型
 * @property integer $quantity 红包个数
 * @property integer $min_amount 红包最小金额 分
 * @property integer $received_amount 已领取金额 分
 * @property integer $received_quantity 已领取数量
 *
 *
 * @property boolean $is_user_created 是否用户创建
 * @property integer $user_id 用户ID
 * @property integer $pay_user_id 付款用户ID
 * @property boolean $is_replace_pay 是否代付
 * @property integer $wallet_log_id 钱包日志ID
 *
 * @property integer $bet_number 用户投注次数
 * @property float $bet_min_amount 用户投注金额USDT
 * @property float $recharge_min_amount 用户充值金额USDT
 *
 * @property integer $check_hour_limit 小时内
 * @property integer $check_hour_invite 小时内邀请人数
 * @property integer $check_hour_bet 小时内交易次数
 * @property integer $check_hour_recharge 小时内充值次数
 *
 * @property boolean $is_agent_only 是否仅代理可领取
 * @property boolean $is_send_back 是否返还
 * @property string $send_back_at 返还时间
 *

 */
class RedPacket extends Model
{

    use MongoAttribute;

    protected $connection = 'mongodb';
    protected $collection = 'red_packet';
    protected $guarded = [];

    protected $dates = [
        'start_at',
        'end_at',
        'send_back_at'
    ];

    protected $casts = [
        'valid_hour' => 'integer',
        'status' => 'boolean',
        'amount' => 'integer',
        'quantity' => 'integer',
        'min_amount' => 'integer',
        'received_amount' => 'integer',
        'received_quantity' => 'integer',
        'is_user_created' => 'boolean',
        'user_id' => 'integer',
        'pay_user_id' => 'integer',
        'is_replace_pay' => 'boolean',
        'wallet_log_id' => 'integer',

        'bet_number' => 'integer',
        'bet_min_amount' => 'float',
        'recharge_min_amount' => 'float',

        'check_hour_limit' => 'integer',
        'check_hour_invite' => 'integer',
        'check_hour_bet' => 'integer',
        'check_hour_recharge' => 'integer',
        'is_agent_only' => 'boolean',
        'is_send_back' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted()
    {
        parent::booted();

        self::creating(static function (RedPacket $model) {
            $model->received_amount = 0;
            $model->received_quantity = 0;
            $model->command = strtoupper(Str::random(8));
        });
    }


}
