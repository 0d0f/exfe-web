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

            $mail_mime = new Mail_mime(array('eol' => "\n"));
            $mail_mime->setHTMLBody($body);
            $mail_mime->addAttachment($attachment , "text/calendar","x_".$args['cross_id_base62'].".ics",false);

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
    }
}
?>
