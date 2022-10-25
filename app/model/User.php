<?php

namespace App\model;


use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\HybridRelations;

class User extends Model
{
    use HybridRelations;

    protected $connection = 'mysql';
    protected $guarded = [];


    public function userData()
    {
        return $this->hasOne(UserData::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Model|UserData
     */
    public function getUserData()
    {
        return $this->userData()->firstOrCreate([
            'user_id' => $this->id,
        ], [
            'country_code' => $this->country_code,
            'channel_id' => $this->channel_id,
            'link_id' => $this->link_id,
            'whats_app' => $this->whats_app,
            'enable_google_authenticator' => false,
        ]);
    }

    //邀请关系
    public function invite()
    {
        return $this->hasOne(UserInvite::class);
    }

    public function inviteLog()
    {
        return $this->hasOne(UserInviteLog::class);
    }

    //渠道关联
    public function channel()
    {
        return $this->belongsTo(AdminUser::class, 'channel_id');
    }

    //渠道关联
    public function channelService()
    {
        return $this->belongsTo(ChannelService::class, 'channel_service_id');
    }

    //上级关联
    public function parent()
    {
        return $this->belongsTo(User::class, 'invite_id');
    }

    public function son()
    {
        return $this->hasMany(User::class, 'invite_id', 'id');
    }

    public function toDaySon()
    {
        return $this->hasMany(User::class, 'invite_id', 'id')->whereDate('created_at', Carbon::today());
    }

    //充值订单
    public function rechargeOrders()
    {
        return $this->hasMany(UserRechargeOrder::class);
    }

    //提现订单
    public function withdrawOrders()
    {
        return $this->hasMany(UserWithdrawOrder::class);
    }

    //钱包关联
    public function wallets()
    {
        return $this->hasMany(Wallet::class);
    }

    public function walletBy()
    {
        return $this->hasOne(Wallet::class);
    }

    //钱包流水数据关联
    public function walletLogs()
    {
        return $this->hasMany(WalletLog::class);
    }

    //钱包流水数据关联
    public function WalletLogMongo()
    {
        return $this->hasMany(WalletLogMongo::class);
    }

    public function footballOrder()
    {
        return $this->hasMany(UserFootballOrder::class);
    }

    public function footballOrder30()
    {
        return $this->hasMany(UserFootballOrder::class)
            ->whereIn('bet_amount_wallet_type', WalletService::BET_WALLET_TYPES)
            ->where('order_over', 1)
            ->whereDate('over_time', '>=', Carbon::today()->subDays(30));
    }

    public function basketballOrder()
    {
        return $this->hasMany(UserBasketballOrder::class);
    }

    public function baseballOrder()
    {
        return $this->hasMany(UserBaseballOrder::class);
    }

    //投资产品关联
    public function products()
    {
        return $this->hasMany(UserProduct::class);
    }

    //投资理财产品关联
    public function manageMoney()
    {
        return $this->hasMany(UserManageMoney::class);
    }

    //矿工产品关联
    public function miners()
    {
        return $this->hasMany(UserMiner::class);
    }

    public function speedMiners()
    {
        return $this->hasMany(UserMiner::class)->where('is_free', 0)->where('is_over', 0);
    }

    //用户设备列表关联
    public function devices()
    {
        return $this->hasMany(Device::class);
    }

    //用户设备IP
    public function ips()
    {
        return $this->hasMany(Device::class, 'ip', 'ip')->where('user_id', '>', 0)->select(['ip', 'user_id']);
    }

    public function userIps()
    {
        return $this->hasMany(User::class, 'ip', 'ip');
    }

    //用户imei设备关联
    public function device()
    {
        return $this->hasOne(Device::class, 'imei', 'imei');
    }

    /**
     * 用户hashID访问器
     * @return string
     */
    public function getHashAttribute()
    {

        return Hashids::encode($this->id);
    }

    public function getShowNameAttribute()
    {

        $nick_name = null;
        if ($this->relationLoaded('userData')) {
            $nick_name = $this->userData?->nick_name;
        }
        if ($nick_name) return FormatPhone($nick_name);
        $name = $this->name;
        if (!is_null($name)) return FormatPhone($name);

        return FormatPhone($this->national_number);
    }

    public function getNickName()
    {
        $nick_name = null;
        if ($this->relationLoaded('userData')) {
            $nick_name = $this->userData?->nick_name;
        }
        if ($nick_name) return $nick_name;
        return FormatPhone($this->national_number, false);
    }


    public function showMoney(float $money, string $walletType)
    {
        $country_code = $this->country_code;
        $to = match ($country_code) {
            "MY" => WalletType::MYR,
            default => $walletType,
        };
        $toMoney = WalletService::make()->walletSwap($money, $walletType, $to);
        if ($to != $walletType) {
            $toMoney = (float)sprintf("%.4f", $toMoney);
        }
        return [$to, $toMoney];

    }


    public function notRefuseWithdrawOrderCount(): int
    {
        return $this->withdrawOrders()->whereIn('order_status', [
            WithdrawOrderStatusType::Checking,
            WithdrawOrderStatusType::CheckSuccess,
            WithdrawOrderStatusType::Paying,
            WithdrawOrderStatusType::PayError,
        ])->count();

    }

    public static function testerIds()
    {
        return self::query()->where('tester', 1)->pluck('id');
    }

    public function tz()
    {
        return Countrycode::getTimeZone($this->country_code);
    }

    /**
     * 获取今日邀请人数
     * @return int
     */
    public function getTodayInviteUsers(): int
    {
        return self::query()->where('invite_id', $this->id)
            ->whereDate('created_at', Carbon::today())->count();
    }

    public function walletLogData()
    {
        return WalletLogDataMongo::query()->firstOrCreate([
            'user_id' => $this->id,
        ], [
            'country_code' => $this->country_code,
            'channel_id' => $this->channel_id,
            'link_id' => $this->link_id,
        ]);
    }

    public function walletLogDayData()
    {
        return WalletLogDayDataMongo::query()->firstOrCreate([
            'user_id' => $this->id,
            'day' => Carbon::today(),
        ], [
            'country_code' => $this->country_code,
            'channel_id' => $this->channel_id,
            'link_id' => $this->link_id,
        ]);
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

}
