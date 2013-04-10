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


function apiError($code,$errorType,$errorDetail = '') {
    $meta["code"]=$code;
    $meta["errorType"]=$errorType;
    $meta["errorDetail"]=$errorDetail;
    echo json_encode(array("meta"=>$meta,"response"=>new stdClass));
    exit(0);
}


function apiResponse($object, $code = 200) {
    $meta["code"] = $code;
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


// common redis cache access by @leaskh {

$redis_cache = new Redis();
$redis_cache->connect(REDIS_CACHE_ADDRESS, REDIS_CACHE_PORT);

function getCache($key) {
    global $redis_cache;
    $value = $redis_cache->get($key);
    return $key && $value ? unserialize($value) : null;
}

function setCache($key, $value) {
    global $redis_cache;
    if ($key && $value) {
        $redis_cache->SET($key, serialize($value));
        $redis_cache->setTimeout($key, 604800); // 60*60*24*7
    }
}

function delCache($key) {
    global $redis_cache;
    if ($key) {
        $redis_cache->del($key);
    }
}

// }


function deepClone($object) {
    return unserialize(serialize($object));
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


function formatName($string, $length = 30) {
    $string = mb_substr($string, 0, $length, 'utf8');
    $string = preg_replace('/\r\n|\n\r|\r|\n/', ' ',  $string);
    return $string;
}


function formatTitle($string, $length = 144) {
    $string = trim(mb_substr($string, 0, $length, 'utf8'));
    $string = preg_replace('/\r\n|\n\r|\r/',    "\n", $string, 1);
    $string = preg_replace('/\r\n|\n\r|\r|\n/', ' ',  $string);
    return $string;
}


function formatDescription($string, $length = 233) {
    $string = trim($length ? mb_substr($string, 0, $length, 'utf8') : $string);
    $string = preg_replace('/\r\n|\n\r|\r|\n/', "\n", $string);
    return $string;
}


function validatePassword($string) {
    return mb_strlen($string, 'utf8') >= 4;
}


function validatePhoneNumber($string) {
    return preg_match('/^\+[0-9]{5,15}$/', $string);
}
