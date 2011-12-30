<?php
require_once("../config.php");
require_once("connect.php");

define("max_msg_len","112");
mb_internal_encoding("UTF-8");
class Apn_Job
{
    public function perform()
    {
        #global $apn_connect;
        
	    #$title=$this->args['title'];
	    #$name=$this->args['name'];
	    #$cross_id=$this->args['cross_id'];
	    #if($this->args['name']=="")
		#    $name=$this->args['external_identity'];
	    #$message=$name." 邀请你参加活动 " .$title;

        $args=$this->args;
        if($args["job_type"]=="invitation")
        {
            $this->generateInvitationPush($args);
        }
        else if($args["job_type"]=="conversation")
        {
            $this->generateConversationPush($args);
        }
        //build a msg body, throw into iOSpush queue
    }
    public function generateConversationPush($args)
    {
    
#%IDENTITY_NAME: %COVN_POST。（on “%X_TITLE”）
        $title=replacemarks($args["title"]);
        $by_identity=$args["by_identity"];
        $name=replacemarks($by_identity["name"]);
    
        $content=replacemarks($args["comment"]);
        $to_identities=$args["to_identities"];

        $msgdefaultlen=strlen(":  (on \\\"\\\")");

        if(strlen($content)+strlen($title)>max_msg_len-strlen($name)-$msgdefaultlen)
        {
            $contentlen=strlen($content);
            $titlelen=strlen($title);
            if($contentlen > max_msg_len-strlen($name)-$msgdefaultlen-10) //keep 10 byte for title 
            {
                $content=utf8substr($content,0,max_msg_len-strlen($name)-$msgdefaultlen-10)."...";
            }
            $title=utf8substr($title,0,max_msg_len-strlen($name)-strlen($content)-$msgdefaultlen)."...";
        }
        $msg=$name.": ".$content." (on \\\"".$title."\\\")";
        $msgbodyobj=array();
        $msgbodyobj["msg"]=$msg;
        $msgbodyobj["cross_id"]=$args["cross_id"];
    
        foreach($to_identities as $to_identity)
        {
           if( $to_identity["provider"]=="iOSAPN")
           {
               $msgbodyobj["external_identity"]=$to_identity["external_identity"];
               $this->deliver($msgbodyobj);
           }
        }
    }

    public function generateInvitationPush($args)
    {
            //find out which invitation should be push to a device, and which invitation is belongs to host and send grather success hint.
            $invitations=$args["invitations"];
            $host_identity_id=$args["host_identity_id"];
            foreach ($invitations as $invitation)
            {
                $isHost=FALSE;
                $identities=$invitation["identities"];
                $msgbodyobj=array();
                foreach($identities as $identity)
                {
                       if($identity["identity_id"]==$host_identity_id)
                            $isHost=TRUE;

                       if($identity["provider"]=="iOSAPN")
                            $msgbodyobj["external_identity"]=$identity["external_identity"];
                }
                if($msgbodyobj["external_identity"]!="")
                {
                    //generate push msg
                    $by_identity_name=$args["by_identity"]["name"];
                    if($by_identity_name=="")
                        $by_identity_name=$args["by_identity"]["external_identity"];

                    $title=replacemarks($args["title"]);

                    $begin_at=$args["begin_at"];
                    $time_type=$args["time_type"];
                    $datetimestr="";
                    if($begin_at=="0000-00-00 00:00:00") // hasn't datetime
                       $datetimestr="";
                    else
                    {
                        if(intval($time_type)==2)
                            $datetimestr="on ".date("M j",strtotime($begin_at));
                        else
                            $datetimestr="at ".date("g:iA D,M j",strtotime($begin_at));
                    }
                    if($isHost==FALSE)
                    {
                        $msgdefaultlen=strlen(" is inviting you for \\\"\\\" ");
                        $msglen=max_msg_len- strlen($by_identity_name) - $msgdefaultlen- strlen($datetimestr);
                        if(strlen($title)>$msglen)
                            $title=utf8substr($title,0,$msglen)."...";
                        $msg="$by_identity_name is inviting you for \\\"$title\\\" ".$datetimestr;
                    }
                    else
                    {
                        $msgdefaultlen=strlen("Gathering the X \\\"\\\" ");
                        $msglen=max_msg_len- $msgdefaultlen- strlen($datetimestr);
                        if(strlen($title)>$msglen)
                        {
                            print "===cut:$msglen";
                            $title=utf8substr($title,0,$msglen)."...";
                        }
                        $msg="Gathering the X \\\"$title\\\" ".$datetimestr;
                    }

                    $msgbodyobj["msg"]=$msg;
                    $msgbodyobj["cross_id"]=$args["cross_id"];
                    
                    $this->deliver($msgbodyobj);
                }

            }
    }
    public function deliver($msgbodyobj)
    {
               date_default_timezone_set('GMT');
               Resque::setBackend(RESQUE_SERVER);
               $jobId = Resque::enqueue("iOSPushMsg","iospush_job" , $msgbodyobj, true);
               echo "throw to pushmsg queue jobid:".$jobId." \r\n";
    }
    public function send($deviceToken,$message,$sound,$badge,$args)
    {
        global $apn_connect;
        global $connect_count;
        //["$apn_connect"]
        $body = array();
        $body['aps'] = array('alert' => $message);
        if ($badge)
          $body['aps']['badge'] = $badge;
        if ($sound)
          $body['aps']['sound'] = $sound;
        $body['args']=$args;

        $err=sendapn($deviceToken,$body);
        if($err==0)
        {
            apn_connect();
            $err=sendapn($deviceToken,$body);
            if($err>0)
                $connect_count["apn_connect"]=time();
        }
    }
}

function replacemarks($str)
{
    $str=str_replace('"','\"',$str);
    return str_replace('&quot;','\"',$str);


}
function utf8substr($str,$start,$limit)
{
    $len=0;
    $substr="";
    for ($i=0;$i<mb_strlen($str);$i++)
    {
        if($i>=$start)
        {
            $char=mb_substr($str,$i,1);

            $len=$len+strlen($char);
            if($len<=$limit)
                $substr=$substr.$char;
            else
                return $substr;
        }
    }
    return $substr;
}
?>
