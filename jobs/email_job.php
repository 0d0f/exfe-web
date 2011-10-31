<?php
#require_once("../lib/class.phpmailer.php");
require_once("../common.php");
require_once("../config.php");

class Email_Job
{
    public function perform()
    {
        global $site_url;
        global $email_connect;
        $name=$this->args['name'];
        if($this->args['name']=="")
            $name=$this->args['external_identity'];

        $icsstr=buildICS($this->args);

        $by_identity_name=$this->args["by_identity"]['name'];
        if($by_identity_name=="")
            $by_identity_name=$this->args["by_identity"]['external_identity'];

        $host_identity=$this->args['host_identity'];
        $host_identity["id"]=$this->args["host_identity_id"];
        $host_name=$host_identity["name"];
        $host_avatar=$host_identity["avatar_file_name"];
        $rsvpyeslink=$site_url.'/rsvp/yes?id='.$this->args['cross_id'].'&token='.$this->args['token'];
    
        
        $mail["exfe_title"]=$this->args['title'];
        $mail["exfee_name"]=$name;
        $mail["host_name"]=$host_name;

        $mail["hint_title"]='Invitation from '.$host_name.".";

        if(intval($this->args["host_identity_id"])==intval($this->args["identity_id"]))
            $mail["hint_title"]="You're successfully gathering this <span style='color: #0591ac; text-decoration: none;'>X.</span>";

        if(intval($this->args['rsvp_status'])===1) //INVITATION_YES
            $mail["rsvp_status"]="<tr> <td> <span style='margin-left:15px; display: block; float: left; color: #333333;'> You're <span style='font-weight: bold;'>CONFIRMED</span> by <span class='exfe_mail_identity_name'>$by_identity_name</span> to attend.</span> </td> </tr>";
        else
            $mail["rsvp_accept"]="<a style='float: left; display: block; text-decoration: none; border: 1px solid #bebebe; background-color: #add1dc; color: #000000; padding: 5px 30px 5px 30px; margin-left: 30px;' alt='Accept' href='$rsvpyeslink'>Accept</a>";

        $mail["exfe_link"]=$site_url.'/!'.$this->args['cross_id_base62'].'?token='.$this->args['token'];
        $mail["host_avatar"]=$site_url."/".getHashFilePath("eimgs",$host_avatar)."/80_80_".$host_avatar;
        $invitations=$this->args["invitations"];
        $exfee_list="";
        foreach($invitations as $invitation)
        {
            if(intval($invitation["identity_id"])!=intval($host_identity["id"]))
            {
                #$exfee_idx=$exfee_idx+1;
                //http://local.exfe.com/eimgs/80_80_default.png
                $exfee_avatar=$site_url."/".getHashFilePath("eimgs",$invitation["avatar_file_name"])."/80_80_".$invitation["avatar_file_name"];
                $exfee_name=$invitation['name'];
                if($exfee_name=="")
                    $exfee_name=$invitation['external_identity'];
                $exfee_list.= "<tr> <td width='25' align='left'> <img width='20' height='20' src='$exfee_avatar'> </td> <td> <span class='exfe_mail_identity_name'>$exfee_name</span> </td> </tr>";
            }
        }
        $mail["exfee_list"]=$exfee_list;
        $mail["content"]=$this->args["description"];
//<a id="exfe_mail_main_more" href="">...more</a>
        $begin_at=$this->args["begin_at"];
        $datetime=explode(" ",$begin_at);
        $mail["date"]=$datetime[0];
        $mail["time"]=$datetime[1];
        if($mail["date"]=="0000-00-00" && $mail["time"]=="00:00:00")
        {
            $mail["date"]="Time";
            $mail["time"]="To be decided.";
            $icsstr="";
        }
        else if($mail["time"]=="00:00:00")
            $mail["time"]="Anytime";
        $mail["place_line1"]=$this->args["place_line1"];
        $mail["place_line2"]=$this->args["place_line2"];
        if($mail["place_line1"]=="")
        {
            $mail["place_line1"]="Place";
            $mail["place_line2"]="To be decided.";
        }



        $body=$this->getMailBody($mail);

       

        if($email_connect=="")
            smtp_connect();
        $this->send($body["title"],$body["body"],$icsstr,$this->args);
    }
    public function getMailBody($mail)
    {
        global $site_url;
        $template=file_get_contents("invitation_template.html");
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
        $mail_body=str_replace("%rsvp_accept%",$mail["rsvp_accept"],$mail_body);
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
            $mail_mime->addAttachment($attachment , "text/calendar","x_".$args['cross_id_base62'].".ics",false);

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
