<?php

class ConversationHelper extends ActionController {

    protected $modConversaction = null;


    public function __construct() {
        $this->modConversation = $this->getModelByName('Conversation');
    }


    public function getConversationByExfeeId($exfee_id, $updated_at = '', $direction = '', $quantity = 50) {
        $identityHelper=$this->getHelperByName('identity');
        $posts=$this->modConversation->getConversationByExfeeId($exfee_id, $updated_at, $direction, $quantity);
        $identities=array();
        $conversation=array();
        foreach($posts as $post)
        {
            $identity_id=$post["identity_id"];
            if(!array_key_exists($identity_id,$identities))
            {
                $identity=$identityHelper->getIdentityById($identity_id);
                $identities[$identity_id]=$identity;
            }

            $conversation_post=new Post($post["id"],$identities[$identity_id],$post["content"], $post["postable_id"],$post["postable_type"],"");
            $conversation_post->created_at=$post["created_at"]." +0000";
            $conversation_post->updated_at=$post["updated_at"]." +0000";
            array_push($conversation,$conversation_post);
        }
        return $conversation;
    }


    public function validatePost($post) {
        return $this->modConversation->validatePost($post);
    }


    public function addConversationCounter($exfee_id, $user_id, $count = 1) {
        return $this->modConversation->addConversationCounter(
            $exfee_id, $user_id, $count
        );
    }

}

