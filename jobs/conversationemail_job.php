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
            } else if($arg["action"]=="changed" && $arg["identities"]!="")
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

        if($mails)
        {
            if($email_connect=="")
            {
                smtp_connect();
            }

            foreach($mails as $mail)
            {
                $this->send($mail["title"],$mail["body"],$mail["to"],$mail["cross_id"]);
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

                $cross_id = $changed_object["id"];
                $cross_id_base62 = int_to_base62($cross_id);

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
                $action_identities=$changed_object["action_identity"];
                $new_exfee_identities=$changed_object["identities"]["newexfees"];
                $changed_fields=$changed_object["changed"];
                $mutelink=$changed_object["mutelink"];
                $exfee_avartar="";
                if($identities)
                    foreach($identities as $identity)
                    {
                        $avartar=getUserAvatar($identity["avatar_file_name"], 80);
                        $exfee_avartar.="<img style=\"padding-right: 5px;\" width=\"40\" height=\"40\" src=\"$avartar\">";
                    }

                $new_exfee_identities_str="";
                $new_exfee_table=array();

                if($new_exfee_identities)
                    foreach($new_exfee_identities as $new_exfee_identity)
                    {
                        if($new_exfee_identity["name"]=="")
                            $new_exfee_identity["name"]=$new_exfee_identity["external_identity"];
                        $new_exfee_identities_str=$new_exfee_identities_str.'<span class="exfe_mail_identity_name">'.$new_exfee_identity["name"]."</span>,";
                        $new_exfee_table[$new_exfee_identity["external_identity"]]=1;
                    }

                $new_exfee_identities_str= rtrim($new_exfee_identities_str, ",");
                if($new_exfee_identities_str!="")
                {
                    $new_exfee_identities_str='<td colspan="5"><p style="margin: 0;">'.$new_exfee_identities_str.' are invited.'.'</p></td>';
                }

                $update_part_body=str_replace("%exfe_title%",$title,$update_part_template);
                //$update_part_body=str_replace("%content%",$cross["description"],$update_part_body);
                if($changed_fields["title"]!="")
                    $update_part_body=str_replace("%content%",$changed_fields["title"],$update_part_body);
                else
                    $update_part_body=str_replace("%content%",$title,$update_part_body);

                if(trim($changed_fields["title"])!="")
                    $update_part_body=str_replace("%title_hl%","color: #0591ac;",$update_part_body);
                else
                    $update_part_body=str_replace("%title_hl%","color: #191919;",$update_part_body);

                $begin_at=$cross["begin_at"];
                #$datetime=explode(" ",$cross["begin_at"]);
                $time=$begin_at[0];
                $date=$begin_at[1];

                if($date=="" && $time=="Sometime" && $cross["time_type"]=="")
                //if($date=="0000-00-00" && $time=="00:00:00")
                {
                    $date="Time";
                    $time="To be decided.";
                }
                else if($cross["time_type"]=="Anytime")
                    $time="Anytime";

                if($changed_fields["begin_at"]!="")
                    $update_part_body=str_replace("%beginat_hl%","color: #0591ac;",$update_part_body);
                else
                    $update_part_body=str_replace("%beginat_hl%","color: #333333;",$update_part_body);

                #$mail['place']['line1']=$this->args['place']['line1'];
                #$mail['place']['line2']=$this->args['place']['line2'];
                if ($cross['place']['line1'] === '') {
                    $cross['place']['line1'] = "Place";
                    $cross['place']['line2'] = "To be decided.";
                }
                if(trim($changed_fields['place']['line1'])!=""||trim($changed_fields['place']['line2'])!="")
                    $update_part_body=str_replace("%place_hl%","color: #0591ac;",$update_part_body);
                else
                    $update_part_body=str_replace("%place_hl%","color: #333333;",$update_part_body);


                if(trim($changed_fields["title"])!="")
                    $update_title_info = "Your <span style='color: #0591ac;'>X</span> “<span style='color: #191919;'>$title</span>” has been updated by $updated_identity. ";
                else
                    $update_title_info = "Your <span style='color: #0591ac;'>X</span> has been updated by $updated_identity. ";

                $update_part_body=str_replace("%date%",$date,$update_part_body);
                $update_part_body=str_replace("%time%",$time,$update_part_body);
                $update_part_body=str_replace("%update_title_info%",$update_title_info,$update_part_body);
                $update_part_body=str_replace("%cross_link%",$site_url . "/!{$cross_id_base62}",$update_part_body);
                #$update_part_body=str_replace("%updated_identity%",$updated_identity,$update_part_body);
                $update_part_body=str_replace("%place_line1%",$cross['place']['line1'],$update_part_body);
                $update_part_body=str_replace("%place_line2%",$cross['place']['line2'],$update_part_body);
                $update_part_body=str_replace("%exfee_avartar%",$exfee_avartar,$update_part_body);
                $update_part_body=str_replace("%new_exfee_update%",$new_exfee_identities_str,$update_part_body);
                $update_part_body=str_replace("%exfe_title%",$title,$update_part_body);
                $update_part_body=str_replace("%site_url%",$site_url,$update_part_body);

                $object=array("old_title"=>$title,"content"=>$update_part_body,"cross_id"=>$cross_id,"cross"=>$cross,"to_identity"=>$to_identities,"new_exfee_table"=>$new_exfee_table);
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
                        $create_at=$post["create_at"][2];//humanDateTime($post["create_at"]);
                        $avartar=getUserAvatar($avatar_file_name, 80);
                        $html .= '<tr>'
                               .     '<td valign="top" width="50" align="left">'
                               .         "<img width=\"40\" height=\"40\" src=\"{$avartar}\">"
                               .     '</td>'
                               .     '<td valign="top">'
                               .         "<span class=\"exfe_mail_message\">{$content}</span>"
                               .         "<br>"
                               .         "<span class=\"exfe_mail_msg_identity_name\">{$name}</span> "
                               .         '<span class="exfe_mail_msg_at">at</span> '
                               .         "<span class=\"exfe_mail_msg_time\">{$create_at}</span>"
                               .     '</td>'
                               . '</tr>'
                               . '<tr><td colspan="2" height="20"></td></tr>';
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
                $mail_body=str_replace("%site_url%",$site_url,$mail_body);
                $mail_body=str_replace("%conversation_part%",$conversation_part_body,$mail_body);

                $mail["title"]=str_replace("%exfe_title%",$title,$template_title);
                $mail["to"]=$to_identity["external_identity"];
                $mail["cross_id"]=$cross_id;
                $mail["cross_id_base62"]=int_to_base62($cross_id);
                if($update_array["id_".$cross_id]!="")
                {
                    $change_object=$update_array["id_".$cross_id];
                    if($change_object)
                    {
                        $mail_body=str_replace(
                            "%split_line%",
                            '<tr><td colspan="5" height="10"></td></tr>'
                          . "<tr><td colspan=\"5\" height=\"1\" background=\"$site_url/static/images/mail_dash.png\"></td></tr>"
                          . '<tr><td colspan="5" height="20"></td></tr>',
                            $mail_body
                        );
                        $mail_body=str_replace("%update_part%",$change_object["content"],$mail_body);
                    }
                }
                else
                {
                        $mail_body=str_replace("%update_part%","",$mail_body);
                        $mail_body=str_replace("%split_line%","",$mail_body);
                }

                $mail["body"]=$mail_body;
                array_push($mails,$mail);
            }
        }
        else if($changed_objects)
        {
            foreach($changed_objects as $change_object)
            {
                $cross=$change_object["cross"];
                $mutelink=$change_object["mutelink"];
                $title=$cross["title"];
                $cross_id=$cross["id"];
                $cross_id_62=int_to_base62($cross["id"]);

                $mail_body=str_replace("%exfe_title%",$title,$template_body);
                $mail_body=str_replace("%mutelink%",$mutelink,$mail_body);
                $mail_body=str_replace("%link%",$link,$mail_body);
                $mail_body=str_replace("%site_url%",$site_url,$mail_body);
                $mail_body=str_replace("%conversation_part%","",$mail_body);

                #$mail["title"]=str_replace("%exfe_title%",$title,$template_title);
                $mail["cross_id"]=$cross_id;
                $mail["cross_id_base62"]=int_to_base62($cross_id);

                $cross_id=$change_object["id"];
                if($update_array["id_".$cross_id]!="")
                {

                    $change_object_content=$update_array["id_".$cross_id];
                    $new_exfee_table=$change_object_content["new_exfee_table"];
                    $to_identities=$change_object_content["to_identity"];
                    if($change_object_content)
                    {
                        if($conversation_objects)
                            $mail_body=str_replace("%split_line%","<tr><td colspan=\"5\" height=\"1\" background=\"$site_url/static/images/mail_dash.png\"></td></tr>",$mail_body);
                        else
                            $mail_body=str_replace("%split_line%","",$mail_body);
                        $mail_body=str_replace("%update_part%",$change_object_content["content"],$mail_body);
                        $mail["title"]=str_replace("%exfe_title%",$change_object_content["old_title"],$template_title);
                        $mail["body"]=$mail_body;

                        foreach($to_identities as $to_identity)
                        {
                            if($new_exfee_table[$to_identity["external_identity"]]!=1)
                            {
                                $mail["to"]=$to_identity["external_identity"];
                                array_push($mails,$mail);
                            }
                        }
                    }
                }

            }
        }

        return $mails;
    }

    public function send($title,$body,$to,$cross_id)
    {
            global $email_connect;
            global $connect_count;

            $mail_mime = new Mail_mime(array('eol' => "\n"));
            $mail_mime->setHTMLBody($body);
            #$mail_mime->addAttachment($attachment , "text/calendar","x_".$args['cross_id_base62'].".ics",false);

            $body = $mail_mime->get();
            $headers = $mail_mime->txtHeaders(array('From' => 'x@exfe.com','Reply-To'=>'x+'.$cross_id.'@exfe.com', 'Subject' => "$title"));

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
