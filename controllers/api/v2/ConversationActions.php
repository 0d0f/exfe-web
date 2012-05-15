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

        $checkHelper=$this->getHelperByName("check","v2");
        $result=$checkHelper->isAPIAllow("conversation_add",$params["token"],array("user_id"=>$uid,"exfee_id"=>$exfee_id));
        if($result["check"]!==true)
        {
            if($result["uid"]===0)
                apiError(401,"invalid_auth","");
            else
                apiError(403,"not_authorized","The X you're requesting is private.");
        }

        $post=json_decode($_POST["post"]);
        $post->postable_type='exfee';
        $post->postable_id=$exfee_id;


        $modelData=$this->getModelByName("conversation","v2");
        $post_id=$modelData->addPost($post);
        $new_post=$modelData->getPostById($post_id);
        $new_post_obj=new Post($post["id"],$post["identity_id"],$post["content"], $post["postable_id"],$post["postable_type"],"");
        apiResponse(array("post"=>$new_post_obj));
    }

    public function doDel()
    {
        $params=$this->params;
        $exfee_id=$params["id"];
        $post_id=$params["post_id"];

        $modelData=$this->getModelByName("conversation","v2");
        $userid=$modelData->getUserIdById($post_id);
        $checkHelper=$this->getHelperByName("check","v2");
        $result=$checkHelper->isAPIAllow("conversation_del",$params["token"],array("user_id"=>$userid));
        if($result["check"]!==true)
        {
            if($result["uid"]===0)
                apiError(401,"invalid_auth","");
            else
                apiError(403,"not_authorized","The X you're requesting is private.");
        }

        $result=$modelData->delPostById($exfee_id,$post_id);
        $post["id"]=$post_id;
        $post["exfee_id"]=$exfee_id;
        if($result===true)
            apiResponse(array("post"=>$post));
        else
            apiError(400,"param_error","Can't delete this post.");
    }

}
