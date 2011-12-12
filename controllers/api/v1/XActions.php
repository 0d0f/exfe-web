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
    //public function doIndex()
    //{
    //    $Data=$this->getModelByName("X");
    //    $cross=$Data->getCross(base62_to_int($_GET["id"]));
    //    if($cross)
    //    {
    //        $place_id=$cross["place_id"];
    //        $cross_id=$cross["id"];
    //        if(intval($place_id)>0)
    //        {
    //            $placeData=$this->getModelByName("place");
    //            $place=$placeData->getPlace($place_id);
    //            $cross["place"]=$place;
    //        }
    //        $invitationData=$this->getModelByName("invitation");
    //        $invitations=$invitationData->getInvitation_Identities($cross_id);


    //        $host_exfee=array();
    //        $normal_exfee=array();
    //        if($invitations)
    //            foreach ($invitations as $invitation)
    //            {
    //                if ($invitation["identity_id"]==$cross["host_id"])
    //                    array_push($host_exfee,$invitation);
    //                else
    //                    array_push($normal_exfee,$invitation);
    //            }

    //        $cross["host_exfee"]=$host_exfee;
    //        $cross["normal_exfee"]=$normal_exfee;

    //        $ConversionData=$this->getModelByName("conversation");
    //        $conversationPosts=$ConversionData->getConversion(base62_to_int($_GET["id"]),'cross');
    //        $cross["conversation"]=$conversationPosts;

    //        $this->setVar("cross", $cross);
    //        $this->displayView();
    //    }
    //}
    public function doPosts()
    {
        $params=$this->params;
         
        $checkhelper=$this->getHelperByName("check");
        $check=$checkhelper->isAPIAllow("x_post",$params["token"],array("cross_id"=>$params["id"]));
//        var_dump($check);
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

            //check if this identity belongs user
            
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
                $insert_id=$postData->addConversation($cross_id,"cross",$identity_id,"",$_POST["content"]);
                if($insert_id>0)
                {
                    $post=$postData->getConversationById($insert_id);
                    $responobj["meta"]["code"]=200;
                    $responobj["response"]["conversation"]=$post;
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
            //echo "get conversation";
            //echo $params["updated_since"];
            $postData=$this->getModelByName("conversation");
            $result=$postData->getConversationByTimeStr($cross_id,"cross",urldecode($params["updated_since"]));
            $responobj["meta"]["code"]=200;
            $responobj["response"]["conversations"]=$result;
            echo json_encode($responobj);
            exit(0);

        }

    }


}
