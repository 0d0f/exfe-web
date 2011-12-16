<?php
require_once("../lib/class.phpmailer.php");
require_once("../common.php");
require_once("../config.php");

class Changeemail_Job
{
    public function multi_perform($args)
    {
        $cross_changed=array();
        foreach($args as $arg)
        {
            if($arg["action"]=="changed" && $arg["changed"]!="")
            {
                if($cross_changed[$arg["id"]]=="")
                    $cross_changed[$arg["id"]]=$arg;
                 else //do mergin
                 {
                    foreach($arg["changed"] as $k=>$v)
                        $cross_changed[$arg["id"]]["changed"][$k]=$v;

                    unset($cross_changed[$arg["id"]]["cross"]);
                    $cross_changed[$arg["id"]]["cross"]=$arg["cross"];
                    $cross_changed[$arg["id"]]["timestamp"]=$arg["timestamp"];
                    foreach($arg["action_identity"] as $identity)
                    {
                        $identity_size=sizeof($cross_changed[$arg["id"]]["action_identity"]);
                        $flag=false;
                        for($idx=0;$idx<$identity_size;$idx++)
                        {
                            if($cross_changed[$arg["id"]]["action_identity"][$idx]["id"]==$identity["id"])
                            {
                                $flag=true;
                                break;
                            }
                        }
                        if($flag==false)
                            array_push($cross_changed[$arg["id"]]["action_identity"],$identity);
                    }
                 }
            }
            if($arg["action"]=="changed" && $arg["identities"]!="")
            {
                if($cross_changed[$arg["id"]]=="")
                    $cross_changed[$arg["id"]]=$arg;
                 else //do mergin
                 {
                    foreach($arg["identities"]["newexfees"] as $identity)
                    {
                        $newexfee_len=sizeof($cross_changed[$arg["id"]]["identities"]["newexfees"]);
                        if($newexfee_len==0)
                                $cross_changed[$arg["id"]]["identities"]["newexfees"]=array(0=>$identity);

                        else
                        {

                            $flag=false;
                            for($idx=0; $idx<$newexfee_len;$idx++)
                                if($cross_changed[$arg["id"]]["identities"]["newexfees"][$idx]["id"]==$identity["id"])
                                {
                                    $flag=true;
                                    break;
                                }

                            if($flag==false)
                                array_push($cross_changed[$arg["id"]]["identities"]["newexfees"],$identity);
                        }
                    }
                     #foreach($arg["identities"]["delexfees"])
                     #{

                     #}
                 }
            }
        }


        foreach($cross_changed as $cross_id=>$changed_data)
        {

            if((time()-$changed_data["timestamp"])>1*60)
            {
               print "process object ========\r\n";
               print_r($changed_data);
            }
            else
            {
               date_default_timezone_set('GMT');
               Resque::setBackend(RESQUE_SERVER);
               $changed_data["queue_name"]="changeemail";
               $changed_data["jobclass_name"]="changeemail_job";
               $jobId = Resque::enqueue("waitingqueue","waiting_job" , $changed_data, true);
               echo "throw to waiting queue jobid:".$jobId." \r\n";
                //throw $changed_data back to resque
            }
        }


    }
    public function perform()
    {
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
                        $avartar=getUserAvatar($avatar_file_name, 80);
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
