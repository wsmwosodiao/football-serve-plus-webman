<?php

namespace App\model;

use App\Traits\MongoAttribute;
use Jenssegers\Mongodb\Eloquent\Model;

/**
 * @property int $user_id
 * @property string $type
 * @property string $id
 * @property string $local
 */
class RobotPushLog extends Model
{
    use  MongoAttribute;

    protected $table = "robot_push_log";
    protected $connection = "mongodb";
    protected $guarded = [];
    public $timestamps = false;

    protected $dates = [
        'push_at',
    ];
    protected $casts = [
        'user_id' => 'integer',
        'type'=> 'string',
        'id'=> 'string',
        'local'=> 'string',
    ];
}
