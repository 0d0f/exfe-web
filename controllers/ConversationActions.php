<?php
class ConversationActions extends ActionController {

    /**
     * @todo remove this code
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
    */

    public function doEmailSave() //for email api
    {
        $responobj["meta"]["code"]=200;
        $comment=$_POST["comment"];
        $cross_id=intval($_POST["cross_id"]);
        $from=$_POST["from"];
        $postkey=$_POST["postkey"];
        $checkhelper=$this->getHelperByName("check");
        $check=$checkhelper->isAllow("mailconversion","",array("cross_id"=>$cross_id,"from"=>$from));
        if(md5(EmailPost_Key)!=$postkey)
        {
            $responobj["response"]["success"]="false";
            $responobj["response"]["error"]="bad post key.";
            echo json_encode($responobj);
            exit();
        }

        if($check["allow"]!="false")
        {
            $identity_id=$check["identity_id"];
            if(trim($comment)!="" && intval($identity_id)>0  && $cross_id>0)
            {
                $postData=$this->getModelByName("conversation");
                $r=$postData->addConversation($cross_id,"cross",$identity_id,"",$comment);

                $logdata=$this->getModelByName("log");
                $logdata->addLog("identity",$identity_id,"conversation","cross",$cross_id,"",$comment,"");

                $exfeehelper=$this->getHelperByName("exfee");
                $exfeehelper->sendConversationMsg($cross_id,$identity_id,$comment);


                if($r===false)
                {
                    $responobj["response"]["error_code"]="403";
                    $responobj["response"]["error"]="Forbidden";
                    $responobj["response"]["success"]="false";
                }
                else
                {
                    $identityData=$this->getModelByName("identity");
                    $identity=$identityData->getIdentityById($identity_id);

                    $userData=$this->getModelByName("user");
                    $user=$userData->getUserProfileByIdentityId($identity_id);
                    $userIdentity=humanIdentity($identity,$user);

                    $responobj["response"]["comment"]=$comment;
                    $responobj["response"]["created_at"]=RelativeTime(time());
                    $responobj["response"]["cross_id"]=$cross_id;

                    $responobj["response"]["identity"]=$userIdentity;
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
                $responobj["response"]["error"]="not vaild comment";
        }

        echo json_encode($responobj);
        exit();
    }

    public function doSave() //for ajax api
    {
        $responobj["meta"]["code"]=200;
        $comment=htmlspecialchars($_POST["comment"]);
        $cross_id=$_POST["cross_id"];
        $token=$_POST["token"];

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
                $r=$postData->addConversation($cross_id,"cross",$identity_id,"",$comment);

                $logdata=$this->getModelByName("log");
                $logdata->addLog("identity",$identity_id,"conversation","cross",$cross_id,"",$comment,"");

                $exfeehelper=$this->getHelperByName("exfee");
                $exfeehelper->sendConversationMsg($cross_id,$identity_id,$comment);

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
                    $userIdentity=humanIdentity($identity,$user);

                    $responobj["response"]["comment"]=$comment;
                    $responobj["response"]["created_at"]=RelativeTime(time());
                    $responobj["response"]["cross_id"]=$cross_id;

                    $responobj["response"]["identity"]=$userIdentity;
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
