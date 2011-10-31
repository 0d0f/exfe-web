<?
require_once("../lib/class.phpmailer.php");
require_once("../common.php");
require_once("../config.php");
class Emailresetpassword_Job
{
    public function perform()
    {
        $title="reset password";
        $external_identity=$this->args['external_identity'];
        $token=$this->args['token'];

        global $email_connect;
        global $site_url;
        $url=$site_url.'/s/resetpassword?token='.$token;

        #$url=$site_url.'/s/active?id='.$identity_id.'&activecode='.$activecode;
        #$link='<a href="'.$url.'">'.$url."</a>";
        #$body=$name." 激活帐号：" .$link."\r\n";

        $url=$site_url.'/s/resetpassword?token='.$token;
        $parturl=substr($url,0,45)."...";
        $mail["link"]=$url;
        $mail["partlink"]=$parturl;
        $mail["name"]=$this->args['name'];
        $mail["external_identity"]=$this->args['external_identity'];
        $mail["title"]=$title;
        $body=$this->getMailBody($mail);
       
        if($email_connect=="")
            smtp_connect();
        $this->send($body["title"],$body["body"],$this->args);
    }
    public function getMailBody($mail)
    {
        global $site_url;
        $template=file_get_contents("resetpassword_template.html");
        $templates=split("\r|\n",$template);
        $template_title=$templates[0];
        unset($templates[0]);
        $template_body=implode($templates);
        $mail_title=str_replace("%title%",$mail["title"],$template_title);

        $mail_body=str_replace("%name%",$mail["name"],$template_body);
        $mail_body=str_replace("%link%",$mail["link"],$mail_body);
        $mail_body=str_replace("%partlink%",$mail["partlink"],$mail_body);
        $mail_body=str_replace("%external_identity%",$mail["external_identity"],$mail_body);
        $mail_body=str_replace("%site_url%",$site_url,$mail_body);

        return array("title"=>$mail_title,"body"=>$mail_body);
    }

    public function send($title,$body,$args)
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

    }
}
?>

