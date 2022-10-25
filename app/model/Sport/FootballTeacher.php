<?php

namespace App\model\Sport;

use App\Traits\MongoAttribute;
use Jenssegers\Mongodb\Eloquent\Model;


/**
 * 导师
 * @property string $name 导师名称
 * @property string $avatar 导师头像
 * @property array $title 导师头衔
 * @property array $description 描述
 * @property float $win_rate 胜率
 * @property float $yield_rate_min 收益率最小值
 * @property float $yield_rate_max 收益率最大值
 * @property float $compensate_amount_max 赔偿金额最大值
 * @property float $pay_amount_min 支付金额最小值
 * @property string $group_chat_url 群聊地址
 * @property boolean $is_rec 是否推荐
 * @property boolean $is_auto 自动带单
 * @property integer $order 排序
 * @property integer $served_users 已服务人数
 * @property integer $predict_match 已经预测比赛数
 * @property integer $ai_count AI数量
 * @property integer $order_count 跟单数量
 * @property float $order_amount_usdt 跟单金额(USDT)
 * @property float $order_amount_robot_usdt AI跟单金额(USDT)
 * @property boolean $status 状态
 * @property string $company 公司
 * @property array $config
 * @property array $tag_ids
 */
class FootballTeacher extends Model
{
    use MongoAttribute;

    protected $connection = 'mongodb';
    protected $table = 'foot_ball_teacher';
    protected $guarded = [];
    protected $casts = [
        'win_rate' => 'float',
        'yield_rate_min' => 'float',
        'yield_rate_max' => 'float',
        'compensate_amount_max' => 'float',
        'pay_amount_min' => 'float',
        'is_rec' => 'boolean',
        'is_auto' => 'boolean',
        'order' => 'integer',
        'served_users' => 'integer',
        'predict_match' => 'integer',
        'status' => 'boolean',
        'ai_count' => 'integer',
        'order_count' => 'integer',
        'order_amount_usdt' => 'float',
    ];

    public function tags()
    {
        return collect(config('footballTags'))->whereIn('_id', $this->tag_ids)->values()->all();
    }
}
