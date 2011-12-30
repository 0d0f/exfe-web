<?php
require_once dirname(__FILE__)."/config.php";

/**
 * 国际化，取用户浏览器语言
 * @param NULL
 * @return $local
*/
$locale = "en_US"; // 默认en_US
if(array_key_exists("locale", $_COOKIE)){
    $locale = $_COOKIE["locale"];
}else if(array_key_exists("HTTP_ACCEPT_LANGUAGE", $_SERVER)){
    $locale = Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE']);
}
$exfe_res = new ResourceBundle($locale, INTL_RESOURCES);

/*
 * 判断并获取GET数据
 * @param $name:field name;
 * @return 如果存在，则返回GET的数据，否则返回空!
*/
function exGet($name)
{
    if (array_key_exists($name,$_GET))
	{
		return $_GET[$name];
	}else{
		return ("");
	}
}

/**
 * 判断并获取POST数据
 * @param $name:field name;
 * @return 如果存在，则返回POST的数据，否则返回空!
*/
function exPost($name)
{
    if (array_key_exists($name,$_POST)) {
        return $_POST[$name];
    } else {
        return ('');
    }
}

/**
 * 判断并获取REQUEST数据
 * @param $name:field name;
 * @return 如果存在，则返回REQUEST的数据，否则返回空!
*/
function exRequest($name)
{
	if (array_key_exists($name, $_REQUEST))
	{
		return $_REQUEST[$name];
	}else{
		return "";
	}
}


function buildICS($args)
{
    global $site_url;
    require_once 'lib/iCalcreator.class.php';

    $v = new vcalendar( array( 'unique_id' => 'exfe' ));

    $v->setProperty( 'X-WR-CALNAME'
                   , $args['title'] );          // set some X-properties, name, content.. .
    $v->setProperty( 'X-WR-CALDESC'
                   , "exfe cal" );
    $v->setProperty( 'X-WR-TIMEZONE'
                   , 'Asia/Shanghai' );

    $e = & $v->newComponent( 'vevent' );           // initiate a new EVENT
    //$e->setProperty( 'categories'
    //               , 'FAMILY' );                   // catagorize
    $begin_at=$args['begin_at'];
    $year=date("Y",strtotime($begin_at));
    $month=date("m",strtotime($begin_at));
    $day=date("d",strtotime($begin_at));
    $hour=date("H",strtotime($begin_at));
    $minute=date("i",strtotime($begin_at));
    $e->setProperty( 'dtstart'
                   , $year, $month, $day, $hour, $minute, 00 );   // 24 dec 2007 19.30
    //$e->setProperty( 'duration'
    //               , 0, 0, 3 );                    // 3 hours
    $e->setProperty( 'summary'
                   , $args['title'] );    // describe the event
    $e->setProperty( 'description'
                   , $args['description'] );    // describe the event
    $e->setProperty( 'location'
                   , $args['place_line1']."\r\n".$args['place_line2']  );                     // locate the event
    $e->setProperty( 'url'
                   , $site_url.'/!'.$args['cross_id_base62']);                     // locate the event

    $a = & $e->newComponent( 'valarm' );           // initiate ALARM
    $a->setProperty( 'action'
                   , 'DISPLAY' );                  // set what to do
    $a->setProperty( 'description'
                   , "exfe:".$args['title'] );          // describe alarm
    $a->setProperty( 'trigger'
                   , array( 'week' => 1 ));        // set trigger one week before

    $str = $v->createCalendar();                   // generate and get output in string, for testing?
    return $str;
}

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

function humanDateTime($timestamp,$time_type=0,$lang='en')
{
    if($timestamp<0)
    {
        $timestamp=0;
        $timestr="";
    } else {
        $timestr=", ".date("M j, Y ", $timestamp);
    }
    $datestr="";
    if($lang=='en')
    {
        if($time_type==0)
            $datestr=date("g:i A, M j, Y ", $timestamp);
        else if($time_type==1)
            $datestr="All day".$timestr;
        else if($time_type==2)
            $datestr="Anytime".$timestr;
    }

    return $datestr;
}

function RelativeTime($timestamp)
{
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

function int_to_base62($input)
{
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

/**
 * 随机产生字符串。
 * @param: string length
 * @return: rand string.
 */
function randStr($len=5, $type="normal")
{
    switch($type){
        case "num":
            $chars = '0123456789';
            $chars_len = 10;
            break;
        case "lowercase":
            $chars = 'abcdefghijklmnopqrstuvwxyz';
            $chars_len = 26;
            break;
        case "uppercase":
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $chars_len = 26;
            break;
        default:
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
            $chars_len = 62;
            break;
    }
    $string = '';
    for($len; $len>=1; $len--)
    {
        $position = rand() % $chars_len;//62 is the length of $chars
        $string .= substr($chars, $position, 1);
    }
    return $string;
}

/**
 * 取得微秒时间
 * @param NULL
 * @return: float microtime value.
 **/
function getMicrotime()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

function reverse_escape($str)
{
  $search=array("\\\\","\\0","\\n","\\r","\Z","\'",'\"');
  $replace=array("\\","\0","\n","\r","\x1a","'",'"');
  return str_replace($search,$replace,$str);
}
/**
 * 散列存储
 * @param NULL
 * @return array
 **/
function hashFileSavePath($savePath, $fileName=''){
    $hashFileName = md5(randStr(20).$fileName.getMicrotime().uniqid());
    $savePath = strtok($savePath, "/");
    $hashDir = $savePath."/".substr($hashFileName, 0, 1);
    $hashSubDir = $hashDir."/".substr($hashFileName, 1, 2);
    $webpath= "/".substr($hashFileName, 0, 1)."/".substr($hashFileName, 1, 2);


    $fileInfo = array(
        "fpath"      =>$hashSubDir,
        "fname"      =>$hashFileName,
        "webpath"      =>$webpath,
        "error"      =>0
    );

    if(is_dir($savePath)){
        if(!is_dir($hashSubDir)){
            $result = mkdir($hashSubDir, 0777, true);
            if(!$result){
                $fileInfo["error"] = 2;
            }
        }
    }else{
        $fileInfo["error"] = 1;
    }
    return $fileInfo;
}

/**
 * 获取散列存储路径
 * @param $fileName, $specialFilePath
 * @return string
 **/
function getHashFilePath($fileName='', $specialFilePath=''){
    if($fileName == ''){
        return false;
    }
    if($fileName == "default.png"){
        return "web";
    }
    if($specialFilePath != ""){
        return $specialFilePath."/".substr($fileName, 0, 1)."/".substr($fileName, 1, 2);
    }
    return substr($fileName, 0, 1)."/".substr($fileName, 1, 2);
}

/**
 * 获取用户头像
 * @param $fileName, $avatarSize
 * @return string
 **/
function getUserAvatar($fileName, $avatarSize){
    $pattern = "/(http[s]?:\/\/)/is";
    if(preg_match($pattern, $fileName)){
        return $fileName;
    }else{
        return IMG_URL."/".getHashFilePath($fileName)."/".$avatarSize."_".$avatarSize."_".$fileName;
    }
}

function autoLink($text) {
   $pattern = "/(((http[s]?:\/\/)|(www\.))(([a-z][-a-z0-9]+\.)?[a-z][-a-z0-9]+\.[a-z]+(\.[a-z]{2,2})?)\/?[a-z0-9._\/~#&=;%+?-]+[a-z0-9\/#=?]{1,1})/is";
   $text = preg_replace($pattern, " <a href='$1'>$1</a>", $text);
   // fix URLs without protocols
   $text = preg_replace("/href='www/", "href='http://www", $text);

   return $text;
}

function cleanText($content)
{
    $content=htmlspecialchars($content);
    $content=autoLink($content);
    return $content;

}

/**
 * 正则替换文本中的URL
 * @param: string.
 * @return: string.
 */
function ParseURL($str)
{
    return preg_replace(
        array(
            "/(?<=[^\]A-Za-z0-9-=\"'\\/])(https?|ftp|gopher|news|telnet|mms){1}:\/\/([A-Za-z0-9\/\-_+=.~!%@?#%&;:$\\()|]+)/is",
            //"/([\n\s])www\.([a-z0-9\-]+)\.([A-Za-z0-9\/\-_+=.~!%@?#%&;:$\[\]\\()|]+)((?:[^\x7f-\xff,\s]*)?)/is",
            "/([^\/\/])www\.([a-z0-9\-]+)\.([A-Za-z0-9\/\-_+=.~!%@?#%&;:$\[\]\\()|]+)((?:[^\x7f-\xff,\s]*)?)/is",
            "/(?<=[^\]A-Za-z0-9\/\-_.~?=:.])([_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*(\.[a-zA-Z]{2,4}))/si"
        ),
        array(
            "<a href=\"\\1://\\2\" target=\"_blank\">\\1://\\2</a>",
            "\\1<a href=\"http://www.\\2.\\3\\4\">[url]www.\\2.\\3\\4[/url]</a>",
            "<a href=\"mailto:\\0\">\\0</a>"
        ),
        ' '.$str
    );
}

/**
 * 简单的打包Array数组
 * @param $inArray
 * @return 打包后的字符串
**/
function simplePackArray($inArray)
{
    return preg_replace('/(.)/es',"str_pad(dechex(ord('\\1')),2,'0',STR_PAD_LEFT)",substr(base64_encode(rand().time()),0,9).base64_encode(serialize($inArray)));
}

/**
 * 简单的解包Array数组
 * @param $inString
 * @return 解包出来的Array数组
**/
function simpleUnpackArray($inString)
{
    return unserialize(base64_decode(substr(preg_replace('/(\w{2})/e',"chr(hexdec('\\1'))",$str),9)));
}

/**
 * 打包Array数组
 * @param $inArray
 * @return 打包后的字符串
**/
function packArray($inArray)
{
    return packString(serialize($inArray));
}

/**
 * 解包Array数组
 * @param $inString
 * @return 解包出来的Array数组
**/
function unpackArray($inString)
{
    return unserialize(unpackString($inString));
}

/**
 * 打包一个字符串。并且进行urlencode编码。
 * @param $string
 * @return 打包后的字符串
**/
function packString($str)
{
    $encode_str = exEncrypt($str,EXFE_PUB_KEY);
    return preg_replace('/(.)/es',"str_pad(dechex(ord('\\1')),2,'0',STR_PAD_LEFT)",substr(base64_encode(rand().time()),0,9).base64_encode($encode_str));
}

/**
 * 解包一个字符串。并且进行urldecode解码。
 * @param $string
 * @return 解包后的字符串
**/
function unpackString($str)
{
    return exDecrypt(base64_decode(substr(preg_replace('/(\w{2})/e',"chr(hexdec('\\1'))",$str),9)),EXFE_PUB_KEY);
}

/**
 * 将UTF-8编码的字符串转成十六进制用于Ajax传输
 * @param $string
 * @return 编码后的字符串
**/
function _BIN2HEX($str)
{
    $arr = @unpack("H*", $str);
    return $arr[1];
}

/**
 * 在服务器端将传入的十六进制Ajax内容解码！
 * @param $string
 * @return 解码后的字符串
**/
function _HEX2BIN($str)
{
    return @pack("H*", $str);
}

/**
 * ******************************************************
 * 通用加密解密方法
 * 开始
**/
function long2str($v, $w) {
    $len = count($v);
    $n = ($len - 1) << 2;
    if ($w) {
        $m = $v[$len - 1];
        if (($m < $n - 3) || ($m > $n)) return false;
        $n = $m;
    }
    $s = array();
    for ($i = 0; $i < $len; $i++) {
        $s[$i] = pack("V", $v[$i]);
    }
    if ($w) {
        return substr(join('', $s), 0, $n);
    }
    else {
        return join('', $s);
    }
}

function str2long($s, $w) {
    $v = unpack("V*", $s. str_repeat("\0", (4 - strlen($s) % 4) & 3));
    $v = array_values($v);
    if ($w) {
        $v[count($v)] = strlen($s);
    }
    return $v;
}

function int32($n) {
    while ($n >= 2147483648) $n -= 4294967296;
    while ($n <= -2147483649) $n += 4294967296;
    return (int)$n;
}

function exEncrypt($str, $key) {
    if ($str == "") {
        return "";
    }
    $v = str2long($str, true);
    $k = str2long($key, false);
    if (count($k) < 4) {
        for ($i = count($k); $i < 4; $i++) {
            $k[$i] = 0;
        }
    }
    $n = count($v) - 1;

    $z = $v[$n];
    $y = $v[0];
    $delta = 0x9E3779B9;
    $q = floor(6 + 52 / ($n + 1));
    $sum = 0;
    while (0 < $q--) {
        $sum = int32($sum + $delta);
        $e = $sum >> 2 & 3;
        for ($p = 0; $p < $n; $p++) {
            $y = $v[$p + 1];
            $mx = int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
            $z = $v[$p] = int32($v[$p] + $mx);
        }
        $y = $v[0];
        $mx = int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
        $z = $v[$n] = int32($v[$n] + $mx);
    }
    return long2str($v, false);
}

function exDecrypt($str, $key) {
    if ($str == "") {
        return "";
    }
    $v = str2long($str, false);
    $k = str2long($key, false);
    if (count($k) < 4) {
        for ($i = count($k); $i < 4; $i++) {
            $k[$i] = 0;
        }
    }
    $n = count($v) - 1;

    $z = $v[$n];
    $y = $v[0];
    $delta = 0x9E3779B9;
    $q = floor(6 + 52 / ($n + 1));
    $sum = int32($q * $delta);
    while ($sum != 0) {
        $e = $sum >> 2 & 3;
        for ($p = $n; $p > 0; $p--) {
            $z = $v[$p - 1];
            $mx = int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
            $y = $v[$p] = int32($v[$p] - $mx);
        }
        $z = $v[$n];
        $mx = int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
        $y = $v[0] = int32($v[0] - $mx);
        $sum = int32($sum - $delta);
    }
    return long2str($v, true);
}
/**
 * 通用加密解密方法
 * 结束
 * ******************************************************
**/
function createToken(){
    $randString = randStr(16);
    $hashString = md5(base64_encode(pack('N5', mt_rand(), mt_rand(), mt_rand(), mt_rand(), uniqid())));
    return md5($hashStr.$randString.getMicrotime().uniqid()).time();
}
