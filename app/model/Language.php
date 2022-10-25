<?php

namespace App\model;

use Illuminate\Database\Eloquent\Model;


class Language extends Model
{
    protected $casts = [
        'required' => 'bool'
    ];
}
