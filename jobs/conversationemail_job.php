<?php
require_once("../lib/class.phpmailer.php");
require_once("../common.php");
require_once("../config.php");

class Conversationemail_Job
{
    public function multi_perform($args)
    {
        global $site_url;
        global $email_connect;
        print "in Conversationemail_Job class:\r\n";
        print_r($args);
    }
    public function perform()
    {
        global $site_url;
        global $email_connect;
        $args=$this->args;
        

        if($email_connect=="")
            smtp_connect();
        $mail=$this->getMailWithTemplate($args);
        $this->send($mail["title"],$mail["body"],$this->args);
    
    
    }
    public function getMailWithTemplate($mail)
    {
        $title=$mail["title"];
        $exfee_name=$mail["exfee_name"];
        $action=$mail["action"];
        $content=$mail["content"];
        $link=$mail["link"];
        $mutelink=$mail["mutelink"];
        $command=$mail["command"];

        //$templatename="conversation";
        $template=file_get_contents("conversation_template.html");
        $templates=split("\r|\n",$template);
        $template_title=$templates[0];
        unset($templates[0]);
        $template_body=implode($templates);
        $mail_title=str_replace("%exfe_title%",$title,$template_title);

        $mail_body=str_replace("%exfe_title%",$title,$template_body);
        $mail_body=str_replace("%exfee_name%",$exfee_name,$mail_body);
        $mail_body=str_replace("%action%",$action,$mail_body);
        $mail_body=str_replace("%content%",$content,$mail_body);
        $mail_body=str_replace("%link%",$link,$mail_body);
        $mail_body=str_replace("%mutelink%",$mutelink,$mail_body);

        return array("title"=>$mail_title,"body"=>$mail_body);
    }
    public function send($title,$body,$args)
    {
            global $email_connect;
            global $connect_count;

            $mail_mime = new Mail_mime(array('eol' => "\n"));
            $mail_mime->setHTMLBody($body);
            #$mail_mime->addAttachment($attachment , "text/calendar","x_".$args['cross_id_base62'].".ics",false);

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
