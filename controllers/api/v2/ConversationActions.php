<?php

class ConversationActions extends ActionController {

    public function doIndex() {
        $params=$this->params;
        $exfee_id=$params["id"];
        $updated_at=$params["updated_at"];
        $clear=$params["clear"];

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
        if($clear!='false') {
            //clear counter
            $conversationData=$this->getModelByName("conversation","v2");
            $conversationData->clearConversationCounter($exfee_id,$result["uid"]);
        }
        apiResponse(array("conversation"=>$conversation));
    }


    public function doAdd() {
        // get models
        $modConv  = $this->getModelByName('conversation', 'v2');
        $hlpCheck = $this->getHelperByName('check',       'v2');
        // get exfee_id
        $params   = $this->params;
        $exfee_id = $params['id'];
        // get raw post
        $post_str = @file_get_contents('php://input');
        $post     = json_decode($post_str);
        $post->postable_type = 'exfee';
        $post->postable_id   = $exfee_id;
        // check auth
        $result   = $hlpCheck->isAPIAllow(
            'conversation_add', $params['token'], ['user_id' => $uid,
            'exfee_id' => $exfee_id, 'identity_id' => $post->by_identity_id]
        );
        if (!$result['check']) {
            if ($result['uid']) {
                apiError(403,"not_authorized","The X you're requesting is private.");
            } else {
                apiError(401,"invalid_auth","");
            }
        }
        $post->by_identity_id = $result['by_identity_id'];
        // do post
        $post_id = $modConv->addPost($post);
        if (!$post_id) {
            apiError(400, 'failed', '');
        }
        // get post
        $post = $modConv->getPostById($post_id);
        // call Gobus {
        $hlpGobus = $this->getHelperByName('gobus', 'v2');
        $hlpCross = $this->getHelperByName('cross', 'v2');
        $modUser  = $this->getModelByName('user',   'v2');
        $modExfee = $this->getModelByName('exfee',  'v2');
        $cross_id = $modExfee->getCrossIdByExfeeId($post->postable_id);
        $cross    = $hlpCross->getCross($cross_id, true);
        $msgArg   = array(
            'cross'         => $cross,
            'post'          => $post,
            'to_identities' => array(),
            'by_identity'   => $post->by_identity,
        );
        $chkUser  = array();
        foreach ($cross->exfee->invitations as $invitation) {
            $msgArg['to_identities'][] = $invitation->identity;
            // @todo: $msgArg['depended'] = false;
            if ($invitation->identity->connected_user_id
             && !$chkUser[$invitation->identity->connected_user_id]) {
                // get mobile identities
                $mobIdentities = $modUser->getMobileIdentitiesByUserId(
                    $invitation->identity->connected_user_id
                );
                foreach ($mobIdentities as $mI => $mItem) {
                    $msgArg['to_identities'][] = $mItem;
                }
                // set conversation counter
                $modConv->addConversationCounter(
                    $cross->exfee->id,
                    $invitation->identity->connected_user_id
                );
                // depended
                if ($invitation->identity->connected_user_id
                === $identity->connected_user_id) {
                    // @todo: $msgArg['depended'] = true;
                }
                // marked
                $chkUser[$invitation->identity->connected_user_id] = true;
            }
        }
        $hlpGobus->send('cross', 'Update', $msgArg);
        $modExfee->updateExfeeTime($cross->exfee->id);
        // }
        // return
        apiResponse(['post' => $post]);
    }


    public function doDel() {
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
