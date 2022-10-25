<?php

namespace App\model;

use App\Traits\MongoAttribute;
use Dcat\Admin\Traits\HasDateTimeFormatter;
use Jenssegers\Mongodb\Eloquent\Model;

/**
 * @property string $generate_secret_key 谷歌验证key
 * @property boolean $is_bind_google_authenticator 是否已绑定谷歌验证
 * @property boolean $enable_google_authenticator 开启谷歌验证
 * @property boolean $enable_sms_verify 开启短信验证
 * @property string $asset_password  资金密码
 * @property string $asset_password_lock_end  资金密码锁定结束时间
 * @property string $asset_password_update_time  资金密码修改时间
 * @property string $nick_name
 * @property string $whats_app
 * @property string $last_whats_app_time
 * @property string $line_app
 * @property string $last_line_app_time
 * @property string $zalo_app
 * @property string $last_zalo_app_time
 * @property string $telegram_app
 * @property string $last_telegram_app_time
 * @property string $avatar
 * @property integer $avatar_index
 * @property boolean $is_public
 * @property boolean $is_bound_robot 是否绑定机器人
 * @property boolean $is_id_auth
 * @property string $id_auth_time
 * @property string $id_number
 * @property string $id_name
 * @property string $parent_chat_id
 * @property string $channel_chat_id
 * @property int $invite_id
 * @property int $football_vip_agent_level
 * @property string $football_vip_agent_level_at
 * @property string $football_vip_agent_month_salary_at
 * @property boolean $can_site_transfer
 * @property boolean $can_show_cn
 * @property string $last_set_login_user_name_at 最后修改登录用户名时间
 *
 * @property bool $bind_usdt_address
 * @property bool $bind_bank_card
 * @property array $user_config
 * @property string $password
 */
class UserData extends Model
{
    use MongoAttribute;

    protected $table = "user_data";
    protected $connection = "mongodb";
    protected $guarded = [];
    public $timestamps = false;

    protected $dates = [
        'asset_password_update_time',
        'asset_password_lock_end',
        'last_whats_app_time',
        'last_line_app_time',
        'last_zalo_app_time',
        'last_telegram_app_time',
        'id_auth_time',
        'football_vip_agent_level_at',
        'football_vip_agent_month_salary_at',
        'last_set_login_user_name_at',
        'last_bet_active_at',
        'is_bound_robot_at',
    ];
    protected $casts = [
        'is_public' => 'boolean',
        'is_id_auth' => 'boolean',
        'football_vip_agent_level' => 'integer',
        'can_site_transfer' => 'boolean',
        'bind_usdt_address' => 'boolean',
        'bind_bank_card' => 'boolean',

        'can_show_cn' => 'boolean',
        'is_bound_robot' => 'boolean',
    ];
}
