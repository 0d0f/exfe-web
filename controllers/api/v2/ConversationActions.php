<?php

class ConversationActions extends ActionController {

    public function doIndex() {
        $params=$this->params;
        $exfee_id=$params["id"];
        $updated_at=$params["updated_at"];
        if($updated_at!='')
            $updated_at=date('Y-m-d H:i:s',strtotime($updated_at));

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
        $conversation=$helperData->getConversationByExfeeId($exfee_id,$updated_at);
        apiResponse(array("conversation"=>$conversation));
    }


    public function doAdd() {
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

        $post_str=@file_get_contents('php://input');
        $post=json_decode($post_str);
        $post->postable_type='exfee';
        $post->postable_id=$exfee_id;


        $modelData=$this->getModelByName("conversation","v2");
        $post_id=$modelData->addPost($post);
        $new_post=$modelData->getPostById($post_id);

        $identityHelper=$this->getHelperByName("identity","v2");
        $identity=$identityHelper->getIdentityById($new_post["identity_id"]);
        $new_post_obj=new Post($new_post["id"],$identity,$new_post["content"], $new_post["postable_id"],$new_post["postable_type"],"");
        $new_post_obj->created_at=$new_post["created_at"];

        // call Gobus {
        $hlpGobus = $this->getHelperByName('gobus', 'v2');
        $hlpCross = $this->getHelperByName('cross', 'v2');
        $modUser  = $this->getModelByName('user',   'v2');
        $modExfee = $this->getModelByName('exfee',  'v2');
        $cross_id = $modExfee->getCrossIdByExfeeId($new_post_obj->postable_id);
        $cross    = $hlpCross->getCross($cross_id, true);
        $msgArg   = array(
            'cross'         => $cross,
            'post'          => $new_post_obj,
            'to_identities' => array(),
            'by_identity'   => $identity,
        );
        $chkMobUs = array();
        foreach ($cross->exfee->invitations as $invitation) {
            $msgArg['to_identities'][] = $invitation->identity;
            // get mobile identities
            if (!$chkMobUs[$invitation->identity->connected_user_id]) {
                $mobIdentities = $modUser->getMobileIdentitiesByUserId(
                    $invitation->identity->connected_user_id
                );
                foreach ($mobIdentities as $mI => $mItem) {
                    $msgArg['to_identities'][] = $mItem;
                }
                $chkMobUs[$invitation->identity->connected_user_id] = true;
            }
        }
        $hlpGobus->send('cross', 'Update', $msgArg);
        // }

        apiResponse(array("post" => $new_post_obj));
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
