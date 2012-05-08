<?php

class CrossesActions extends ActionController {

    public function doIndex()
    {
        $params=$this->params;
        $checkHelper=$this->getHelperByName("check","v2");
        $result=$checkHelper->isAPIAllow("cross",$params["token"],array("cross_id"=>$params["id"]));
        if($result["check"]!==true)
        {
            if($result["uid"]===0)
                apiError(401,"invalid_auth","");
            else
                apiError(403,"not_authorized","The X you're requesting is private.");
        }

        $crossHelper=$this->getHelperByName("cross","v2");
        $cross=$crossHelper->getCross($params["id"]);
        if($cross===NULL)
            apiError(400,"param_error","The X you're requesting is not found.");
        apiResponse(array("cross"=>$cross));
    }
    
    
    public function doAdd()
    {
        $params=$this->params;
        $by_identity_id=$_POST["by_identity_id"];
        $checkHelper=$this->getHelperByName("check","v2");
        $result=$checkHelper->isAPIAllow("cross_add",$params["token"],array("by_identity_id"=>$by_identity_id));
        if($result["check"]!==true)
        {
            if($result["uid"]===0)
                apiError(401,"invalid_auth","");
            else
                apiError(403,"not_authorized","The X you're requesting is private.");
        }
        $cross_str=$_POST["cross"];
        $cross=json_decode($cross_str);
        $crossHelper=$this->getHelperByName("cross","v2");
        $cross_id=$crossHelper->gatherCross($cross,$by_identity_id);
        
        if(intval($cross_id)>0)
        {
            $crossHelper=$this->getHelperByName("cross","v2");
            $cross=$crossHelper->getCross($cross_id);
            apiResponse(array("cross"=>$cross));
        }
        else
            apiError(500,"server_error","Can't gather this Cross.");

    }
    
    
    public function doEdit()
    {
        $params=$this->params;
        $cross_str=$_POST["cross"];
        $by_identity_id=intval($_POST["by_identity_id"]);
        $cross=json_decode($cross_str);

        $checkHelper=$this->getHelperByName("check","v2");
        $result=$checkHelper->isAPIAllow("cross_edit",$params["token"],array("cross_id"=>$params["id"],"by_identity_id"=>$by_identity_id));
        if($result["check"]!==true)
        {
            if($result["uid"]===0)
                apiError(401,"invalid_auth","");
            else
                apiError(403,"not_authorized","The X you're requesting is private.");
        }
        $cross->id=$params["id"];
        $cross->exfee_id=$result["exfee_id"];
        $crossHelper=$this->getHelperByName("cross","v2");
        $msgArg = array('event' => array('old_cross' => $crossHelper->getCross(intval($params["id"]))));
        $cross_id=$crossHelper->editCross($cross,$by_identity_id);
        if(intval($cross_id)>0)
        {
            $crossHelper=$this->getHelperByName("cross","v2");
            $msgArg['cross'] = $cross = $crossHelper->getCross($cross_id);
            // call Gobus
            $hlpGobus = $this->getHelperByName('gobus', 'v2');
            foreach ($cross->exfee->invitations as $invitation) {
                if ($invitation->identity->id === $by_identity_id) {
                    $msgArg['by_identity'] = $invitation->identity;
                    break;
                }
            }
            foreach ($cross->exfee->invitations as $invitation) {
                $msgArg['to_identity'] = $invitation->identity;
                $hlpGobus->send("{$invitation->identity->provider}_job", 'cross', $msgArg);
            }
            //
            apiResponse(array("cross"=>$cross));
        }
        else
            apiError(500,"server_error","Can't Edit this Cross.");
    }
}
