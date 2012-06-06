<?php

class ConversationHelper extends ActionController {
    public function getConversationByExfeeId($exfee_id,$updated_at='') {

        
        $identityHelper=$this->getHelperByName("identity","v2");
        $conversationData=$this->getModelByName("conversation","v2");
        $posts=$conversationData->getConversationByExfeeId($exfee_id,$updated_at) ;
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
}

