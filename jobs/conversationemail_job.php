<?php
require_once("../lib/class.phpmailer.php");
require_once("../common.php");
require_once("../config.php");

class Conversationemail_Job
{
    public function multi_perform($args)
    {
        global $site_url;
        global $img_url;
        global $email_connect;

        $mails=$this->getMailBodyWithMultiObjects($args);
        if($mails)
        {
            if($email_connect=="")
                smtp_connect();

            foreach($mails as $mail)
            {
                $this->send($mail["title"],$mail["body"],$mail["to"],$mail["cross_id_base62"]);
            }
        }


    }
    public function perform()
    {
    #    global $site_url;
    #    global $email_connect;
    #    $args=$this->args;

    #    if($email_connect=="")
    #        smtp_connect();
    #    $mail=$this->getMailWithTemplate($args);
    #    $this->send($mail["title"],$mail["body"],$this->args);
    #
    #
    }
    public function getMailBodyWithMultiObjects($args)
    {
        global $site_url;
        global $img_url;
        $template=file_get_contents("conversation_template.html");
        $templates=split("\r|\n",$template);
        $template_title=$templates[0];
        unset($templates[0]);
        $template_body=implode($templates);

        $mails=array();
        $pargs=array();
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
                $name="";
                $mutelink="";
                $link="";
                $cross_id_base62="";
                foreach($posts as $post)
                {
                    if($post["identity"]["external_identity"]!=$external_identity)
                    {
                        $title=$post["title"];
                        $avatar_file_name=$post["identity"]["avatar_file_name"];
                        $name=$post["identity"]["name"];
                        $content=$post["content"];
                        $mutelink=$post["mutelink"];
                        $link=$post["link"];
                        $create_at=humanDateTime($post["create_at"]);
                        $avartar=$img_url."/".getHashFilePath("",$avatar_file_name)."/80_80_".$avatar_file_name;
                  //      $html.="<tr> <td valign='top' width='50' height='60' align='left'> <img  class='exfe_mail_avatar' src='".$avartar."'> </td> <td valign='top'> <span class='exfe_mail_message'>$content</span> <br> <span class='exfe_mail_identity_name'>$name</span> <span class='exfe_mail_msg_at'>at</span> <span class='exfe_mail_msg_time'>$create_at</span> </td> </tr>";
                        $html.="<tr> <td valign='top' width='50' height='60' align='left'> <img width='40' height='40' src='$avartar'> </td> <td valign='top'> <span class='exfe_mail_message'>$content</span> <br> <span class='exfe_mail_identity_name'>$name</span> <span class='exfe_mail_msg_at'>at</span> <span class='exfe_mail_msg_time'>$create_at</span> </td> </tr>";
                        $cross_id_base62=$post["cross_id_base62"];
                    }
                }
                $to_identity=$identity_post["to_identity"];

                $mail_body=str_replace("%conversations%",$html,$template_body);
                $mail_body=str_replace("%host_name%",$name,$mail_body);
                $mail_body=str_replace("%exfe_title%",$title,$mail_body);
                $mail_body=str_replace("%mutelink%",$mutelink,$mail_body);
                $mail_body=str_replace("%link%",$link,$mail_body);

                $mail["body"]=$mail_body;
                $mail["title"]=str_replace("%exfe_title%",$title,$template_title);
                $mail["to"]=$to_identity["external_identity"];
                $mail["cross_id_base62"]=$cross_id_base62;
                array_push($mails,$mail);
            }
        }
        return $mails;
    }

    public function send($title,$body,$to,$cross_id_base62)
    {
            global $email_connect;
            global $connect_count;

            $mail_mime = new Mail_mime(array('eol' => "\n"));
            $mail_mime->setHTMLBody($body);
            #$mail_mime->addAttachment($attachment , "text/calendar","x_".$args['cross_id_base62'].".ics",false);

            $body = $mail_mime->get();
            $headers = $mail_mime->txtHeaders(array('From' => 'x@exfe.com','Reply-To'=>'x+'.$cross_id_base62.'@exfe.com', 'Subject' => "$title"));
            
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
