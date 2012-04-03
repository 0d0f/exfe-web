<?php

class CrossActions extends ActionController {

    public function doIndex()
    {
        $params=$this->params;
        $crossData=$this->getModelByName("cross","v2");
        $cross=$crossData->getCross(100092);

//category
//created_at
//by_identity


       
        $cross["type"]="cross";
        $cross["relation"]=array("id"=>"","type"=>"");
        $cross["widget"]["background"]=$cross["background"];
        $cross["widget"]["theme"]="";
        $cross["by_identity_id"]=$cross["host_id"];
        unset($cross["background"]);
        print_r($cross);





        //time
        //place
        //attribute
        //exfee_id
        //widget[
        //background
        //theme
        //]
        
    }
}
