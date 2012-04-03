<?php

class CrossActions extends ActionController {

    public function doIndex()
    {
        $params=$this->params;
        $crossData=$this->getModelByName("cross","v2");
        $cross=$crossData->getCross(100092);
    }
}
