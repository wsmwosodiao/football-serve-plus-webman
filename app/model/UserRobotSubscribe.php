<?php

namespace App\model;

use App\Traits\MongoAttribute;
use Jenssegers\Mongodb\Eloquent\Model;

/**
 * @property int $user_id
 * @property int $channel_id
 * @property int $link_id
 * @property string $referral_code
 * @property string $local
 * @property boolean $is_bound_robot_subscribe
 */
class UserRobotSubscribe extends Model
{
    use  MongoAttribute;

    protected $table = "user_robot_subscribe";
    protected $connection = "mongodb";
    protected $guarded = [];
    public $timestamps = false;

    protected $dates = [
        'register_at',
        'bound_robot_at',
        'is_bound_robot_subscribe_at',
    ];
    protected $casts = [
        'user_id' => 'integer',
        'channel_id' => 'integer',
        'link_id' => 'integer',
        'referral_code'=> 'string',
        'local'=> 'string',
        'is_bound_robot_subscribe' => 'boolean',
    ];
}
