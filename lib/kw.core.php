<?php
/*
 * KW CORE functions
*/

/* === РАБОТА СО ВХОДНЫМИ ДАННЫМИ === */

/**
 * Эквивалент isset( array[ key ] ) ? array[ key ] : default ;
 * at PHP 7 useless, z = a ?? b;
 * @param $array
 * @param $key
 * @param $default
 */
function at($array, $key, $default)
{
    return isset($array[$key]) ? $array[$key] : $default;
}

/**
 * возвращает значение переменной (из GET-а) или значение по умолчанию, если там пусто или она не определена
 * @param $value
 * @param string $default
 * @return mixed
 */
function retVal($value, $default = '')
{
    return (isset($value) && $value != '') ? $value : $default;
}

/* ====== */

/* === РАБОТА С ДАТАМИ === */

/**
 * конвертирует дату из человекопонятного представления в метку времени, для создания метки времени берется полдень указанной даты
 * @param $str_date
 * @param string $format
 * @return int
 */
function convertDateToTimestamp($str_date, $format = "d.m.Y")
{
    if (function_exists('date_parse_from_format')) {
        $date_array = date_parse_from_format($format, $str_date);
    } else {
        if (function_exists('date_parse')) {
            $date_array = date_parse($str_date);
        } else {
            // date_parse not implemented
            $date_array_pre = getdate(strtotime($str_date));
            $date_array['month'] = $date_array_pre['mon'];
            $date_array['day'] = $date_array_pre['mday'];
            $date_array['year'] = $date_array_pre['year'];
        }

    }
    return mktime(12, 0, 0, $date_array['month'], $date_array['day'], $date_array['year']);
}

/**
 * конвертирует дату из человекопонятного представления в массив
 * @param $str_date
 * @return array
 */
function convertDateToArray($str_date)
{
    if (function_exists('date_parse_from_format')) {
        $date_array = date_parse_from_format('d.m.Y', $str_date);
    } else {
        if (function_exists('date_parse')) {
            $date_array = date_parse($str_date);
        } else {
            // date_parse not implemented
            $date_array_pre = getdate(strtotime($str_date));
            $date_array['month'] = $date_array_pre['mon'];
            $date_array['day'] = $date_array_pre['mday'];
            $date_array['year'] = $date_array_pre['year'];
            $date_array['hour'] = 12;
            $date_array['minute'] = 0;
            $date_array['second'] = 0;
        }
    }
    return $date_array;
}

/**
 * конвертирует "человекопонятную" дату в MYSQL-формат
 * @param $string
 */
function convertUserDateToSQLDate($string)
{
    Date('Y-m-d', ConvertDateToTimestamp($string));
}

/* ====== */
/* === ПЕРЕВОД ВЕЛИЧИН === */

/**
 * @param $size
 * @return string
 */
function convertToHumanBytes($size)
{
    $inflexion = array(" Bytes", " K", " M", " G", " T", " P", " E", " Z", " Y");
    return $size ? round($size / pow(1024, ($i = floor(log($size, 1024)))), 0) . $inflexion[$i] : '0'.$inflexion[0];
}

/**
 * Функция возвращает число и окончание для множественного числа слова на основании числа и массива окончаний
 * @param $number int число чего-либо
 * @param $titles array варианты написания слов для количества 1, 2 и 5 (яблоко, яблока, яблок)
 * @return string число и окончание
 * see http://habrahabr.ru/post/105428/
 */
function str_human_plural_form($number, $titles = array('', '', ''))
{
    $cases = array(2, 0, 1, 1, 1, 2);
    return $number . " " . $titles[($number % 100 > 4 && $number % 100 < 20) ? 2 : $cases[min($number % 10, 5)]];
}

/**
 * Функция возвращает только окончание для множественного числа слова на основании числа и массива окончаний
 * @param $number int число чего-либо
 * @param $titles array варианты написания слов для количества 1, 2 и 5 (яблоко, яблока, яблок)
 * @return string только окончание
 */
function get_human_plural_form($number, $titles = array('', '', ''))
{
    $cases = array(2, 0, 1, 1, 1, 2);
    return $titles[($number % 100 > 4 && $number % 100 < 20) ? 2 : $cases[min($number % 10, 5)]];
}


/* such as human_plural_form, but written by me at 2010 year */
/**
 * @param $num
 * @param $str1
 * @param $str2
 * @param $str3
 * @return string
 */
function getHumanFriendlyCounter($num, $str1, $str2, $str3)
{
    $ret = '';
    if ($num == 0) $ret = $str3;
    if ($num == 1) $ret = $str1;
    if ($num < 21) {
        if ($num == 1) $ret = $str1;
        if (($num > 1) && ($num < 5)) $ret = $str2;
        if (($num > 4) && ($num < 21)) $ret = $str3;
    } else {
        $residue = ($num % 10);
        if ($residue == 1) $ret = $str1;
        if (($residue > 1) && ($residue < 5)) $ret = $str2;
        if (($residue > 4) && ($residue <= 9)) $ret = $str3;
        if ($residue == 0) $ret = $str3;
    }
    return $ret;
}

/* ====== */
/* === ВАЛИДАЦИЯ ДАННЫХ === */

/**
 * Проверяет заданную переменную на допустимость (на основе массива допустымых значений)
 * и если находит - возвращает её. В противном случае возвращает NULL.
 * @param $data
 * @param $allowed_values_array
 * @return $data if it is in allowed values array, NULL otherwise
 */
function getAllowedValue($data, $allowed_values_array)
{
    if (empty($data)) {
        return NULL;
    } else {
        $key = array_search($data, $allowed_values_array);
        return ($key !== FALSE)
            ? $allowed_values_array[$key]
            : NULL;
    }
}


/* === ПРОВЕРКА IP === */

/**
 * Проверка вхождения IP4 в заданный диапазон
 * @param $ip       - массив октетов проверяемого IP
 * @param $ip_start - массив октетов начала интервала
 * @param $ip_end   - массив октетов конца интервала
 * В интервалах допускаются маски '*'
 * @return bool     - TRUE или FALSE, входит IP или нет в диапазон
 *
 * http://www.manhunter.ru/webmaster/39_proverka_prinadlezhnosti_ip_adresa_zadannomu_diapazonu.html
 */
function chk_ips($ip, $ip_start, $ip_end)
{
    if (!isset($ip_start)) $ip_start = array('*', '*', '*', '*');
    if (!isset($ip_end)) $ip_end = array('*', '*', '*', '*');

    for ($i = 0; $i < 4; $i++) {
        if ($ip_start[$i] == '*') $ip_start[$i] = '0';
        if ($ip_end[$i] == '*') $ip_end[$i] = '255';
    }

    $test_ip = kw_ip2long(join('.', $ip));
    $range_start = kw_ip2long(join('.', $ip_start));
    $range_end = kw_ip2long(join('.', $ip_end));

    /* if (($test_ip >= $range_start) && ($test_ip <= $range_end)) {
        // IP входит в интервал
        return true;
    } else {
        // IP не входит в интервал
        return false;
    } */
    return !!(($test_ip >= $range_start) && ($test_ip <= $range_end));
}

/**
 * Преобразование IP4 по модулю
 * @param string $ip
 * @return int
 */
function kw_ip2long($ip)
{
    if (($r = ip2long($ip)) < 0) {
        $r += 4294967296;
    }
    return $r;
}

/**
 * Функция разворачивания маски подсети
 * @param $ip массив октетов начала диапазона
 * @param $mask маска
 * @return array массив IP конца диапазона
 */
function addip($ip, $mask)
{
    // Количество IP в каждой маске
    $ip_count = Array(32 => 0, 31 => 1, 30 => 3, 29 => 7, 28 => 15, 27 => 31, 26 => 63,
        25 => 127, 24 => 255, 23 => 511, 22 => 1023, 21 => 2047, 20 => 4095,
        19 => 8191, 18 => 16383, 17 => 32767, 16 => 65535, 15 => 131071,
        14 => 262143, 13 => 524287, 12 => 1048575, 11 => 2097151,
        10 => 4194303, 9 => 8388607, 8 => 16777215, 7 => 33554431,
        6 => 67108863, 5 => 134217727, 4 => 268435455, 3 => 536870911,
        2 => 1073741823);
    $x = Array();
    $ips = $ip_count[$mask];

    $x[0] = $ip[0] + intval($ips / (256 * 256 * 256));
    $ips = ($ips % (256 * 256 * 256));

    $x[1] = $ip[1] + intval($ips / (256 * 256));
    $ips = ($ips % (256 * 256));

    $x[2] = $ip[2] + intval($ips / (256));
    $ips = ($ips % 256);

    $x[3] = $ip[3] + $ips;

    return ($x);
}

/**
 * Парсер диапазонов IP
 * @param $range строка диапазона
 * @return array|bool Массив из двух массивов ([0]=>ip_start, [1]=>ip_end) или FALSE если строка не является допустимым диапазоном
 */
function range_parser($range)
{
    $range = trim($range);
    // Проверка диапазона вида x.x.x.x-y.y.y.y
    if (strpos($range, "-")) {
        $tmp = explode("-", $range);
        $ip_start = explode(".", trim($tmp[0]));
        $ip_end = explode(".", trim($tmp[1]));
    } // Проверка диапазона вида x.x.x.x/y
    elseif (strpos($range, "/")) {
        $tmp = explode("/", $range);
        $ip_start = explode(".", $tmp[0]);
        // Развернуть маску подсети
        $ip_end = addip($ip_start, $tmp[1]);
    } // Проверка диапазона вида x.x.*.* или одиночного IP
    else {
        $ip_start = $ip_end = explode(".", $range);
    }
    // Простенькая проверка на корректность полученных диапазонов
    if (count($ip_start) == 4 && count($ip_end) == 4) {
        return array($ip_start, $ip_end);
    } else {
        return false;
    }
}

/*  EXAMPLES
$ip="127.0.12.7";                  // IP для проверки
$test_ip=explode(".",$ip);

$range="127.0.0.0/22";             // Маска подсети
$chk=range_parser($range);
chk_ips($test_ip,$chk[0],$chk[1]); // FALSE

$range="127.0.0.0-127.1.0.255";    // Интервал IP-адресов
$chk=range_parser($range);
chk_ips($test_ip,$chk[0],$chk[1]); // TRUE

$range="127.0.12.2";               // Одиночный IP
$chk=range_parser($range);
chk_ips($test_ip,$chk[0],$chk[1]); // FALSE

$range="127.*.*.*";                // Маска IP
$chk=range_parser($range);
chk_ips($test_ip,$chk[0],$chk[1]); // TRUE

//*/


/* === ПРОЧИЕ === */

/**
 * выполняет редирект на указанный url
 * @param $url
 * @desciption
 */
function redirect($url)
{
    if (headers_sent() === false) die(header('Location: ' . $url));
    else die($url);
}

/**
 * возвращает TRUE если скрипт вызван при помощи ajax
 * @param bool $debugmode
 * @return bool
 */
function isAjaxCall($debugmode = false)
{
    $debug = (isset($debugmode)) ? $debugmode : false;
    return ((!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) && (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')) || ($debug);
}
