<?php
class IdentityActions extends ActionController {
    public function doGet()
    {

        $IdentityData=$this->getModelByName("identity");
        $identity=$IdentityData->getIdentity($_GET["identity"]);

        $responobj["meta"]["code"]=200;
        //$responobj["meta"]["errType"]="Bad Request";
        //$responobj["meta"]["errorDetail"]="invalid_auth";
        if($identity)
            $responobj["response"]["identity"]=$identity;
        else
            $responobj["response"]="";
        echo json_encode($responobj);
        exit();
    }

}
