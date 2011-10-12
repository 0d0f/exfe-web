<?php
require_once("../lib/class.phpmailer.php");
require_once("../common.php");
require_once("../config.php");
class Emailactivecode_Job
{
    public function perform()
    {
        $title="激活 Exfe 帐号";
        $name=$this->args['name'];
        $identity_id=$this->args['identityid'];
        $avatar_file_name=$this->args['avatar_file_name'];
        $activecode=$this->args['activecode'];


        global $site_url;
        global $email_connect;
        $url=$site_url.'/s/active?id='.$identity_id.'&activecode='.$activecode;
        $link='<a href="'.$url.'">'.$url."</a>";
        $body=$name." 激活帐号：" .$link."\r\n";

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
