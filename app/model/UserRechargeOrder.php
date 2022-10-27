<?php

namespace App\model;

use App\Enums\OrderStatusType;
use App\Traits\AdminMarketDataScope;
use App\Traits\ModelPro;
use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\HybridRelations;

class UserRechargeOrder extends Model
{
    use HybridRelations;

    protected $table = 'user_recharge_orders';
    protected $connection = "mysql";
    protected $guarded = [];

    protected $casts = [
        'is_pay' => 'bool',
        'input_data'=>'json'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }



}
