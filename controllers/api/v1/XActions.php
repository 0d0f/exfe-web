<?php
class XActions extends ActionController {

    function rsvp($rsvp)
    {
        $params=$this->params;
        $checkhelper=$this->getHelperByName("check");
        $check=$checkhelper->isAPIAllow("x_rsvp",$params["token"],array("cross_id"=>$params["id"]));
        if($check["check"]==false)
        {
            $responobj["meta"]["code"]=403;
            $responobj["meta"]["error"]="forbidden";
            echo json_encode($responobj);
            exit(0);
        }

        //$r=$invitationData->rsvpByUser($cross_id,$userid,$state);
        if($rsvp=="yes")
            $state=INVITATION_YES;
        if($rsvp=="no")
            $state=INVITATION_NO;
        if($rsvp=="maybe")
            $state=INVITATION_MAYBE;


        $invitationData=$this->getModelByName("Invitation");
        $result=$invitationData->rsvpIdentities($params["id"],$check["identity_id_list"],$state,$check["user_id"]);
        $responobj["meta"]["code"]=200;
        $responobj["response"]["invitations"]=$result;;
        echo json_encode($responobj);
        exit(0);

    }
    public function doYes()
    {
       $this->rsvp("yes");
    }
    public function doNo()
    {
       $this->rsvp("no");
    }
    public function doMaybe()
    {
       $this->rsvp("maybe");
    }
    public function doPosts()
    {
        $params=$this->params;

        $checkhelper=$this->getHelperByName("check");
        $check=$checkhelper->isAPIAllow("x_post",$params["token"],array("cross_id"=>$params["id"]));
        if($check["check"]==false)
        {
            $responobj["meta"]["code"]=403;
            $responobj["meta"]["error"]="forbidden";
            echo json_encode($responobj);
            exit(0);
        }
        $cross_id=$params["id"];
        if($_POST)
        {
            $external_identity=$_POST["external_identity"];
            $userData=$this->getModelByName("user");
            $identity_id=$userData->ifIdentityBelongsUser($external_identity,$check["user_id"]);
            if($identity_id===FALSE)
            {
                $responobj["meta"]["code"]=403;
                $responobj["meta"]["error"]="forbidden";
                echo json_encode($responobj);
                exit(0);
            }
            else if(intval($identity_id)>0)
            {
                $postData=$this->getModelByName("conversation");
                $comment=$_POST["content"];
                $insert_id=$postData->addConversation($cross_id,"cross",$identity_id,"",$comment);
                if($insert_id>0)
                {
                    $post=$postData->getConversationById($insert_id);
                    $responobj["meta"]["code"]=200;
                    $responobj["response"]["conversation"]=$post;

                    $logdata=$this->getModelByName("log");
                    $logdata->addLog("identity",$identity_id,"conversation","cross",$cross_id,"",$comment,"{\"id\":$insert_id}");

                    $exfeehelper=$this->getHelperByName("exfee");
                    $exfeehelper->sendConversationMsg($cross_id,$identity_id,$comment);

                    echo json_encode($responobj);
                    exit(0);
                }
                else
                {
                    $responobj["meta"]["code"]=500;
                    $responobj["meta"]["error"]="can't post";
                    echo json_encode($responobj);
                    exit(0);
                }

            }
        }
        else
        {
            $postData=$this->getModelByName("conversation");
            $result=$postData->getConversationByTimeStr($cross_id,"cross",urldecode($params["updated_since"]));
            $responobj["meta"]["code"]=200;
            $responobj["response"]["conversations"]=$result;
            echo json_encode($responobj);
            exit(0);

        }

    }


}
