<?php

class CrossActions extends ActionController {

    public function doIndex()
    {
        $params=$this->params;
        $crossHelper=$this->getHelperByName("cross","v2");
        $cross=$crossHelper->getCross($params["id"]);
        if($cross===NULL)
        {
            $err["code"]=500;
            echo json_encode($err);
            exit(0);
        }
        echo json_encode(array("cross"=>$cross));
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
