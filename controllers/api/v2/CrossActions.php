<?php

class CrossActions extends ActionController {

    public function doIndex()
    {
        $params=$this->params;
        $crossHelper=$this->getHelperByName("cross","v2");
        $cross=$crossHelper->getCross($params["id"]);
        echo json_encode($cross);
    }
    public function doNew()
    {
        $params=$this->params;
        $cross_str=$_POST["cross"];
        $cross=json_decode($cross_str);
        $crossHelper=$this->getHelperByName("cross","v2");
        $crossHelper->gatherCross($cross);

        //print_r($cross);


    }
}
