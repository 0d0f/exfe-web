<?php

class ConversationModels extends DataModel {

    public function getConversationByExfeeId($exfee_id,$updated_at='') {
        $update_cond="";
        if($updated_at!='')
            $update_cond="and updated_at>'$updated_at'";
        $sql="select * from posts where postable_id=$exfee_id and (postable_type='exfee' or postable_type='cross') $update_cond order by updated_at desc;";
        $posts=$this->getAll($sql);
        return $posts;
    }


    public function addPost($post, $timestamp = 0) {
        $sql      = "select id from crosses where exfee_id={$post->postable_id};";
        $cross    = $this->getRow($sql);
        $cross_id = $cross["id"];

        $updated  = array(
            'updated_at'  => date('Y-m-d H:i:s', time()),
            'identity_id' => $post->by_identity_id,
            'content'     => $post->content,
        );
        $cross_updated["conversation"]=$updated;
        saveUpdate($cross_id,$cross_updated);
        $time     = $timestamp ? "FROM_UNIXTIME({$timestamp})" : 'NOW()';
        $sql      = "insert into posts (identity_id,content,postable_id,postable_type,created_at,updated_at) values ({$post->by_identity_id},'{$post->content}',{$post->postable_id},'{$post->postable_type}',{$time},NOW());";
        $result   = $this->query($sql);
        $post_id  = intval($result['insert_id']);
        return $post_id;
    }


    public function getPostById($post_id)
    {
        $sql="select * from posts where del=0 and id=$post_id;";
        $post=$this->getRow($sql);
        return $post;
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

}
