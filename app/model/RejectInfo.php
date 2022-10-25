<?php

namespace App\model;

use Jenssegers\Mongodb\Eloquent\Model;


class RejectInfo extends Model
{
    protected $table = "reject_info";
    protected $connection = "mongodb";
    protected $guarded = [];
}
