<?php

namespace App\model\Sport;


use App\Traits\MongoAttribute;
use Jenssegers\Mongodb\Eloquent\Model;

/**
 * 联赛
 * @property integer $id
 * @property string $name
 * @property string $name_cn
 * @property string $country
 * @property string $logo
 * @property string $logo_path
 *
 * @property boolean $is_rec 是否推荐
 * @property boolean $is_hot 是否热门
 * @property integer $order 排序
 *
 * @property array $title
 */
class FootBallTeam extends Model
{

    use MongoAttribute;

    protected $connection = 'mongodb';
    protected $table = 'foot_ball_team';
    protected $guarded = [];

    protected $casts = [
        'id' => 'integer',
        'is_rec' => 'boolean',
        'is_hot' => 'boolean',
        'order' => 'integer'
    ];

    public static function addTeam($team, $venue = []): void
    {
        $id = data_get($team, 'id');
        self::query()
            ->firstOrCreate([
                'id' => $id,
            ], [
                'name' => data_get($team, 'name'),
                'code' => data_get($team, 'code'),
                'country' => data_get($team, 'country'),
                'founded' => data_get($team, 'founded'),
                'national' => data_get($team, 'national'),
                'logo' => data_get($team, 'logo'),
                'logo_path' => "teams/$id.png",
                'venue' => $venue
            ]);
    }


}
