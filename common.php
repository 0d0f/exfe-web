<?php
function humanIdentity($identity,$user)
{
    $provider=$identity["provider"];

    if($identity["name"]=="")
        $identity["name"]=$user["name"];
    if($identity["avatar_file_name"]=="")
        $identity["avatar_file_name"]=$user["avatar_file_name"];
    if($provider=="email")
    {
        if($identity["name"]=="")
            $identity["name"]=$identity["external_identity"];
    }
    if($identity["avatar_file_name"]=="")
        $identity["avatar_file_name"]="default.png";
    return $identity;
}
function humanDateTime($timestamp,$lang='en')
{
    if($lang=='en')
        return date("g:i A, M j, Y ", $timestamp);
}
function RelativeTime($timestamp){
    if($timestamp<0)
        return 0;
    $difference = time() - $timestamp;
    $periods = array("sec", "min", "hour", "day", "week",
            "month", "year", "decade");
    $lengths = array("60","60","24","7","4.35","12","10");

    if ($difference > 0) { // this was in the past
        $ending = "ago";
    } else { // this was in the future
        $difference = -$difference;
        $ending = "later";
    }
    for($j = 0; $difference >= $lengths[$j]; $j++)
    {
        if ($lengths[$j]==0)
        {
            $difference = 0;
            break;
        }
        else
            $difference /= $lengths[$j];
    }
    $difference = round($difference);
    if($difference != 1) $periods[$j].= "s";
    $text = "$difference $periods[$j] $ending";
    return $text;
}


function base62_to_int($input)
{
    $base62= array (
            '0'=>0, '1'=>1, '2'=>2, '3'=>3, '4'=>4, '5'=>5,'6'=>6,'7'=>7,'8'=>8,'9'=>9,
            'a'=>10, 'b'=>11, 'c'=>12, 'd'=>13, 'e'=>14, 'f'=>15, 'g'=>16, 'h'=>17,
            'i'=>18, 'j'=>19, 'k'=>20, 'l'=>21, 'm'=>22, 'n'=>23, 'o'=>24, 'p'=>25,
            'q'=>26, 'r'=>27, 's'=>28, 't'=>29, 'u'=>30, 'v'=>31, 'w'=>32, 'x'=>33,
            'y'=>34, 'z'=>35, 
            'A'=>36, 'B'=>37, 'C'=>38, 'D'=>39, 'E'=>40, 'F'=>41, 'G'=>42, 'H'=>43,
            'I'=>44, 'J'=>45, 'K'=>46, 'L'=>47, 'M'=>48, 'N'=>49, 'O'=>50, 'P'=>51,
            'Q'=>52, 'R'=>53, 'S'=>54, 'T'=>55, 'U'=>56, 'V'=>57, 'W'=>58, 'X'=>59,
            'Y'=>60, 'Z'=>61
            );
    $input=strval($input);
    $output=0;
    $len=strlen($input);
    for($i=0;$i<$len;$i++)
    {

        $num=$base62["$input[$i]"];
        $output=$output+$num*pow(62,($len-$i-1));
    }
    return $output;
}
function int_to_base62($input) {
    $base62= array (
            '0', '1', '2', '3', '4', '5','6','7','8','9',
            'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h',
            'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p',
            'q', 'r', 's', 't', 'u', 'v', 'w', 'x',
            'y', 'z', 
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H',
            'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P',
            'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X',
            'Y', 'Z'
            );

    $output="";
    while($input!=0)
    {
        $mod=$input%62;
        $output=$base62[$mod].$output;
        $input=($input-$mod)/62;
    }
    return $output;
}

function getRealIpAddr()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
    {
      $ip=$_SERVER['HTTP_CLIENT_IP'];
    }
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
    {
      $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    else
    {
      $ip=$_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

/**
 * 依据指定长度截取字符串
 * 传入值：$sourceStr　－　源字符串，即需要截取的字符串。
           $outStrLen　－　输出字符串的长度！
 * 返回值：经过处理后的字符串！
**/
function mbString($sourceStr,$outStrLen)
{
    $curStrLen = mb_strlen($sourceStr,"UTF-8");
    if($curStrLen > $outStrLen){
        $echoStr = mb_substr($sourceStr, 0, $outStrLen, "UTF-8")."...";
    }else{
        $echoStr = $sourceStr;
    }
    return $echoStr;
}
