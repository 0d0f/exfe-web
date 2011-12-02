<?php
require_once("../lib/class.phpmailer.php");
require_once("../common.php");
require_once("../config.php");
class Welcomeandactivecode_Job
{
    public function perform()
    {
        global $email_connect;
        global $site_url;

        $title="欢迎并激活EXFE帐号";
        $name=$this->args['name'];
        $identity_id=$this->args['identityid'];
        $avatar_file_name=$this->args['avatar_file_name'];
        $activecode=$this->args['activecode'];


        $url=$site_url.'/s/active?id='.$identity_id.'&activecode='.$activecode;
        $parturl=substr($url,0,45)."...";
        $mail["link"]=$url;
        $mail["partlink"]=$parturl;
        $mail["name"]=$name;
        $mail["external_identity"]=$this->args['external_identity'];

        $body=$this->getMailBody($mail);

        if($email_connect=="")
            smtp_connect();
        $this->send($body["title"],$body["body"],$icsstr,$this->args);
    }
    public function getMailBody($mail)
    {
        global $site_url;
        $template=file_get_contents("welcome_template.html");
        $templates=split("\r|\n",$template);
        $template_title=$templates[0];
        unset($templates[0]);
        $template_body=implode($templates);
        $mail_title=str_replace("%exfe_title%",$mail["exfe_title"],$template_title);

        $mail_body=str_replace("%title%", 'Welcome to EXFE!', $template_body);

        $mail_body=str_replace("%name%",$mail["name"],$template_body);

        $mail_body=str_replace(
            "%link%",
            '<p>'
          .     '<span style="font-size:14px; color:#333;">Please click here to verify your identity:</span>'
          .     "<a style=\"color:#191919; text-decoration: underline;\" href=\"{$mail["link"]}\">{$mail["partlink"]}</a>"
          . '</p>',
            $mail_body
        );

        $mail_body=str_replace("%external_identity%",$mail["external_identity"],$mail_body);
        $mail_body=str_replace("%site_url%",$site_url,$mail_body);

        return array("title"=>$mail_title,"body"=>$mail_body);
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

    }
}
?>
