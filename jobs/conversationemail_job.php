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

        $conversation_args=array();
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
            else if($arg["action"]=="changed" && $arg["identities"]!="")
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
            else
            {
                $key="id_".$arg["cross_id"];
                if($conversation_args[$key]=="")
                    $conversation_args[$key]=array();
                array_push($conversation_args[$key],$arg);
            }
        #    $external_identity_list[$arg["external_identity"]]=1;
        }

        if($conversation_args)
        {
            $conversation_objects=$this->getConversationObjects($conversation_args);
            $mails=$this->getMailBodyWithMultiObjects($conversation_objects,$cross_changed);
            unset($cross_changed);
        }
        else
        {
            if($cross_changed)
            {
                $changed_objects=$this->getChangedObjects($cross_changed);
                if($changed_objects)
                {
                    $mails=$this->getMailBodyWithMultiObjects(array(),$changed_objects);
                }
            }
        }
        #print_r($mails);

        if($mails)
        {
            if($email_connect=="")
            {
                smtp_connect();
            }

            foreach($mails as $mail)
            {
                $this->send($mail["title"],$mail["body"],$mail["to"],$mail["cross_id_base62"]);
            }
        }


    }
    public function getChangedObjects($args)
    {
        $changed_objects=array();
        foreach($args as $cross_id=>$changed_data)
        {

            if((time()-$changed_data["timestamp"])>1*60)
               array_push($changed_objects,$changed_data);
            else
            {
               date_default_timezone_set('GMT');
               Resque::setBackend(RESQUE_SERVER);
               $changed_data["queue_name"]="conversationemail";
               $changed_data["jobclass_name"]="conversationemail_job";
               $jobId = Resque::enqueue("waitingqueue","waiting_job" , $changed_data, true);
               echo "throw to waiting queue jobid:".$jobId." \r\n";
                //throw $changed_data back to resque
            }
        }
        if(sizeof($changed_objects)>0)
            return $changed_objects;
        return NULL;
        //print_r($args);
    }
    public function perform()
    {
    }
    public function getConversationObjects($args)
    {
        $identity_posts=array();
        foreach($args as $k=>$posts)
        {
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
        return $identity_posts;
    }
    public function buildUpdateMailBody($changed_objects)
    {
        global $site_url;
        global $img_url;
        $update_array=array();
        if($changed_objects)
        {
            $update_part_template=file_get_contents("update_part_template.html");
            foreach($changed_objects as $changed_object)
            {
                $action_identities=$changed_object["action_identity"];
                $updated_identity="";
                foreach($action_identities as $action_identity)
                {
                    $name=$action_identity["name"];
                    if($name=="")
                        $name=$action_identity["external_identity"];

                    $updated_identity.=$name.",";
                }

                $updated_identity = rtrim($updated_identity , ",");
                $title=$changed_object["title"];
                $cross=$changed_object["cross"];

                $identities=$cross["identities"];
                $to_identities=$cross["identities"];
                $cross_id = $cross["id"];
                $cross_id_base62 = int_to_base62($cross["cross_id"]);
                $action_identities=$changed_object["action_identity"];
                $changed_fields=$changed_object["changed"];
                $mutelink=$changed_object["mutelink"];
                $exfee_avartar="";
                if($identities)
                    foreach($identities as $identity)
                    {
                        $avartar=$img_url."/".getHashFilePath("",$identity["avatar_file_name"])."/80_80_".$identity["avatar_file_name"];
                        $exfee_avartar.="<img width='40' height='40' src='$avartar'>";
                    }
                #if($mail_body=="")
                #    $mail_body=$template_body;

                #$mail_body=str_replace("%conversations%",$html,$mail_body);
                #$mail_body=str_replace("%host_name%",$name,$mail_body);

                $update_part_body=str_replace("%exfe_title%",$title,$update_part_template);
                //$update_part_body=str_replace("%content%",$cross["description"],$update_part_body);
                $update_part_body=str_replace("%content%",$changed_fields["title"],$update_part_body);
                
                $datetime=explode(" ",$cross["begin_at"]);
                $date=$datetime[0];
                $time=$datetime[1];
                $update_part_body=str_replace("%date%",$date,$update_part_body);
                $update_part_body=str_replace("%time%",$time,$update_part_body);
                $update_part_body=str_replace("%updated_identity%",$updated_identity,$update_part_body);
                $update_part_body=str_replace("%place_line1%",$cross["place_line1"],$update_part_body);
                $update_part_body=str_replace("%place_line2%",$cross["place_line2"],$update_part_body);
                $update_part_body=str_replace("%exfee_avartar%",$exfee_avartar,$update_part_body);
                $update_part_body=str_replace("%site_url%",$site_url,$update_part_body);

                #$mail_body=str_replace("%update_part%",$update_part_body,$mail_body);
                #$mail_body=str_replace("%conversation_part%","",$mail_body);
                #$mail_body=str_replace("%mutelink%",$mutelink,$mail_body);
                #$mail_body=str_replace("%link%",$link,$mail_body);
                #foreach($to_identities as $to_identity)
                #{
                #    $mail["body"]=$mail_body;
                #    $mail["title"]=str_replace("%exfe_title%",$title,$template_title);
                #    $mail["to"]=$to_identity["external_identity"];
                #    $mail["cross_id_base62"]=$cross_id_base62;
                #    array_push($mails,$mail);
                #}
                $object=array("content"=>$update_part_body,"cross_id"=>$cross_id,"to_identity"=>$to_identities);
                $update_array["id_".$cross_id]=$object;
            }
        }
        return $update_array;
    }
    public function getMailBodyWithMultiObjects($conversation_objects,$changed_objects)
    {
        global $site_url;
        global $img_url;
        $template=file_get_contents("conversation_template.html");
        $templates=split("\r|\n",$template);
        $template_title=$templates[0];
        unset($templates[0]);
        $template_body=implode($templates);

        $conversation_part_template=file_get_contents("conversation_part_template.html");

        $update_array=$this->buildUpdateMailBody($changed_objects);

        $mails=array();
        if($conversation_objects)
        {
            foreach($conversation_objects as $key=>$identity_post)
            {
                $mail=array();
                $posts=$identity_post["posts"];
                $html="";
                $title="";
                $name="";
                $mutelink="";
                $link="";
                $cross_id_base62="";
                $cross_id="";
                //if($changed_objects[""]
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
                        $html.="<tr> <td valign='top' width='50' height='60' align='left'> <img width='40' height='40' src='$avartar'> </td> <td valign='top'> <span class='exfe_mail_message'>$content</span> <br> <span class='exfe_mail_msg_identity_name'>$name</span> <span class='exfe_mail_msg_at'>at</span> <span class='exfe_mail_msg_time'>$create_at</span> </td> </tr>";
                        $cross_id_base62=$post["cross_id_base62"];
                        $cross_id=$post["cross_id"];
                    }
                }
                if($changed_objects[$cross_id]!="")
                {
                    $changed_cross=$changed_objects[$cross_id];
                }
                
                $to_identity=$identity_post["to_identity"];

                $conversation_part_body=str_replace("%conversations%",$html,$conversation_part_template);

                $mail_body=str_replace("%host_name%",$name,$template_body);
                $mail_body=str_replace("%exfe_title%",$title,$mail_body);
                $mail_body=str_replace("%mutelink%",$mutelink,$mail_body);
                $mail_body=str_replace("%link%",$link,$mail_body);
                $mail_body=str_replace("%conversation_part%",$conversation_part_body,$mail_body);

                $mail["title"]=str_replace("%exfe_title%",$title,$template_title);
                $mail["to"]=$to_identity["external_identity"];
                $mail["cross_id_base62"]=$cross_id_base62;
                $mail["cross_id"]=$cross_id;
                if($update_array["id_".$cross_id]!="")
                {
                    $change_object=$update_array["id_".$cross_id];
                    if($change_object)
                    {
                        $mail_body=str_replace("%update_part%",$change_object["content"],$mail_body);
                    }
                }
                else
                        $mail_body=str_replace("%update_part%","",$mail_body);

                $mail["body"]=$mail_body;
                array_push($mails,$mail);
            }
        }

        #print "=====mail=======\r\n";
        #print_r($mails);
        #print "=====mail=======\r\n";
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
