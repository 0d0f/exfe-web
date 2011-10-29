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

        global $email_connect;

        #$url=$site_url.'/s/active?id='.$identity_id.'&activecode='.$activecode;
        #$link='<a href="'.$url.'">'.$url."</a>";
        #$body=$name." 激活帐号：" .$link."\r\n";


        $body=$this->getMailBody($mail);

        if($email_connect=="")
            smtp_connect();
        $this->send($body["title"],$body["body"],$icsstr,$this->args);
    }

    public function getMailBody($mail)
    {
        global $site_url;
        $template=file_get_contents("activecode_template.html");
        $templates=split("\r|\n",$template);
        $template_title=$templates[0];
        unset($templates[0]);
        $template_body=implode($templates);
        $mail_title=str_replace("%exfe_title%",$mail["exfe_title"],$template_title);

        $mail_body=str_replace("%exfe_title%",$mail["exfe_title"],$template_body);
        $mail_body=str_replace("%exfee_name%",$mail["exfee_name"],$mail_body);
        $mail_body=str_replace("%hint_title%",$mail["hint_title"],$mail_body);
        $mail_body=str_replace("%host_name%",$mail["host_name"],$mail_body);
        $mail_body=str_replace("%rsvp_status%",$mail["rsvp_status"],$mail_body);
        $mail_body=str_replace("%exfe_link%",$mail["exfe_link"],$mail_body);
        $mail_body=str_replace("%host_avatar%",$mail["host_avatar"],$mail_body);
        $mail_body=str_replace("%exfee_list%",$mail["exfee_list"],$mail_body);
        $mail_body=str_replace("%content%",$mail["content"],$mail_body);
        $mail_body=str_replace("%date%",$mail["date"],$mail_body);
        $mail_body=str_replace("%time%",$mail["time"],$mail_body);
        $mail_body=str_replace("%place_line1%",$mail["place_line1"],$mail_body);
        $mail_body=str_replace("%place_line2%",$mail["place_line2"],$mail_body);
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
