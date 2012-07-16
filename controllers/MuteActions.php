<?php

class MuteActions extends ActionController {

    public function doX() {
        $cross_id = intval($_GET["id"]);

        $success=FALSE;
        $identity_id=$_SESSION["identity_id"];
        $status=0;
        if(intval($identity_id)>0) {
            $userData= $this->getModelByName('user');
            $user_id=$userData->getUserIdByIdentityId($identity_id);
            if(intval($user_id)>0) {
                $muteData= $this->getModelByName('mute');
                if($_GET["a"]=="resume") {
                    $result=$muteData->setMute("x",$cross_id,$user_id,0);
                    if(intval($result)>0) {
                        $success=TRUE;
                        $status=0;
                    }
                }
                else {
                    $result=$muteData->setMute("x",$cross_id,$user_id,1);
                    if(intval($result)>0) {
                        $success=TRUE;
                        $status=1;
                    }
                }
            }
        }
        if($success==TRUE) {
            if($_GET["source"]=="ajax") {
                $responobj["response"]["success"]="true";
                $responobj["response"]["status"]=$status;
                echo json_encode($responobj);
                exit(0);
            }
            else {
                $crossDataObj=$this->getModelByName("x");
                $cross=$crossDataObj->getCross($cross_id);
                $this->setVar("mute", array("cross_id"=>$cross_id,"status"=>$status));
                $this->setVar("cross", $cross);
                $this->displayView();
            }
        }
        else {
            if($_GET["source"]=="ajax") {
                $responobj["response"]["success"]="false";
                echo json_encode($responobj);
                exit(0);
            }
            else {
                $crossDataObj = $this->getModelByName("x");
                $cross=$crossDataObj->getCross($cross_id);
                $this->setVar("mute", array("cross_id"=>$cross_id,"status"=>$status));
                $this->setVar("cross", $cross);
                $this->displayView();
            }
        }
    }

}
