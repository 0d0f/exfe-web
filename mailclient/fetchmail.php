<?php 
include("receivemail.class.php");

$username = 'x@exfe.com';
$password = 'V:%wGHsuOXI}x)il';

$obj = new receiveMail($username,$password,$username,"{imap.gmail.com:993/imap/ssl/novalidate-cert}INBOX");
$obj->connect();         
$tot = $obj->getTotalMails(); 
for($i = $tot; $i > 0; $i--)
{
    $head = $obj->getHeaders($i);  
    $to=$head["to"];
    $from=$head["from"];
    
    $cross_id_base62="";
    if(preg_match('/^x\+([0-9a-zA-Z]+)@exfe.com/',$to,$matches)>0)
        $cross_id_base62=$matches[1];
    $body=$obj->getBody($i);  
    
    if($body["charset"]!="" && strtolower($body["charset"])!="utf-8")
    {
        $body["body"]=mb_convert_encoding($body["body"],"utf-8",$body["charset"]);
    }
    $message = $body["body"];
    $message = str_ireplace("<br>","\n",$message);
    $message = str_ireplace("<br/>","\n",$message);
    $message = str_ireplace("<br />","\n",$message);

    $str=strip_gmail($message);
    if($str!="")
        $message=$str;
	$message=substr(strip_html_tags($message),0,233);
    $message_array=explode("\n",$message);
    $result_str="";
    $endflag=false;
    if(sizeof($message_array)>0)
    {
       foreach($message_array as $line)
       {
         $r=if_replys_or_signature(trim($line));
         if($r===false)
         {
             $result_str.=$line."\n";
         }
         else
             break;
       }
    }


    if($cross_id_base62!="")
    {
        print $from;
        print "\r\n";
        print $cross_id_base62;
        print "\r\n";
        print trim($result_str);
        $result=postcomment($cross_id_base62,$from,$result_str);
        print "post:".$result."\r\n";
        print "========\r\n";
    }
}

$obj->close_mailbox();   //Close Mail Box
function if_replys_or_signature($line)
{
   $flag =false;
   if($line=="--")
       return true;
   $flag = strpos($line,"-----Original Message-----");

   if($flag === false)
    $flag = strpos($line,"________________________________");

   if($flag === false)
    $flag = strpos($line,"Sent from my iPhone");

   if($flag === false)
    $flag = strpos($line,"Sent from my BlackBerry");

   if($flag === false)
       if(preg_match('/^On (.*) wrote:/',$line)==1)
           return true;
   if($flag === false)
       if(trim($line)=="")
           return true;
   if($flag===0)
       return true;
   return false;
}

function strip_gmail($text)
{
        $string = preg_replace( '@<(script|style)[^>]*?>.*?@si', '', $text);
        $flag=strpos($text,"<div class=\"gmail_quote\"");
        if($flag>0)
            return trim(substr($text,0,$flag));
        return "";
}

function strip_html_tags($text)
{
    $string = preg_replace( '@<(script|style)[^>]*?>.*?@si', '', $text);
    $string = strip_tags($string);
    return trim($string);
}

function postcomment($cross_id_base62,$from,$comment)
{
$url = 'http://dev.0d0f.com/conversation/emailsave';
$fields = array(
            'cross_id_base62'=>$cross_id_base62,
            'from'=>$from,
            'comment'=> $comment
        );
foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
rtrim($fields_string,'&');

$ch = curl_init();

curl_setopt($ch,CURLOPT_URL,$url);
curl_setopt($ch,CURLOPT_POST,count($fields));
curl_setopt($ch,CURLOPT_POSTFIELDS,$fields_string);
curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);

$result = curl_exec($ch);
print_r($result);
curl_close($ch);

$resultobj=json_decode($result);
return $resultobj->response->success;

}
