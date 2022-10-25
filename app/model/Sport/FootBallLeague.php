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
 * @property string $flag
 * @property string $logo_path
 * @property boolean $is_rec 是否推荐
 * @property boolean $is_hot 是否热门
 * @property integer $order 排序
 * @property array $title
 */
class FootBallLeague extends Model
{

    use MongoAttribute;

    protected $connection = 'mongodb';
    protected $table = 'foot_ball_league';
    protected $guarded = [];

    protected $casts = [
        'id' => 'integer',
        'is_rec' => 'boolean',
        'is_hot' => 'boolean',
        'order' => 'integer'
    ];



    public static function addLeague($league): void
    {
        $id = data_get($league, 'id');
        self::query()
            ->updateOrCreate([
                'id' => $id,
            ], [
                'name' => data_get($league, 'name'),
                'country' => data_get($league, 'country'),
                'country_code' => data_get($league, 'country_code'),
                'logo' => data_get($league, 'logo'),
                'flag' => data_get($league, 'flag'),
                'logo_path' => "leagues/$id.png",
            ]);
    }


}
