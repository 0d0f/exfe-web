<?php
class IdentityActions extends ActionController {
    public function doGet()
    {

        $IdentityData=$this->getModelByName("identity");
        $identity=$IdentityData->getIdentity($_GET["identity"]);

        if( intval($identity["id"])>0)
            if($identity["avatar_file_name"]=="" || $identity["name"]=="")
            {
                $userData=$this->getModelByName("user");
                $user=$userData->getUserProfileByIdentityId($identity["id"]);

                //get user default
                if($identity["avatar_file_name"]=="")
                    $identity["avatar_file_name"]=$user["avatar_file_name"];    
                if($identity["avatar_file_name"]=="")
                    $identity["avatar_file_name"]="default.png";

                if($identity["name"]=="")
                    $identity["name"]=$user["name"];    
            }
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
