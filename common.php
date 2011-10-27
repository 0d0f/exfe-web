<?php

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
    if (array_key_exists($name,$_POST))
	{
		return $_POST[$name];
	}else{
		return ("");
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
    $datestr="";
    if($lang=='en')
    {
        if($time_type==0)
            $datestr=date("g:i A, M j, Y ", $timestamp);
        else if($time_type==1)
            $datestr="All day, ".date("M j, Y ", $timestamp);
        else if($time_type==2)
            $datestr="Anytime, ".date("M j, Y ", $timestamp);
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

    $fileInfo = array(
        "fpath"      =>$hashSubDir,
        "fname"      =>$hashFileName,
        "error"      =>0
    );

    if(is_dir($savePath)){
        if(!is_dir($hashDir)){
            try{
                mkdir($hashDir, 0777);
            }catch(Exception $e){ 
                $fileInfo["error"] = 2;
            }
            try{
                mkdir($hashSubDir, 0777);
            } catch (Exception $e) {
                $fileInfo["error"] = 3;
            }
        }
    }else{
        $fileInfo["error"] = 1;
    }
    return $fileInfo;
}

/**
 * 获取散列存储路径
 * @param $rootPath, $fileName
 * @return string
 **/
function getHashFilePath($rootPath='', $fileName=''){
    if($fileName == ''){
        return false;
    }else if($fileName == "default.png"){
        return $rootPath;
    }
    $rootPath = $rootPath == '' ? $rootPath : strtok($rootPath,"/")."/";
    return $rootPath.substr($fileName, 0, 1)."/".substr($fileName, 1, 2);
}
