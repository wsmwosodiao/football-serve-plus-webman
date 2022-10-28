<?php

namespace App\model;

use Jenssegers\Mongodb\Eloquent\Model;


class WalletLogDayDataMongo extends Model
{

    protected $connection = "mongodb";

    protected $table = 'wallet_log_day_data';

    protected $guarded = [];

    protected $dates = [
        'day',
        'push_at'
    ];
    protected $casts = [
        'is_push' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
