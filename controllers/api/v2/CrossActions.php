<?php

class CrossActions extends ActionController {

    public function doIndex()
    {
        $params=$this->params;
        $crossData=$this->getHelperByName("cross","v2");
        $cross=$crossData->getCross($params["id"]);

        $cross=new Cross($title, $description, $time, $place, $attribute, $exfee_id, $widget);
        //var_dump($cross);
    }
}
