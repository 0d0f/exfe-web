<?php

class ConversationActions extends ActionController {

    public function doIndex()
    {
        $params=$this->params;
        $exfee_id=$params["id"];

        $checkHelper=$this->getHelperByName("check","v2");
        $result=$checkHelper->isAPIAllow("conversation",$params["token"],array("user_id"=>$uid,"exfee_id"=>$exfee_id));
        if($result["check"]!==true)
        {
            if($result["uid"]===0)
                apiError(401,"invalid_auth","");
            else
                apiError(403,"not_authorized","The X you're requesting is private.");
        }

        $helperData=$this->getHelperByName("conversation","v2");
        $conversation=$helperData->getConversationByExfeeId($exfee_id);
        apiResponse(array("conversation"=>$conversation));
    }
    public function doAdd()
    {
        $params=$this->params;
        $exfee_id=$params["id"];
        print $exfee_id;
    }
}
