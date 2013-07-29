<?php

class ConversationModels extends DataModel {

    public function getConversationByExfeeId($exfee_id, $updated_at = '', $direction = '', $quantity = 0) {
        // get direction and order
        switch (strtolower($direction)) {
            case 'older':
                $direction  = '<';
                $order_cond = 'DESC';
                break;
            case 'newer':
            default:
                $direction  = '>';
                $order_cond = '';
        }
        // get update condition
        $update_cond = '';
        if ($updated_at !== '') {
            $update_cond = "AND `updated_at` {$direction} '{$updated_at}'";
        }
        // get limit condition
        $quantity = (int) $quantity;
        if ($quantity <= 0 || $quantity > 10000) {
            $quantity  = 10000;
        }
        // query
        $sql = "SELECT * FROM `posts` WHERE `postable_id` = {$exfee_id} AND (`postable_type` = 'exfee' OR `postable_type` = 'cross') {$update_cond} ORDER BY `updated_at` {$order_cond} LIMIT {$quantity}";
        $posts = $this->getAll($sql);
        if ($direction === '>') {
            $posts = array_reverse($posts);
        }
        return $posts;
    }


    public function addPost($post, $timestamp = 0) {
        $chkPost = $this->validatePost($post);
        if ($chkPost['error']) {
            return ['post_id' => 0, 'post' => $post, 'error' => $chkPost['error']];
        }
        $post = $chkPost['post'];
        $post->by_identity_id = (int) $post->by_identity_id;
        $post->postable_id    = (int) $post->postable_id;
        $sql                  = "select id from crosses where exfee_id = {$post->postable_id};";
        $cross                = $this->getRow($sql);
        $cross_id             = $cross["id"];
        $updated              = array(
            'updated_at'  => date('Y-m-d H:i:s', time()),
            'identity_id' => $post->by_identity_id,
            'content'     => $post->content,
        );
        $post->content        = dbescape($post->content);
        $post->postable_type  = dbescape($post->postable_type);
        $time                 = $timestamp ? "FROM_UNIXTIME({$timestamp})" : 'NOW()';
        $sql                  = "insert into posts (identity_id,content,postable_id,postable_type,created_at,updated_at)
                                 values ({$post->by_identity_id}, '{$post->content}', {$post->postable_id}, '{$post->postable_type}', {$time}, {$time});";
        $result               = $this->query($sql);
        $post_id              = intval($result['insert_id']);
        if ($post_id) {
            saveUpdate($cross_id, ['conversation' => $updated]);
        }
        return ['post_id' => $post_id, 'post' => $post, 'error' => []];
    }


    public function getPostById($post_id) {
        $rawPost = $this->getRawPostById($post_id);
        if ($rawPost) {
            $hlpIdentity = $this->getHelperByName('identity');
            $identity    = $hlpIdentity->getIdentityById($rawPost['identity_id']);
            return new Post(
                $rawPost['id'], $identity, $rawPost['content'],
                $rawPost['postable_id'], $rawPost['postable_type'],
                '', $rawPost['created_at']
            );
        } else {
            return null;
        }
    }


    public function getRawPostById($post_id) {
        return $this->getRow(
            "SELECT * FROM `posts` WHERE `del` = 0 and `id` = {$post_id}"
        );
    }


    public function delPostById($exfee_id,$post_id)
    {
        $sql="update posts set del=1 where id=$post_id and postable_id=$exfee_id;";
        $result   = $this->query($sql);
        if($result>0)
            return true;
        return false;
    }


    public function getUserIdById($post_id)
    {
        $sql="select p.identity_id,u.userid from posts p,user_identity u where id=$post_id and u.identityid=p.identity_id;";
        $post=$this->getRow($sql);
        return intval($post["userid"]);
    }


    public function addConversationCounter($exfee_id,$user_id,$count=1)
    {
        if( intval($exfee_id) > 0 && intval($user_id)>0 ) {
            $key = $exfee_id.":".$user_id;
            $redis = new Redis();
            $redis->connect(REDIS_SERVER_ADDRESS, REDIS_SERVER_PORT);
            $conversation_count = $redis->HGET("conversation:count",$key);
            $conversation_count=$conversation_count+$count;
            $redis->HSET("conversation:count",$key,$conversation_count);
        }
    }


    public function clearConversationCounter($exfee_id,$user_id)
    {
        $key = $exfee_id.":".$user_id;
        $redis = new Redis();
        $redis->connect(REDIS_SERVER_ADDRESS, REDIS_SERVER_PORT);
        $redis->HSET("conversation:count",$key,0);
    }


    public function getConversationCounter($exfee_id,$user_id) {
        $key = $exfee_id.":".$user_id;
        $redis = new Redis();
        $redis->connect(REDIS_SERVER_ADDRESS, REDIS_SERVER_PORT);
        $conversation_count = $redis->HGET("conversation:count",$key);
        return $conversation_count;
    }


    public function validatePost($post) {
        // init
        $result = ['post' => $post, 'error' => []];
        // check structure
        if (!$post || !is_object($post)) {
            $result['error'][] = 'invalid_post_structure';
        }
        // check content
        if (isset($result['post']->content)) {
            $result['post']->content = formatDescription($result['post']->content);
            if (strlen($result['post']->content) === 0) {
                $result['error'][] = 'post';
            }
        } else {
            $result['error'][] = 'no_post_content';
        }
        return $result;
    }

}
