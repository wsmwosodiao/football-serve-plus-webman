<?php

namespace App\model\Sport;
use App\Traits\MongoAttribute;
use Jenssegers\Mongodb\Eloquent\Model;

/**
 * 保存推送数据，等待图片生成之后发送
 * @property string $post_data 发送的数据
 * @property boolean $is_send 是否推送
 * @property string $created_at 创建时间
 * @property string $send_at 发送时间
 *
 */
class FootBallAfterImgPush extends Model
{
    use MongoAttribute;

    protected $connection = 'mongodb';
    protected $table = 'foot_ball_after_img';
    protected $guarded = [];


    protected $dates = [
        'created_at',
        'send_at'
    ];

    protected $casts = [
        'is_send' => 'boolean'
    ];
}