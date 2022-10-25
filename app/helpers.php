<?php
use Carbon\Carbon;
use Workerman\Worker;

/**
 * 获取是否是正式环境
 * @return bool
 */
function IsDebug()
{
    return Worker::$daemonize;
}





/**
 * 获取时间倒计时
 * @param $time
 * @return float|int
 */
function DiffInSeconds($time, $tz = null)
{
    if ($time) {
        $time = Carbon::make($time)?->timezone($tz);

        if ($time->gt(now($tz)->toDateTimeString())) return $time->diffInSeconds(now($tz)->toDateTimeString());
    }
    return 0;
}

function ToDateTimeString($time, $tz = null)
{
    if ($time) return Carbon::make($time)?->timezone($tz)->toDateTimeString();
    return null;
}

function ToDateString($time, $tz = null)
{
    if ($time) return Carbon::make($time)?->timezone($tz)->toDateString();
    return null;
}

/**
 * 获取时区
 * @return array|\Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application|\Illuminate\Http\Request|mixed|string
 */
function GetTimezone()
{
    $tz = request()?->header("Timezone") ?? request('timezone') ?? config('app.timezone');
    if (in_array($tz, timezone_identifiers_list())) {
        return $tz;
    }
    return config('app.timezone');
}

function GetSysCarbon(Carbon $time): Carbon|string
{
    return $time->tz(config('app.timezone'));
}

function getIP(): string
{
    try {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            //ip from share internet
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            //ip pass from proxy
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return collect(explode(",", $ip))->first();
    } catch (Exception $exception) {
        return '';
    }
}

function Lang($slug, array $params = [], $local = null)
{
    if (empty($slug)) {
        return null;
    }

    $slug = strtoupper($slug);

    $langContent = getLang($local, $slug);
    $langContent = $langContent ?? $slug;
    if (count($params) > 0) {
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                continue;
            }
            if(is_float($value)){
                $value = convert_scientific_number_to_normal($value);
            }
            $langContent = str_replace("{" . $key . "}", $value, $langContent);
        }
    }
    $langContent = preg_replace('/{.*}/', '', $langContent);
    return $langContent ?? $slug;
}

function getLang($local, $slug)
{

    $allLang = config('allLang');

    $itemLang = data_get($allLang, $slug);

    return data_get($itemLang, $local);

}

function LocalDataGet($data, $local = null, $default = null)
{
    $local = $local ?? config('appLocal', setting('default_lang'));
    return data_get($data, $local, $default);
}

function WalletTypeName($walletType)
{
    return match ($walletType) {
        WalletType::MYR => 'RM',
        WalletType::THB => '฿',
        WalletType::IDR => 'Rp',
        WalletType::INR => '₹',
        WalletType::VND => '₫',
        default => $walletType,
    };
}

function FormatPhone($national_number, bool $hidden = true)
{
    try {
        $country = PhoneNumber::make($national_number)->getCountry();


        /*$national_number = match ($country) {
            'MY' => PhoneNumber::make($national_number)->formatNational(),
            default => str_replace("+$country", "", $national_number),
        };*/

        $national_number = str_replace(array(" "), "", $national_number);

        if (!$hidden) {
            return $national_number;
        }

        $s = strlen($national_number) - 7;

        $xx = "";

        foreach (range(0, $s) as $i) {
            $xx .= "*";
        }

        return match ($country) {
            //'MY' => strlen($national_number) >= 12 ? substr_replace($national_number, '***', 6, 3) : substr_replace($national_number, '**', 6, 2),
            //default => substr_replace($national_number, '****', $s, 4),
            default => substr($national_number, 0, 4) . $xx . substr($national_number, -3),//substr_replace($national_number, '****', $s, 4),
        };
    } catch (Exception $exception) {
        $s = strlen($national_number) / 2 - 2;
        return substr_replace($national_number, '****', 4, 4);
    }


}

/**
 * 根据权重获取随机区间返回ID
 * @param $array
 * @param string $key
 * @param string $value_key
 * @return int|string
 */
function ArrayRandByWeight($array, string $key = 'id', string $value_key = 'weight'): int|string
{
    try {
        if (!empty($array)) {
            //区间最大值
            $sum = 0;
            //区间 数组
            $interval = array();
            //制造区间
            foreach ($array as $value) {
                $interval[$value[$key]]['min'] = $sum + 1;
                $interval[$value[$key]]['max'] = $sum + $value[$value_key];
                $sum += $value[$value_key];
            }
            if ($sum <= 1) return 0;
            //在区间内随机一个数
            $result = random_int(1, (int)$sum);
            //获取结果属于哪个区间, 返回其ID

            foreach ($interval as $id => $v) {

                while ($result >= $v['min'] && $result <= $v['max']) {
                    return $id;
                }
            }
        }
        return 0;
    } catch (Exception $exception) {
        return 0;
    }
}

/**
 * 获取图片地址
 * @param $path
 * @param int $w
 * @param string|null $style
 * @return mixed
 */
function ImageUrl($path, int $w = 700, string $style = null): mixed
{
    if (empty($path)) return $path;
    if (Str::contains($path, '//')) {
        return $path;
    }
    $x_style = "";
    if ($w && !$style) {
        $x_style = "?x-oss-process=image/resize,w_$w/quality,q_90";
    }
    if ($style) {
        $x_style = "?x-oss-process={$style}";
    }
    return Storage::disk("aliyun")->url($path . $x_style);
}

function AvatarURL($path, int $size = 100)
{
    $style = "image/auto-orient,1/resize,m_fill,w_$size,h_$size/quality,q_90";
    return ImageUrl($path, 100, $style);
}

function ImageBaseUrl($path, $style = null): mixed
{
    if (empty($path)) return $path;
    if (Str::contains($path, '//')) {
        return $path;
    }
    return Storage::disk("aliyun")->url($path . $style);
}

function ApiSportsLogo($url)
{
    return str_replace("https://media.api-sports.io/", "/sports-logo/", $url);
}

function MoneyF($value)
{
    return sprintf("%.8f", $value);
}

function MoneyCoin($balance, $nums = 8)
{
    $balance = number_format($balance, $nums);

    $balance = trim(strval($balance));
    if (preg_match('#^-?\d+?\.0+$#', $balance)) {
        $balance = preg_replace('#^(-?\d+?)\.0+$#', '$1', $balance);
    }
    if (preg_match('#^-?\d+?\.[0-9]+?0+$#', $balance)) {
        $balance = preg_replace('#^(-?\d+\.[0-9]+?)0+$#', '$1', $balance);
    }

    return $balance;
}

function count_decimals($x)
{
    return strlen(substr(strrchr($x, "."), 1));
}

function Random($min, $max)
{
    $decimals = max(count_decimals($min), count_decimals($max));
    $factor = 10 ** $decimals;
    $min *= $factor;
    $max *= $factor;
    if ($min === 0 && $max === 0) {
        return 0;
    }
    if ($min < $max) {
        return bcdiv(random_int($min, $max), $factor);
    }
    if ($max < $min) {
        return bcdiv(random_int($max, $min), $factor);
    }
    return bcdiv($min, $factor);
}

function FloatNumber($number): string
{
    return convert_scientific_number_to_normal(floatval($number));
}

function ToDecimal2($v): string
{
    $v = (floor((int)($v * 100)) / 100);

    return sprintf("%01.2f", $v);
}

/**
 * 将科学计数法的数字转换为正常的数字
 * 为了将数字处理完美一些，使用部分正则是可以接受的
 * @param  $number
 * @return string
 * @author loveyu
 */

function convert_scientific_number_to_normal($number): string
{
    if (stripos($number, 'e') === false) {
        //判断是否为科学计数法
        return $number;
    }

    if (!preg_match("/^([\\d.]+)[eE]([\\d\\-\\+]+)$/", str_replace(array(" ", ","), "", trim($number)), $matches)) {
        //提取科学计数法中有效的数据，无法处理则直接返回
        return $number;
    }


    //对数字前后的0和点进行处理，防止数据干扰，实际上正确的科学计数法没有这个问题
    $data = preg_replace(array("/^[0]+/"), "", rtrim($matches[1], "0."));
    $length = (int)$matches[2];
    if ($data[0] == ".") {
        //由于最前面的0可能被替换掉了，这里是小数要将0补齐
        $data = "0{$data}";
    }

    //这里有一种特殊可能，无需处理
    if ($length == 0) {
        return $data;
    }
    //记住当前小数点的位置，用于判断左右移动
    $dot_position = strpos($data, ".");
    if ($dot_position === false) {
        $dot_position = strlen($data);
    }
    //正式数据处理中，是不需要点号的，最后输出时会添加上去
    $data = str_replace(".", "", $data);
    if ($length > 0) {
        //如果科学计数长度大于0
        //获取要添加0的个数，并在数据后面补充
        $repeat_length = $length - (strlen($data) - $dot_position);
        if ($repeat_length > 0) {
            $data .= str_repeat('0', $repeat_length);
        }
        //小数点向后移n位
        $dot_position += $length;
        $data = ltrim(substr($data, 0, $dot_position), "0") . "." . substr($data, $dot_position);
    } elseif ($length < 0) {
        //当前是一个负数
        //获取要重复的0的个数
        $repeat_length = abs($length) - $dot_position;
        if ($repeat_length > 0) {
            //这里的值可能是小于0的数，由于小数点过长
            $data = str_repeat('0', $repeat_length) . $data;
        }
        $dot_position += $length;//此处length为负数，直接操作
        if ($dot_position < 1) {
            //补充数据处理，如果当前位置小于0则表示无需处理，直接补小数点即可
            $data = ".{$data}";
        } else {
            $data = substr($data, 0, $dot_position) . "." . substr($data, $dot_position);
        }
    }
    if ($data[0] == ".") {
        //数据补0
        $data = "0{$data}";
    }
    return trim($data, ".");
}

function random_user()
{
    $male_names = array("James", "John", "Robert", "Michael", "William", "David", "Richard", "Charles", "Joseph", "Thomas", "Christopher", "Daniel", "Paul", "Mark", "Donald", "George", "Kenneth", "Steven", "Edward");

    $famale_names = array("Mary", "Patricia", "Linda", "Barbara", "Elizabeth", "Jennifer", "Maria", "Susan", "Margaret", "Dorothy", "Lisa", "Nancy", "Karen", "Betty", "Helen", "Sandra", "Donna", "Carol", "Ruth");

    $surnames = array("Smith", "Jones", "Taylor", "Williams", "Brown", "Davies", "Evans", "Wilson", "Thomas", "Roberts", "Johnson", "Lewis", "Walker", "Robinson", "Wood", "Thompson", "White", "Watson", "Jackson");

    $frist_num = mt_rand(0, 18);

    $sur_num = mt_rand(0, 18);

    $type = rand(0, 1);

    if ($type == 0) {
        $username = $male_names[$frist_num] . " " . $surnames[$sur_num];

    } else {
        $username = $famale_names[$frist_num] . " " . $surnames[$sur_num];

    }

    return $username;

}
