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

            $mail_mime = new Mail_mime(array('eol' => "\n"));
            $mail_mime->setHTMLBody($body);

            $body = $mail_mime->get();
            $headers = $mail_mime->txtHeaders(array('From' => 'x@exfe.com', 'Subject' => "$title"));
            
            $message = $headers . "\r\n" . $body;

            $r = $email_connect->send_raw_email(array('Data' => base64_encode($message)), array('Destinations' => $args['external_identity']));

            if ($r->isOK())
            {
                print("Mail sent; message id is " . (string) $r->body->SendRawEmailResult->MessageId . "\n");
                $connect_count["email_connect"]=time();
            }
            else
            {
                  print("Mail not sent; error is " . (string) $r->body->Error->Message . "\n");
            }

    	    //$email_connect->Body = $body;
    	    //$email_connect->Subject = $title;
    	    //$email_connect->AddAddress($args['external_identity']);  // This is where you put the email adress of the person you want to mail

    	    //if(!$email_connect->Send())
    	    //{
    	    //    echo "Message was not sent<br/ >";
    	    //    echo "Mailer Error: " . $email_connect->ErrorInfo;
    	    //}
    	    //else
    	    //{
            //    $connect_count["email_connect"]=time();
    	    //    echo "Message has been sent";
    	    //}
    }
}
?>
