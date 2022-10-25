<?php


namespace App\model;


use Illuminate\Support\Str;
use Jenssegers\Mongodb\Eloquent\Model;

/**
 * @property string $_id
 * @property string $type
 * @property bool $is_read
 * @property bool $is_push
 * @property bool $is_push_success
 * @property bool $socket
 * @property bool $forced
 * @property bool $notification
 * @property string $title_slug
 * @property string $content_slug
 * @property array $params
 * @property array $data
 * @property string $created_at
 * @property string $local
 */
class Notification extends Model
{

    protected $connection = "mongodb";
    protected $guarded = [];

    protected $dates = ['read_time','push_at'];

    protected $casts = [
        'is_push' => 'boolean',
        'is_push_success' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function read(): bool
    {
        return $this->is_read;
    }


    public function title()
    {
        $local = data_get($this, 'local');

        return Lang($this->title_slug, [], $local);

    }

    public function content()
    {
        $params = $this->params;

        $local = data_get($this, 'local');

        $params = collect($params)->map(function ($value, $key) use ($local) {
            if (is_array($value)) {
                return $value;
            }
            if (Str::contains($key, ['_lang', '_type'])) {
                return Lang($value, [], $local);
            }
            return $value;
        })->toArray();
        return Lang($this->content_slug, $params, $local);
    }

    public function getData()
    {
        return collect($this->data)->values();
    }


    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }


}
