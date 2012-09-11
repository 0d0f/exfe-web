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


function getAvatarUrl($raw_avatar, $size = '80_80') {
    return $raw_avatar
         ? (preg_match('/^http(s)*:\/\/.+$/i', $raw_avatar)
          ? $raw_avatar
          : (IMG_URL
           . '/' . substr($raw_avatar, 0, 1)
           . '/' . substr($raw_avatar, 1, 2)
           . '/' . "{$size}_{$raw_avatar}"))
         : '';
}


function getDefaultAvatarUrl($name) {
    return $name ? (API_URL . '/v2/avatar/default?name=' . urlencode($name)) : '';
}


function createToken(){
    $randString = randStr(16);
    $hashString = md5(base64_encode(pack('N5', mt_rand(), mt_rand(), mt_rand(), mt_rand(), uniqid())));
    return md5($hashStr.$randString.getMicrotime().uniqid()).time();
}


/**
 * @todo: removing!!!!!!!
 * @by: @leaskh
 */
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
