<?php
class ConversationActions extends ActionController {

    public function doAdd()
    {
        $checkhelper=$this->getHelperByName("check");
        $check=$checkhelper->isAllow("conversion","",array("cross_id"=>$cross_id,"token"=>$token));
        if($check["allow"]=="false")
        {
            header( 'Location: /s/login' ) ;
            exit(0);
        }

        $postData=$this->getModelByName("conversation");
        $cross_id=intval($_GET["id"]);
        $identity_id=$_SESSION["identity_id"];
        $postData->addConversation($cross_id,"cross",$identity_id,"",$_POST["comment"]);

        $cross_id=intval($_GET["id"]);
        $cross_id_base62=int_to_base62($cross_id);
        header( "Location: /!$cross_id_base62" ) ;
        exit(0);
    }
    public function doSave() //for ajax api
    {
        $responobj["meta"]["code"]=200;
        $comment=$_POST["comment"];
        $cross_id=$_POST["cross_id"];

        $checkhelper=$this->getHelperByName("check");
        $check=$checkhelper->isAllow("conversion","",array("cross_id"=>$cross_id,"token"=>$token));
        if($check["allow"]!="false")
        {
            $identity_id=$_SESSION["tokenIdentity"]["identity_id"];
            if(intval($identity_id)==0)
                $identity_id=$_SESSION["identity_id"];
            if(trim($comment)!="" && intval($identity_id)>0 )
            {
                $postData=$this->getModelByName("conversation");
                $r=$postData->addConversation($cross_id,"cross",$identity_id,"",$_POST["comment"]);

                $logdata=$this->getModelByName("log");
                $logdata->addLog("identity",$identity_id,"conversation","cross",$cross_id,"",$_POST["comment"],"");

                //base mail object
                $link=SITE_URL.'/!'.int_to_base62($cross_id);
                $mail["link"]=$link;
                $mail["template_name"]="conversation";
                $mail["action"]="post";
                $mail["content"]=$comment;

                $crossData=$this->getModelByName("x");
                $cross=$crossData->getCross($cross_id);
                $mail["title"]=$cross["title"];

                $identityData=$this->getModelByName("identity");
                $exfee_identity=$identityData->getIdentityById($identity_id);
                $exfee_identity=humanIdentity($exfee_identity,NULL);
                $mail["exfee_name"]=$exfee_identity["name"];

                $apnargs["exfee_name"]=$exfee_identity["name"];
                $apnargs["comment"]=$comment;
                $apnargs["cross_id"]=$cross_id;



                $invitationData=$this->getModelByName("invitation");
                $invitation_identities=$invitationData->getInvitation_Identities($cross_id);
                if($invitation_identities)
                foreach($invitation_identities as $invitation_identity)
                {
                    $identities=$invitation_identity["identities"];
                    if($identities)
                    foreach($identities as $identity)
                    {
                        if(intval($identity["status"])==3)
                        {
                            $identity=humanIdentity($identity,NULL);
                            $msghelper=$this->getHelperByName("msg");
                            if($identity["provider"]=="email")
                            {
                                $mail["external_identity"]=$identity["external_identity"];
                                $msghelper->sentTemplateEmail($mail);
                            }
                            if($identity["provider"]=="iOSAPN")
                            {
                                $apnargs["external_identity"]=$identity["external_identity"];
                                $msghelper->sentApnConversation($apnargs);
                            }
                        }
                    }
                }


                if($r===false)
                {
                    $responobj["response"]["success"]="false";
                }
                else
                {
                    $identityData=$this->getModelByName("identity");
                    $identity=$identityData->getIdentityById($identity_id);

                    $userData=$this->getModelByName("user");
                    $user=$userData->getUserProfileByIdentityId($identity_id);

                    $responobj["response"]["comment"]=$comment;
                    $responobj["response"]["created_at"]=RelativeTime(time());
                    $responobj["response"]["cross_id"]=$cross_id;
                    if($identity["name"]=="")
                        $identity["name"]=$user["name"];
                    if($identity["bio"]=="")
                        $identity["bio"]=$user["bio"];
                    if($identity["avatar_file_name"]=="")
                        $identity["avatar_file_name"]=$user["avatar_file_name"];

                    $responobj["response"]["identity"]=$identity;
                    $responobj["response"]["success"]="true";
                }
            }
            else
            {
                $responobj["response"]["success"]="false";
            }
        }
        else
        {
                $responobj["response"]["success"]="false";
                $responobj["response"]["error"]="need login";
        }
        echo json_encode($responobj);

        exit();
    }

}
