<?php
require_once("../lib/class.phpmailer.php");
require_once("../common.php");
require_once("../config.php");

class Email_Job
{
    public function perform()
    {
        $title="来自 Exfe 的活动邀请：".$this->args['title'];
        $name=$this->args['name'];
        if($this->args['name']=="")
        $name=$this->args['external_identity'];
    
        global $site_url;
        global $email_connect;
        
        $link='<a href="'.$site_url.'/!'.$this->args['cross_id_base62'].'?token='.$this->args['token'].'">'.$this->args['title']."</a>";
        $body=$name." 在 Exfe 上邀请你参加活动 " .$link."，这个活动的详细情况如下：\r\n";
        $body=$body.$this->args['description'];
        $icsstr=buildICS($this->args);

        if($email_connect=="")
            smtp_connect();
        $this->send($title,$body,$icsstr,$this->args);
    
    
    }

    public function send($title,$body,$attachment,$args)
    {
            global $email_connect;
            global $connect_count;

            cleanMailer();
    	    $email_connect->Body = $body;
    	    $email_connect->Subject = $title;
    	    $email_connect->AddAddress($args['external_identity']);  // This is where you put the email adress of the person you want to mail
            $email_connect->AddStringAttachment($attachment, "exfe_".$args['cross_id_base62'].".ics",'base64',"text/calendar");
            //print_r("email_job");
            //print_r($email_connect);

    	    if(!$email_connect->Send())
    	    {
    	        echo "Message was not sent<br/ >";
    	        echo "Mailer Error: " . $email_connect->ErrorInfo;
    	    }
    	    else
    	    {
                $connect_count["email_connect"]=time();
    	        echo "Message has been sent";
    	    }
    }
}
?>
