<?php
class ConversationActions extends ActionController
{

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
                $r=$postData->addConversation($cross_id,"exfee",$identity_id,"",$comment);

                $logdata=$this->getModelByName("log");
                if(intval($r)>0)
                {
                    $logdata->addLog("identity",$identity_id,"conversation","cross",$cross_id,"",$comment,"{\"id\":$r}");
                    $exfeehelper=$this->getHelperByName("exfee");
                    $exfeehelper->sendConversationMsg($cross_id,$identity_id,$comment);
                }


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
                $responobj["response"]["error_code"]="403";
                $responobj["response"]["error"]="not vaild comment";
        }

        echo json_encode($responobj);
        exit();
    }

    public function doSave() // for ajax api
    {
        $responobj["meta"]["code"]=200;
        $comment=htmlspecialchars($_POST["message"]);
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
                $r=$postData->addConversation($cross_id,"exfee",$identity_id,"",$comment);
                $logdata=$this->getModelByName("log");
                if(intval($r)>0)
                {
                    $logdata->addLog("identity",$identity_id,"conversation","cross",$cross_id,"",$comment,"{\"id\":$r}");
                    $exfeehelper=$this->getHelperByName("exfee");
                    $exfeehelper->sendConversationMsg($cross_id,$identity_id,$comment);
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
                    $userIdentity=humanIdentity($identity,$user);

                    $responobj["response"]["content"]=$comment;
                    $responobj["response"]["created_at"]=date('Y-m-d H:i:s', time());
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
                $responobj["response"]["error_code"]="403";
                $responobj["response"]["error"]="need login";
        }
        echo json_encode($responobj);

        exit();
    }

}
