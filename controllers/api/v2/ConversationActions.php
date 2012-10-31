<?php

class ConversationActions extends ActionController {

    public function doIndex() {
        $params=$this->params;
        $exfee_id=$params["id"];
        $updated_at=$params["updated_at"];
        $clear=$params["clear"];

        if($updated_at!='')
            $updated_at=date('Y-m-d H:i:s',strtotime($updated_at));

        $checkHelper=$this->getHelperByName('check');
        $result = $checkHelper->isAPIAllow(
            'conversation', $params['token'], ['exfee_id' => $exfee_id]
        );
        if($result["check"]!==true)
        {
            if($result["uid"]===0)
                apiError(401,"invalid_auth","");
            else
                apiError(403,"not_authorized","The X you're requesting is private.");
        }

        $helperData=$this->getHelperByName('conversation');
        $conversation=$helperData->getConversationByExfeeId($exfee_id,$updated_at);
        if($clear!='false') {
            //clear counter
            $conversationData=$this->getModelByName('conversation');
            $conversationData->clearConversationCounter($exfee_id,$result["uid"]);
        }
        apiResponse(array("conversation"=>$conversation));
    }


    public function doAdd() {
        // get models
        $modConv  = $this->getModelByName('conversation');
        $hlpCheck = $this->getHelperByName('check');
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
            'conversation_add', $params['token'],
            ['exfee_id' => $exfee_id, 'identity_id' => $post->by_identity_id]
        );
        if (!$result['check']) {
            if ($result['uid']) {
                apiError(403,"not_authorized","The X you're requesting is private.");
            } else {
                apiError(401,"invalid_auth","");
            }
        }
        if (DEBUG) {
            error_log($post_str);
        }
        $post->by_identity_id = $result['by_identity_id'];
        // do post
        $rstPost = $modConv->addPost($post);
        if (!($post_id = $rstPost['post_id'])) {
            if ($rstPost['error']) {
                apiError(400, 'error_post', $rstPost['error'][0]);
            }
            apiError(400, 'failed', '');
        }
        // get post
        $post = $modConv->getPostById($post_id);
        // call Gobus {
        $hlpGobus  = $this->getHelperByName('gobus');
        $hlpCross  = $this->getHelperByName('cross');
        $modDevice = $this->getModelByName('device');
        $modExfee  = $this->getModelByName('exfee');
        $cross_id  = $modExfee->getCrossIdByExfeeId($post->postable_id);
        $cross     = $hlpCross->getCross($cross_id, true);
        $msgArg    = array(
            'cross'         => $cross,
            'post'          => $post,
            'to_identities' => array(),
            'by_identity'   => $post->by_identity,
        );
        $chkUser   = array();
        foreach ($cross->exfee->invitations as $invitation) {
            $msgArg['to_identities'][] = $invitation->identity;
            if ($invitation->identity->connected_user_id > 0
            && !$chkUser[$invitation->identity->connected_user_id]) {
                // get mobile identities
                $mobIdentities = $modDevice->getDevicesByUserid(
                    $invitation->identity->connected_user_id,
                    $invitation->identity
                );
                foreach ($mobIdentities as $mI => $mItem) {
                    $msgArg['to_identities'][] = $mItem;
                }
                // set conversation counter
                if ($invitation->identity->connected_user_id !== $result['uid']) {
                    $modConv->addConversationCounter(
                        $cross->exfee->id,
                        $invitation->identity->connected_user_id
                    );
                }
                // marked
                $chkUser[$invitation->identity->connected_user_id] = true;
            }
        }
        if (DEBUG) {
            error_log(json_encode($msgArg));
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

        $modelData=$this->getModelByName('conversation');
        $userid=$modelData->getUserIdById($post_id);
        $checkHelper=$this->getHelperByName('check');
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
