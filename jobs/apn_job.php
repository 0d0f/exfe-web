<?php
require_once("../config.php");
require_once("connect.php");

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
        $host_identity_id=$args["host_identity_id"];
        if($args["job_type"]=="invitation")
        {
            //find out which invitation should be push to a device, and which invitation is belongs to host and send grather success hint.
            $invitations=$args["invitations"];
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

                    $title=$args["title"];

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
                        $msg="$by_identity_name is inviting you for \"$title\" ".$datetimestr;
                    else
                        $msg="Gathering the X \"$title\" ".$datetimestr;

                    $msgbodyobj["msg"]=$msg;
                    $msgbodyobj["cross_id"]=$args["cross_id"];
                    
                    $this->deliver($msgbodyobj);
//%IDENTITY_NAME is inviting you for “%X_TITLE” at/on %X_SHORTTIME
                }

            }
        }

        //build a msg body, throw into iOSpush queue

        #$sound ="default";
        #$args = array('cross_id'=>$cross_id);
        #$deviceToken = $this->args["identity"]["external_identity"];
        #$badge=1;
        #if($apn_connect=="")
        #    apn_connect();
        #   // $this->connect();
        #$this->send($deviceToken,$message,$sound,$badge,$args);
    }
    public function deliver($msgbodyobj)
    {
               date_default_timezone_set('GMT');
               Resque::setBackend(RESQUE_SERVER);
               $changed_data["queue_name"]="iospush";
               $changed_data["jobclass_name"]="iospush_job";
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

?>
