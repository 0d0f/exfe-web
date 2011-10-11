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
//            $this->connect();
        $this->send($title,$body,$icsstr,$this->args);
    
    
    }
#    public function connect()
#    {
#        global $email_connect;
#        if($email_connect=="")
#        {
#            print "init email\r\n";
#	        $email_connect= new PHPMailer();
#	        $email_connect->CharSet = 'UTF-8';
#    	    $email_connect->IsSMTP();
#	        $email_connect->IsHTML(true);
#    	    $email_connect->Host = 'ssl://smtp.gmail.com:465';
#    	    $email_connect->SMTPAuth = TRUE;
#    	    $email_connect->Username = '0d0fnofity@gmail.com';  // Change this to your gmail adress
#    	    $email_connect->Password = 'alter8!chill';  // Change this to your gmail password
#    	    $email_connect->From = '0d0fnofity@gmail.com';  // This HAVE TO be your gmail adress
#    	    $email_connect->FromName = '0d0fnofity.com'; // This is the from name in the email, you can put anything you like here
#            $email_connect->SMTPKeepAlive = true;
#        }
#    }
    public function send($title,$body,$attachment,$args)
    {
            global $email_connect;
            global $connect_count;

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
