<?php

class ConversationActions extends ActionController {

    public function doIndex() {
        $params     = $this->params;
        $exfee_id   = $params['id'];
        $updated_at = $params['updated_at'];
        $direction  = $params['direction'];
        $quantity   = $params['quantity'];
        $clear      = $params['clear'];

        if ($updated_at) {
            $raw_updated_at = strtotime($updated_at);
            if ($raw_updated_at !== false) {
                $updated_at = date('Y-m-d H:i:s', $raw_updated_at);
            } else {
                $updated_at = '';
            }
        } else {
            $updated_at = '';
        }

        $checkHelper = $this->getHelperByName('check');
        $result = $checkHelper->isAPIAllow(
            'conversation', $params['token'], ['exfee_id' => $exfee_id]
        );
        if (!$result['check']) {
            if ($result['uid']) {
                apiError(403, 'not_authorized', "The X you're requesting is private.");
            } else {
                apiError(401, 'invalid_auth', '');
            }
        }

        $helperData   = $this->getHelperByName('conversation');
        $conversation = $helperData->getConversationByExfeeId(
            $exfee_id, $updated_at, $direction, $quantity
        );
        if ($clear !== 'false') {
            //clear counter
            $conversationData=$this->getModelByName('conversation');
            $conversationData->clearConversationCounter($exfee_id, $result['uid']);
        }
        $modExfee = $this->getModelByName('exfee');
        $cross_id = $modExfee->getCrossIdByExfeeId($exfee_id);
        touchCross($cross_id, $result['uid']);
        apiResponse(['conversation' => $conversation]);
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
        $post     = @json_decode($post_str);
        if (!$post) {
            apiError(400, 'error_post');
        }
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
        $post->by_identity_id = (int) $result['by_identity_id'];
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
        $modQueue = $this->getModelByName('Queue');
        $modExfee = $this->getModelByName('exfee');
        $hlpCross = $this->getHelperByName('cross');
        $cross_id = $modExfee->getCrossIdByExfeeId($post->postable_id);
        $cross    = $hlpCross->getCross($cross_id, true);
        $draft    = isset($cross->attribute)
                 && isset($cross->attribute['state'])
                 && $cross->attribute['state'] === 'draft';
        if (!$draft) {
            $modQueue->despatchConversation(
                $cross, $post, $result['uid'], $post->by_identity_id
            );
        }
        // }
        $modExfee->updateExfeeTime($cross->exfee->id);
        // return
        touchCross($cross_id, $result['uid']);
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
        if ($result === true) {
            $modExfee = $this->getModelByName('exfee');
            $cross_id = $modExfee->getCrossIdByExfeeId($exfee_id);
            touchCross($cross_id, $result['uid']);
            apiResponse(array("post"=>$post));
        } else {
            apiError(400,"param_error","Can't delete this post.");
        }
    }

}
