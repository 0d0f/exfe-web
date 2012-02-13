<?php
require_once("../lib/class.phpmailer.php");
require_once("../common.php");
require_once("../config.php");
class Emailverifying_Job
{
    public function perform()
    {
        global $email_connect;
        global $site_url;

        $title="EXFE identity verification";
        $name=$this->args['name'];
        $user_name = trim($this->args['user_name']);
        if($user_name != ""){
            $user_name = "by <span style='color:#191919;'>".$user_name."</span>";
        }
        $avatar_file_name=$this->args['avatar_file_name'];
        $token=$this->args['token'];

        $url=$site_url.'/s/verifyIdentity?token='.$token;
        $report_spam_url = $site_url.'/s/reportSpam?token='.$token;
        
        $parturl = preg_replace('/^http[s]*:\/\//i', '', $url);
        $parturl = substr($parturl, 0, 31) . '…' . substr($parturl, -8);
        
        $mail["link"]=$url;
        $mail["report_spam_url"] = $report_spam_url;
        $mail["site_url"] = $site_url;
        $mail["partlink"]=$parturl;
        $mail["name"]=$name;
        $mail["user_name"]=$user_name;
        $mail["external_identity"]=$this->args['external_identity'];

        $mail_body=$this->getMailBody($mail);

        if($email_connect==""){
            smtp_connect();
        }
        $this->send($title,$mail_body,$this->args);
    }
    public function getMailBody($mail)
    {
        $template_con = file_get_contents("verifying_template.html");
        $mail_body=str_replace("%name%",$mail["name"],$template_con);
        $mail_body=str_replace("%link%",$mail["link"],$mail_body);
        $mail_body=str_replace("%partlink%",$mail["partlink"],$mail_body);
        $mail_body=str_replace("%report_spam_url%",$mail["report_spam_url"],$mail_body);
        $mail_body=str_replace("%external_identity%",$mail["external_identity"],$mail_body);
        $mail_body=str_replace("%user_name%",$mail["user_name"],$mail_body);
        $mail_body=str_replace("%site_url%",$mail["site_url"],$mail_body);

        return $mail_body;
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
