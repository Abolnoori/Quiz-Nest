<?php
function gregorian_to_jalali($gy, $gm, $gd) {
    $g_d_m = array(0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334);
    $jy = ($gy <= 1600) ? 0 : 979;
    $gy -= ($gy <= 1600) ? 621 : 1600;
    $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
    $days = (365 * $gy) + ((int)(($gy2 + 3) / 4)) - ((int)(($gy2 + 99) / 100))
        + ((int)(($gy2 + 399) / 400)) - 80 + $gd + $g_d_m[$gm - 1];
    $jy += 33 * ((int)($days / 12053));
    $days %= 12053;
    $jy += 4 * ((int)($days / 1461));
    $days %= 1461;
    $jy += (int)(($days - 1) / 365);
    if ($days > 365) $days = ($days - 1) % 365;
    $jm = ($days < 186) ? 1 + (int)($days / 31) : 7 + (int)(($days - 186) / 30);
    $jd = 1 + (($days < 186) ? ($days % 31) : (($days - 186) % 30));
    return array($jy, $jm, $jd);
}

function jdate($format, $timestamp = null) {
    if ($timestamp === null) {
        $timestamp = time();
    }
    
    $date = explode('-', date('Y-m-d', $timestamp));
    $time = date('H:i:s', $timestamp);
    $jalali_date = gregorian_to_jalali($date[0], $date[1], $date[2]);
    
    $jy = $jalali_date[0];
    $jm = $jalali_date[1];
    $jd = $jalali_date[2];
    
    $week = array(
        'شنبه',
        'یکشنبه',
        'دوشنبه',
        'سه‌شنبه',
        'چهارشنبه',
        'پنج‌شنبه',
        'جمعه'
    );
    
    $months = array(
        '',
        'فروردین',
        'اردیبهشت',
        'خرداد',
        'تیر',
        'مرداد',
        'شهریور',
        'مهر',
        'آبان',
        'آذر',
        'دی',
        'بهمن',
        'اسفند'
    );
    
    $out = '';
    $chars = str_split($format);
    
    foreach ($chars as $char) {
        switch ($char) {
            case 'Y':
                $out .= $jy;
                break;
            case 'y':
                $out .= substr($jy, 2);
                break;
            case 'm':
                $out .= str_pad($jm, 2, '0', STR_PAD_LEFT);
                break;
            case 'n':
                $out .= $jm;
                break;
            case 'd':
                $out .= str_pad($jd, 2, '0', STR_PAD_LEFT);
                break;
            case 'j':
                $out .= $jd;
                break;
            case 'l':
                $out .= $week[date('w', $timestamp)];
                break;
            case 'F':
                $out .= $months[$jm];
                break;
            case '/':
                $out .= '/';
                break;
            default:
                $out .= $char;
        }
    }
    
    return $out;
}
?> 