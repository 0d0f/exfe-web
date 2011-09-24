<?php
require("../lib/class.phpmailer.php");
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
	    
	    $link='<a href="'.$site_url.'/!'.$this->args['cross_id_base62'].'?token='.$this->args['token'].'">'.$this->args['title']."</a>";
	    $body=$name." 在 Exfe 上邀请你参加活动 " .$link."，这个活动的详细情况如下：\r\n";
	    $body=$body.$this->args['description'];

	    $mailer = new PHPMailer();
	    $mailer->CharSet = 'UTF-8';
    	$mailer->IsSMTP();
	    $mailer->IsHTML(true);
    	    $mailer->Host = 'ssl://smtp.gmail.com:465';
    	    $mailer->SMTPAuth = TRUE;
    	    $mailer->Username = '0d0fnofity@gmail.com';  // Change this to your gmail adress
    	    $mailer->Password = 'alter8!chill';  // Change this to your gmail password
    	    $mailer->From = '0d0fnofity@gmail.com';  // This HAVE TO be your gmail adress
    	    $mailer->FromName = '0d0fnofity.com'; // This is the from name in the email, you can put anything you like here
    	    $mailer->Body = $body;
    	    $mailer->Subject = $title;
    	    $mailer->AddAddress($this->args['external_identity']);  // This is where you put the email adress of the person you want to mail
    	    if(!$mailer->Send())
    	    {
    	        echo "Message was not sent<br/ >";
    	        echo "Mailer Error: " . $mailer->ErrorInfo;
    	    }
    	    else
    	    {
    	        echo "Message has been sent";
    	    }
	
#	echo $this->args['name'];
#                sleep(600);
        }
}
?>
