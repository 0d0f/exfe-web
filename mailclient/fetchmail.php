<?php 
include("mailconf.php");
include("receivemail.class.php");
#$username = 'x@exfe.com';
#$password = 'V:%wGHsuOXI}x)il';
#$interval=10;

$shutdown=false;

$errorcount=array();

$PIDFILE = getenv('PIDFILE');
if ($PIDFILE) {
    $pid=getmypid();
    $r=file_put_contents($PIDFILE, $pid) or
        die('Could not write PID information to ' . $PIDFILE);
}
else
        die('must write pidfile: ' . $PIDFILE);

fwrite(STDOUT , '*** Starting worker '.$worker."\n");

$obj = new receiveMail($username,$password,$username,"{imap.gmail.com:993/imap/ssl/novalidate-cert}INBOX");
$obj->connect();         

while(true) {
    if($shutdown) {
        break;
    }
    dofetchandpost($obj);
    usleep($interval * 1000000);
}

$obj->close_mailbox();   //Close Mail Box

function registerSigHandlers()
{
    if(!function_exists('pcntl_signal')) {
        return;
    }
    pcntl_signal(SIGTERM, 'shutdown');
    pcntl_signal(SIGINT, 'shutdown');
    pcntl_signal(SIGQUIT, 'shutdown');
}

function thislog($message)
{
    #if($this->logLevel == self::LOG_NORMAL) {
    #    fwrite( STDOUT , "*** " . $message . "\n");
    #}
    #else if($this->logLevel == self::LOG_VERBOSE) {
        fwrite( STDOUT , "** [" . strftime('%T %Y-%m-%d') . "] " . $message . "\n");
    #}
}


function shutdown()
{
    global $shutdown;
    $shutdown = true;
    thislog('Exiting...');
    #$this->shutdown = true;
    #$this->log('Exiting...');
}

function dofetchandpost($obj)
{
    global $errorcount;
    if($obj->isconnected()==false)
    {
        thislog("reconnecting...");
        $obj->connect();
    }

    $tot = $obj->getTotalMails(); 
    thislog("new mail:".$tot);
    if($tot>0)
    {
        for($i = $tot; $i > 0; $i--)
        {
            $head = $obj->getHeaders($i);  
            $to=$head["to"];
            $from=$head["from"];
            $subject=$head["subject"];
            
            $cross_id="";
            #$cross_id_base62="";
            #$match_result=preg_match('/^x\+([0-9a-zA-Z]+)@exfe.com/',$to,$matches);
            #if($match_result==0)
            #    $match_result=preg_match('/<x\+([0-9a-zA-Z]+)@exfe.com>/',$to,$matches);
            $match_result=preg_match('/^x\+([0-9]+)@exfe.com/',$to,$matches);
            if($match_result==0)
                $match_result=preg_match('/<x\+([0-9]+)@exfe.com>/',$to,$matches);

            if($match_result>0)
            {
                $cross_id=$matches[1];
            }

            $body=$obj->getBody($i);  
            
            if($body["charset"]!="" && strtolower($body["charset"])!="utf-8")
            {
                $body["body"]=mb_convert_encoding($body["body"],"utf-8",$body["charset"]);
            }
            $message = $body["body"];
            $message = str_ireplace("<br>","\n",$message);
            $message = str_ireplace("</div>","\n",$message);
            $message = str_ireplace("</p>","\n",$message);
            $message = str_ireplace("<br/>","\n",$message);
            $message = str_ireplace("<br />","\n",$message);
        
            $str=strip_gmail($message);
            if($str!="")
                $message=$str;
            $source_str=strip_html_tags($message);
            mb_internal_encoding("UTF-8");
            if(mb_strlen($source_str)>233)
        	    $message=mb_substr($source_str,0,233)."...";
            else
                $message=$source_str;

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
            print "post comment:".$cross_id." ".$from." ".$result_str."\r\n";
        
        
            if($cross_id!="")
            {
                print $from;
                print "\r\n";
                print $cross_id;
                print "\r\n";
                $result_str=html_entity_decode($result_str, ENT_QUOTES, 'UTF-8');
                print trim($result_str);
    
                $result=postcomment($cross_id,$from,$result_str);
                if($result->response->success=="true")
                {
                    $move_r=$obj->moveMails($i,"posted");
                    echo "\r\npost send\r\n";
                    if($move_r==true)
                        echo "\r\nArchive mail $move_r \r\n";
    
                }
                else
                {
                    if($result->response->error_code=="403")
                    {
                        $mail["to"]=$from;
                        $mail["title"]=$subject;
                        $mail["body"]="Sorry for the inconvenience, but email you just sent to EXFE was not sent from an attendee identity to the X (cross). Please try again from the correct email address.\n -- ";
                        $mail["body"].="\n".$body;
                        require_once '../lib/Resque.php';
                        date_default_timezone_set('GMT');
                        Resque::setBackend(RESQUE_SERVER);
                        $jobId = Resque::enqueue("textemail","textemail_job" , $mail, true);
                        if($jobId!="")
                        {
                            $move_r=$obj->moveMails($i,"error");
                            echo "\r\npost error\r\n";
                            if($move_r==true)
                            {
                                unset($errorcount[$error_key]);
                                echo "\r\n move mail to error box \r\n";
                            }
                        }
                        break;

                        //send error mail to user @ $from
                    }
                    $error_key=md5($cross_id.$from.$result_str);
                    $error_count=intval($errorcount[$error_key]);
                    if($error_count<=3)
                    {
                        $errorcount[$error_key]=$error_count+1;
                        print "\r\n add count \r\n";
                    }
                    else
                    {
                        $move_r=$obj->moveMails($i,"error");
                        echo "\r\npost error\r\n";
                        if($move_r==true)
                        {
                            unset($errorcount[$error_key]);
                            echo "\r\n move mail to error box \r\n";
                        }
                    }
                }
            }
            else
            {
                    $error_key=md5($cross_id.$from.$result_str);
                    $error_count=intval($errorcount[$error_key]);
                    if($error_count<=3)
                    {
                        $errorcount[$error_key]=$error_count+1;
                        print "\r\n add count \r\n";
                    }
                    else
                    {
                        $move_r=$obj->moveMails($i,"error");
                        echo "\r\npost error\r\n";
                        if($move_r==true)
                        {
                            unset($errorcount[$error_key]);
                            echo "\r\n move mail to error box \r\n";
                        }
                    }
            }
        }
    }

    #$check=$obj->checkMails();
    #var_dump($check);
    #if($check->Nmsgs>0)
    #{
    #    $head = $obj->getHeaders(1);  
    #    print_r($head);
    #}

}


function if_replys_or_signature($line)
{
   $flag =false;
   if($line=="--" ||$line=="--&nbsp;" )
       return true;
   $flag = strpos($line,"-----Original Message-----");

   if($flag === false)
    $flag = strpos($line,"________________________________");

   if($flag === false)
    $flag = strpos($line,"Sent from my iPhone");

   if($flag === false)
    $flag = strpos($line,"Sent from my BlackBerry");

   if($flag === false)
       if(preg_match('/^From:.*[mailto:.*]/',$line)==1)
           return true;

   if($flag === false)
       if(preg_match('/^On (.*) wrote:/',$line)==1)
           return true;
   if($flag === false)
       if(preg_match('/^发自我的 iPhone/',$line)==1)
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
        $flag=strpos($text,"<div class=\"gmail_quote\"");
        if($flag>0)
            return trim(substr($text,0,$flag));
        return "";
}

function strip_html_tags($text)
{
    $string = preg_replace('/<script\b[^>]*>(.*?)<\/script>/i', "", $text);
    $string = preg_replace('/<style\b[^>]*>(.*?)<\/style>/i', "", $string);
    $string = strip_tags($string);
    return trim($string);
}

function postcomment($cross_id,$from,$comment)
{
    $fields = array(
                'cross_id'=>$cross_id,
                'from'=>$from,
                'comment'=> $comment,
                'postkey'=> POSTKEY
            );
    
    thislog("post comment:".$fields);
    foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
    rtrim($fields_string,'&');
    
    $ch = curl_init();
    
    curl_setopt($ch,CURLOPT_URL,EmailPost_link);
    curl_setopt($ch,CURLOPT_POST,count($fields));
    curl_setopt($ch,CURLOPT_POSTFIELDS,$fields_string);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    $resultobj=json_decode($result);
    return $resultobj;
    //->response->success;

}


