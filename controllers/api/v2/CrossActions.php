<?php

class CrossActions extends ActionController {

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
        $cross_str=$_POST["cross"];
        $cross=json_decode($cross_str);
        $crossHelper=$this->getHelperByName("cross","v2");
        $cross_id=$crossHelper->gatherCross($cross);
        
        if(intval($cross_id)>0)
        {
            $crossHelper=$this->getHelperByName("cross","v2");
            $cross=$crossHelper->getCross($cross_id);
            echo json_encode(array("cross"=>$cross));
        }
        else
        {
            $err["code"]=500;
            echo json_encode($err);
        }

    }
    
    
    public function doEdit()
    {
        $params=$this->params;
        $cross_str=$_POST["cross"];
        $cross=json_decode($cross_str);
        $cross->id=$params["id"];
        $crossHelper=$this->getHelperByName("cross","v2");
        $cross_id=$crossHelper->editCross($cross);
        if(intval($cross_id)>0)
        {
            $crossHelper=$this->getHelperByName("cross","v2");
            $cross=$crossHelper->getCross($cross_id);
            echo json_encode(array("cross"=>$cross));
        }
        else
        {
            $err["code"]=500;
            echo json_encode($err);
        }
    }
}
