<?php
#require_once("../lib/class.phpmailer.php");
require_once("../common.php");
require_once("../config.php");

class Email_Job
{
    public function perform()
    {
        $name=$this->args['name'];
        if($this->args['name']=="")
            $name=$this->args['external_identity'];

        $by_identity_name=$this->args["by_identity"]['name'];
        if($by_identity_name=="")
            $by_identity_name=$this->args["by_identity"]['external_identity'];

        $host_identity=$this->args['host_identity'];
        $host_name=$host_identity["name"];
        $host_avatar=$host_identity["avatar_file_name"];
        $rsvpyeslink='<a id="exfe_mail_exfee_accept" alt="Accept" href="'.$site_url.'/rsvp/yes?id='.$this->args['cross_id'].'&token='.$this->args['token'].'">Accept</a>';
    
        global $site_url;
        global $email_connect;
        
        $mail["exfe_title"]=$this->args['title'];
        $mail["exfee_name"]=$name;
        $mail["host_name"]=$host_name;

        $mail["hint_title"]='Invitation from '.$host_name.".";

        if(intval($this->args["host_identity_id"])==intval($this->args["identity_id"]))
            $mail["hint_title"]="You're successfully gathering this <span class='exfe_mail_cross'>X.</span>";

        if(intval($this->args['rsvp_status'])===1) //INVITATION_YES
            $mail["rsvp_status"]="<span id='exfe_mail_exfee_beconfirmed'> You're <span class='confirmed'>CONFIRMED</span><br> by <span class='exfe_mail_identity_name'> $by_identity_name </span> to attend.  </span>";
        else
            $mail["rsvp_status"]=$rsvpyeslink;

        $mail["exfe_link"]=$site_url.'/!'.$this->args['cross_id_base62'].'?token='.$this->args['token'];
        $mail["host_avatar"]=$site_url."/eimgs/80_80_".$host_avatar;
        $invitations=$this->args["invitations"];
        $exfee_list="";
        $exfee_sum=sizeof($invitations);
        $exfee_idx=0;
        foreach($invitations as $invitation)
        {
            $exfee_idx=$exfee_idx+1;
            //http://local.exfe.com/eimgs/80_80_default.png
            $exfee_avatar=$site_url."/eimgs/80_80_".$invitation["avatar_file_name"];
            $exfee_name=$invitation['name'];
            if($exfee_name=="")
                $exfee_name=$invitation['external_identity'];
            $exfee_list.="<li> <img class='exfe_mail_avatar' src='$exfee_avatar'> <span class='exfe_mail_identity_name'>$exfee_name</span>";
            if($exfee_idx!=$exfee_sum)
                $exfee_list.=",";
            $exfee_list.="</li>";
        }
        $mail["exfee_list"]=$exfee_list;
        $mail["content"]=$this->args["description"];
//<a id="exfe_mail_main_more" href="">...more</a>
        $begin_at=$this->args["begin_at"];
        $datetime=explode(" ",$begin_at);
        $mail["date"]=$datetime[0];
        $mail["time"]=$datetime[1];
        if($mail["time"]=="")
            $mail["time"]="Anytime";
        $mail["place_line1"]=$this->args["place_line1"];
        $mail["place_line2"]=$this->args["place_line2"];

        $body=$this->getMailBody($mail);

        $icsstr=buildICS($this->args);

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
