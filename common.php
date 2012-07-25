<?php
require_once dirname(__FILE__)."/config.php";
require_once dirname(__FILE__)."/Classes/EFObject.php";

date_default_timezone_set('UTC');

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
    $begin_at=$args['begin_at'][0];
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
                   , $args['place']['line1']."\r\n".$args['place']['line2']);                     // locate the event
    $e->setProperty( 'url'
                   , $site_url.'/!'.$args['cross_id']);                     // locate the event

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


function getHashedFilePath($filename = '') {
    // do hash
    $hashed_name = md5(json_encode(array(
        'action'    => 'save_file',
        'file_name' => $filename,
        'microtime' => Microtime(),
        'random'    => Rand(0, Time()),
        'unique_id' => Uniqid(),
    )));
    // get path
    $hashed_path
  = IMG_FOLDER
  . '/' . substr($hashed_name, 0, 1)
  . '/' . substr($hashed_name, 1, 2);
    // make dir
    if(!is_dir($hashed_path)){
        if(!mkdir($hashed_path, 0777, true)) {
            return null;
        }
    }
    // return
    return array('path' => $hashed_path, 'filename' => $hashed_name);
}


function getAvatarUrl($provider = '', $external_id = '', $raw_avatar = '', $size = '80_80', $spec_fallback = '') {
    if ($raw_avatar) {
        $raw_avatar
      = preg_match('/^http(s)*:\/\/.+$/i', $raw_avatar)
      ? $raw_avatar
      : (IMG_URL
      . '/' . substr($raw_avatar, 0, 1)
      . '/' . substr($raw_avatar, 1, 2)
      . '/' . "{$size}_{$raw_avatar}");
    } else {
        $raw_avatar = $spec_fallback ?: (API_URL . "/v2/avatar/get?provider={$provider}&external_id={$external_id}");
        if ($provider === 'email') {
            $raw_avatar = 'http://www.gravatar.com/avatar/' . md5($external_id) . '?d=' . urlencode($raw_avatar);
        }
    }
    return $raw_avatar;
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


/**
 * @todo: removing!!!!!!!
 * @by: @leaskh
 */
function createToken(){
    $randString = randStr(16);
    $hashString = md5(base64_encode(pack('N5', mt_rand(), mt_rand(), mt_rand(), mt_rand(), uniqid())));
    return md5($hashStr.$randString.getMicrotime().uniqid()).time();
}


function json_encode_nounicode($code)
{
    $code = json_encode(urlencodeAry($code));
    return urldecode($code);
}


function urlencodeAry($data)
{
    if(is_array($data))
    {
        foreach($data as $key=>$val)
        {
            if(is_numeric($val))
                $data[$key] = $val;
            else
                $data[$key] = urlencodeAry($val);
        }
        return $data;
    }
    else
    {
        return urlencode($data);
    }
}


/**
 * convert ip to int number
 *
 */
function ipToInt($IPAddress) {
    $ipArr = explode('.', $IPAddress);
    if (count($ipArr) != 4) return 0;
    $intIP = 0;
    foreach ($ipArr as $k => $v){
        $intIP += (int)$v*pow(256, intval(3-$k));
    }
    return $intIP;
}


function apiError($code,$errorType,$errorDetail = '') {
    $meta["code"]=$code;
    $meta["errorType"]=$errorType;
    $meta["errorDetail"]=$errorDetail;
    echo json_encode(array("meta"=>$meta,"response"=>new stdClass));
    exit(0);
}


function apiResponse($object) {
    $meta["code"]=200;
    echo json_encode(array("meta"=>$meta,"response"=>$object));
    exit(0);
}


function mgetUpdate($cross_ids)
{
    $fields=implode($cross_ids," ");
    $redis = new Redis();
    $redis->connect(REDIS_SERVER_ADDRESS, REDIS_SERVER_PORT);
    if(sizeof($cross_ids)>0)
    {
        $key=$cross_id;
        $update=$redis->HMGET("cross:updated",$cross_ids);
        return $update;
    }
}


function getUpdate($cross_id){
    if(intval($cross_id)>0)
    {
        $key=$cross_id;
        $redis = new Redis();
        $redis->connect(REDIS_SERVER_ADDRESS, REDIS_SERVER_PORT);
        $update=json_decode($redis->HGET("cross:updated",$key),true);
        return $update;
    }
}


function saveUpdate($cross_id, $updated) {
    if(intval($cross_id) > 0) {
        $key = $cross_id;
        $redis = new Redis();
        $redis->connect(REDIS_SERVER_ADDRESS, REDIS_SERVER_PORT);
        $update = json_decode($redis->HGET("cross:updated",$key),true);
        foreach($updated as $k => $v)
            $update[$k] = $v;

        $update_json=json_encode($update);
        $redis->HSET("cross:updated",$key,$update_json);
    }
}


/**
 * Dictionary:
 * http://plugins.svn.wordpress.org/sil-dictionary-webonary/trunk/include/dictionary-search.php
 * http://stackoverflow.com/questions/5074161/what-is-the-most-efficient-way-to-whitelist-utf-8-characters-in-php
 */
function get_CJK_unicode_ranges() {
    return array(
        '[\x{2E80}-\x{2EFF}]',   # CJK Radicals Supplement
        '[\x{2F00}-\x{2FDF}]',   # Kangxi Radicals
        '[\x{2FF0}-\x{2FFF}]',   # Ideographic Description Characters
        '[\x{3000}-\x{303F}]',   # CJK Symbols and Punctuation
        '[\x{3040}-\x{309F}]',   # Hiragana
        '[\x{30A0}-\x{30FF}]',   # Katakana
        '[\x{3100}-\x{312F}]',   # Bopomofo
        '[\x{3130}-\x{318F}]',   # Hangul Compatibility Jamo
        '[\x{3190}-\x{319F}]',   # Kanbun
        '[\x{31A0}-\x{31BF}]',   # Bopomofo Extended
        '[\x{31F0}-\x{31FF}]',   # Katakana Phonetic Extensions
        '[\x{3200}-\x{32FF}]',   # Enclosed CJK Letters and Months
        '[\x{3300}-\x{33FF}]',   # CJK Compatibility
        '[\x{3400}-\x{4DBF}]',   # CJK Unified Ideographs Extension A
        '[\x{4DC0}-\x{4DFF}]',   # Yijing Hexagram Symbols
        '[\x{4E00}-\x{9FFF}]',   # CJK Unified Ideographs
        '[\x{A000}-\x{A48F}]',   # Yi Syllables
        '[\x{A490}-\x{A4CF}]',   # Yi Radicals
        '[\x{AC00}-\x{D7AF}]',   # Hangul Syllables
        '[\x{F900}-\x{FAFF}]',   # CJK Compatibility Ideographs
        '[\x{FE30}-\x{FE4F}]',   # CJK Compatibility Forms
        '[\x{1D300}-\x{1D35F}]', # Tai Xuan Jing Symbols
        '[\x{20000}-\x{2A6DF}]', # CJK Unified Ideographs Extension B
        '[\x{2F800}-\x{2FA1F}]', # CJK Compatibility Ideographs Supplement
    );
}


function checkCjk($string) {
    return preg_match('/' . implode('|', get_CJK_unicode_ranges()) . '/u', $string);
}
