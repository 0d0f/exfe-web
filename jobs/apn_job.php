<?php
require_once("../config.php");
require_once("connect.php");

define("max_msg_len","112");
mb_internal_encoding("UTF-8");
class Apn_Job
{
    public function multi_perform($args)
    {
        $change_objects=array();
        $rsvp_objects=array();
        foreach($args as $arg)
        {
            if($arg["job_type"]=="invitation")
            {
                $this->generateInvitationPush($arg);
            }
            else if($arg["job_type"]=="conversation")
            {
                $this->generateConversationPush($arg);
            }
            //build a msg body, throw into iOSpush queue
            else if ($arg["job_type"]=="crossupdate")
            {
                //push obj into array, wait for mergin/process/throw
                $cross_id=$arg["id"];
                $timestamp=$arg["timestamp"];
                $old_obj=$change_objects["id".$arg["id"]];
                if($old_obj)
                {
                    if(intval($old_obj["timestamp"])<intval($arg["timestamp"]))
                    {
                        //do mergin
                        foreach($arg["changed"] as $k=>$v)
                        {
                            $change_objects["id".$arg["id"]]["changed"][$k]=$v;
                        }
                        $change_objects["id".$arg["id"]]["timestamp"]=$arg["timestamp"];
                    }
                }
                else
                {
                        $change_objects["id".$arg["id"]]=$arg;
                }
            }
            else if($arg["job_type"]=="rsvp")
            {
                $timestamp=$arg["timestamp"];
                $cross_id=$arg["cross_id"];
                #if($rsvp_objects["id".$cross_id]!="")
                #{
                    $old_rsvpobj=$rsvp_objects["id".$cross_id];
                    if($old_rsvpobj["timestamp"]<$arg["timestamp"])
                    {
                        $rsvp_objects["id".$cross_id]["cross_id"]=$arg["cross_id"];
                        $rsvp_objects["id".$cross_id]["cross_title"]=$arg["cross_title"];
                        $rsvp_objects["id".$cross_id]["timestamp"]=$arg["timestamp"];
                        if($arg["invitation"]!="")
                        {
                            $invitation_id=$arg["invitation"]["invitation_id"];
                            $new_rsvpobj["invitation_id"]=$arg["invitation"]["invitation_id"];
                            $new_rsvpobj["state"]=$arg["invitation"]["state"];
                            $new_rsvpobj["by_identity_id"]=$arg["invitation"]["by_identity_id"];
                            $new_rsvpobj["identity_id"]=$arg["invitation"]["identity_id"];
                            $new_rsvpobj["provider"]=$arg["invitation"]["provider"];
                            $new_rsvpobj["external_identity"]=$arg["invitation"]["external_identity"];
                            $new_rsvpobj["name"]=$arg["invitation"]["name"];
                            //mergin invitation
                            $rsvp_objects["id".$cross_id]["invitations"]["invitation_$invitation_id"]=$new_rsvpobj;
                            //mergin to_identities
                            if($rsvp_objects["id".$cross_id]["to_identities"]=="")
                                $rsvp_objects["id".$cross_id]["to_identities"]=$arg["to_identities"];
                        }
                        else if($arg["invitations"]!="")
                        {
                            foreach($arg["invitations"] as $k => $v)
                                $rsvp_objects["id".$cross_id]["invitations"][$k]=$v;
                            if($rsvp_objects["id".$cross_id]["to_identities"]=="")
                                $rsvp_objects["id".$cross_id]["to_identities"]=$arg["to_identities"];
                        }
                    }
                    else
                    {

                            foreach($arg["invitations"] as $k => $v)
                            {
                                if($rsvp_objects["id".$cross_id]["invitations"][$k]=="")
                                    $rsvp_objects["id".$cross_id]["invitations"][$k]=$v;
                            }
                    }
                #}
            }
        }
        if(sizeof($change_objects)>0)
        {
            foreach($change_objects as $change_object)
            {
                if((time()-$change_object["timestamp"])>1*60)
                {
                    print "process ====\r\n";
                    $this->generateCrossUpdatePush($change_object);
                }
                else
                {
                   print "throw====\r\n";
                   date_default_timezone_set('GMT');
                   Resque::setBackend(RESQUE_SERVER);
                   $change_object["queue_name"]="iOSAPN";
                   $change_object["jobclass_name"]="apn_job";
                   $jobId = Resque::enqueue("waitingqueue","waiting_job" , $change_object, true);
                   echo "throw to waiting queue jobid:".$jobId." \r\n";
                }
            }
            //do mergin, then if old than 5min process,if not throw into wait queue
        }
        if(sizeof($rsvp_objects)>0)
        {
            foreach($rsvp_objects as $rsvp_object)
            {
                    if((time()-$rsvp_object["timestamp"])>1*60)
                    {
                        print "process invitation====\r\n";
                        print_r($rsvp_object);
                        #$this->generateCrossUpdatePush($change_object);
                    }
                    else
                    {
                       date_default_timezone_set('GMT');
                       Resque::setBackend(RESQUE_SERVER);
                       $rsvp_object["queue_name"]="iOSAPN";
                       $rsvp_object["jobclass_name"]="apn_job";
                       $rsvp_object["job_type"]="rsvp";
                       $jobId = Resque::enqueue("waitingqueue","waiting_job" , $rsvp_object, true);
                       echo "throw to waiting queue jobid:".$jobId." \r\n";
                    }
            }
        }
    }
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


    }

    public function generateCrossUpdatePush($args)
    {
        #"%X_OLDTITLE" updates: Title is changed to "%X_TITLE". New time: %X_SHORTTIME. New Place: %X_PLACETITLE, %X_PLACEDESCRIPTION
        $title=$args["title"];

        #if($args["cross"]["identities"]=="")
        #{
            $obj["identity_id"] ="24";
            $obj["status"] ="3";
            $obj["provider"] = "iOSAPN";
            $obj["external_identity"] = "96da067d5b5fba84c032b12fa5667b19acd47d8fb383784ae2a4dd4904fb8858";
            $args["cross"]["identities"][0]=$obj;
        #}
//        $change_str="";
        $changemsgs=array();
        foreach($args["changed"] as $k=>$v)
        {
            if($k=="title")
            {
                $change_str.="Title is changed to \\\"$v\\\"";
                $changemsgs["title"]=$v;
            }
            else if($k=="begin_at")
            {
                $time_type=$args["changed"]["time_type"];
                $begin_at=$v;
                $datetimestr="";
                if($begin_at=="0000-00-00 00:00:00") // hasn't datetime
                   $datetimestr="";
                else
                {
                    if(intval($time_type)==2)
                        $datetimestr=date("M j",strtotime($begin_at));
                    else
                        $datetimestr=date("g:iA D,M j",strtotime($begin_at));
                }


                if($datetimestr!="")
                    $changemsgs["time"]=$datetimestr;
            }
            else if($k=="place_line1" )
                    $changemsgs["place_line1"]=$v;
            else if($k=="place_line2" )
                    $changemsgs["place_line2"]=$v;

        }
        if(sizeof($changemsgs)>0)
        {
            $updatestr="$title updates:";
            if($changemsgs["title"]!="")
                $updatestr.=" Title is changed to \\\"".$changemsgs["title"]."\\\".";
            if($changemsgs["time"]!="")
                $updatestr.=" New time: ".$changemsgs["time"].".";
            if($changemsgs["place_line1"]!="" || $changemsgs["place_line2"]!="" )
            {
                if($changemsgs["place_line1"]!="" && $changemsgs["place_line2"]!="")
                $updatestr.=" New Place: ".$changemsgs["place_line1"].", ".$changemsgs["place_line2"];
            }
            $updatestr=utf8substr($updatestr,0,max_msg_len)."...";

            $msgbodyobj=array();
            $msgbodyobj["msg"]=$updatestr;
            $msgbodyobj["cross_id"]=$args["id"];
    
            $to_identities=$args["cross"]["identities"];
            foreach($to_identities as $to_identity)
            {
               if( $to_identity["provider"]=="iOSAPN")
               {
                   $msgbodyobj["external_identity"]=$to_identity["external_identity"];
                   print_r($msgbodyobj);
                   $this->deliver($msgbodyobj);
               }
            }
        }

                #"%X_OLDTITLE" updates: Title is changed to "%X_TITLE". New time: %X_SHORTTIME. New Place: %X_PLACETITLE, %X_PLACEDESCRIPTION
        


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
