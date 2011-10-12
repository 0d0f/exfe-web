<?php
require_once("../lib/class.phpmailer.php");
require_once("../common.php");
require_once("../config.php");

class Templatemail_Job
{
    public function perform()
    {
        global $site_url;
        global $email_connect;
        $args=$this->args;
        

        if($email_connect=="")
            smtp_connect();
        $mail=$this->getMailWithTemplate($args,$args["template_name"]);
        $this->send($mail["title"],$mail["body"],$this->args);
    
    
    }
    public function getMailWithTemplate($mail,$templatename)
    {
        print $templatename;

        $title=$mail["title"];
        $exfee_name=$mail["exfee_name"];
        $action=$mail["action"];
        $content=$mail["content"];
        $link=$mail["link"];
        $command=$mail["command"];

        //$templatename="conversation";
        $template=file_get_contents($templatename."_template.html");
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

        return array("title"=>$mail_title,"body"=>$mail_body);
    }
    public function send($title,$body,$args)
    {
            global $email_connect;
            global $connect_count;

            cleanMailer();
    	    $email_connect->Body = $body;
    	    $email_connect->Subject = $title;
    	    $email_connect->AddAddress($args['external_identity']);  // This is where you put the email adress of the person you want to mail

            //print_r($email_connect);
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
