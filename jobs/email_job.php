<?php
require("../lib/class.phpmailer.php");
require("../common.php");
require("../config.php");

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
            $this->connect();
        $this->send($title,$body,$icsstr,$this->args);
    
    
    }
    public function connect()
    {
        global $email_connect;
        if($email_connect=="")
        {
            print "init email\r\n";
	        $email_connect= new PHPMailer();
	        $email_connect->CharSet = 'UTF-8';
    	    $email_connect->IsSMTP();
	        $email_connect->IsHTML(true);
    	    $email_connect->Host = 'ssl://smtp.gmail.com:465';
    	    $email_connect->SMTPAuth = TRUE;
    	    $email_connect->Username = '0d0fnofity@gmail.com';  // Change this to your gmail adress
    	    $email_connect->Password = 'alter8!chill';  // Change this to your gmail password
    	    $email_connect->From = '0d0fnofity@gmail.com';  // This HAVE TO be your gmail adress
    	    $email_connect->FromName = '0d0fnofity.com'; // This is the from name in the email, you can put anything you like here
            $email_connect->SMTPKeepAlive = true;
        }
    }
    public function send($title,$body,$attachment,$args)
    {
            global $email_connect;
            global $connect_count;

    	    $email_connect->Body = $body;
    	    $email_connect->Subject = $title;
    	    $email_connect->AddAddress($args['external_identity']);  // This is where you put the email adress of the person you want to mail
            $email_connect->AddStringAttachment($attachment, "exfe_".$args['cross_id_base62'].".ics");
            $email_connect->ContentType="text/calendar";
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
