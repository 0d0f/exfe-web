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

        $mails=$this->getMailBodyWithMultiObjects($args);
        if($mails)
        {
            if($email_connect=="")
                smtp_connect();

            foreach($mails as $mail)
                $this->send($mail["title"],$mail["body"],$mail["to"]);
        }


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
    public function getMailBodyWithMultiObjects($args)
    {
        #$to_identities=$args["to_identities"];
        $mails=array();
        $pargs=array();
        #$external_identity_list=array();
        foreach($args as $arg)
        {
            $key="id_".$arg["cross_id"];
            if($pargs[$key]=="")
                $pargs[$key]=array();
            array_push($pargs[$key],$arg);
        #    $external_identity_list[$arg["external_identity"]]=1;
        }
        foreach($pargs as $k=>$posts)
        {
            $identity_posts=array();
            foreach($posts as $post)
            {
                $to_identities=$post["to_identities"];
                foreach($to_identities as $to_identity)
                {
                    $identity_key="identity_".$to_identity["identity_id"];
                    if($identity_posts[$identity_key]=="")
                        $identity_posts[$identity_key]["posts"]=array();
                    unset($post["to_identities"]);
                    array_push($identity_posts[$identity_key]["posts"],$post);
                    if($identity_posts[$identity_key]["to_identity"]=="")
                        $identity_posts[$identity_key]["to_identity"]=$to_identity;
                }
            }
        }
        if($identity_posts)
        {
            foreach($identity_posts as $key=>$identity_post)
            {
                $mail=array();
                $posts=$identity_post["posts"];
                $html="";
                $title="";
                foreach($posts as $post)
                {
                    if($post["identity"]["external_identity"]!=$external_identity)
                    {
                        $title=$post["title"];
                        $avatar_file_name=$post["identity"]["avatar_file_name"];
                        $name=$post["identity"]["name"];
                        $content=$post["content"];
                        $create_at=humanDateTime($post["create_at"]);
                        $html.="<li><img src='$avatar_file_name' />$content<br/>$name at $create_at</li>";
                    }
                }
                $to_identity=$identity_post["to_identity"];

                $mail["body"]=$html;
                $mail["title"]=$title;
                $mail["to"]=$to_identity["external_identity"];
                array_push($mails,$mail);
            }
        }
        return $mails;
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
    public function send($title,$body,$to)
    {
            global $email_connect;
            global $connect_count;

            $mail_mime = new Mail_mime(array('eol' => "\n"));
            $mail_mime->setHTMLBody($body);
            #$mail_mime->addAttachment($attachment , "text/calendar","x_".$args['cross_id_base62'].".ics",false);

            $body = $mail_mime->get();
            $headers = $mail_mime->txtHeaders(array('From' => 'x@exfe.com', 'Subject' => "$title"));
            
            $message = $headers . "\r\n" . $body;

            $r = $email_connect->send_raw_email(array('Data' => base64_encode($message)), array('Destinations' => $to));
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
