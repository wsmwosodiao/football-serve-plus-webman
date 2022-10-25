<?php

namespace App\model;

use Illuminate\Database\Eloquent\Model;


class LanguageConfig extends Model
{
    const CACHE_TAG = 'language_config';
    protected $table = 'language_config';

    protected $casts = [
        'content' => 'json',
    ];

    protected $guarded = [];


    public static function AllGroup(): array
    {
        return self::query()->groupBy('group')->select(['group'])->pluck('group', 'group')->toArray();
    }

    protected static function booted()
    {

    }

}
