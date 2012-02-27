<?php
class ConversationModels extends DataModel{
    public function addConversation($postable_id,$postable_type,$identity_id,$title,$content)
    {
        if(intval($postable_id)>0 & $postable_type=="cross")
        {
            //TODO:check if identity_id belongs this cross

            $sql="select id,state from  invitations where identity_id=$identity_id and cross_id=$postable_id;";
            $row=$this->getRow($sql);
            if(intval($row["id"])>0)
            {
                // @todo: time fixed
                $time=time() + (60 * 60 * 5);
                $content=mysql_real_escape_string($content);
                $title=mysql_real_escape_string($title);
                $sql="insert into posts (identity_id,title,content,postable_id,postable_type,created_at,updated_at) values($identity_id,'$title','$content',$postable_id,'$postable_type',FROM_UNIXTIME($time),FROM_UNIXTIME($time))";

		        $result=$this->query($sql);
                if(intval($result["insert_id"])>0)
                    return intval($result["insert_id"]);
            }

        }
        return false;
    }

    public function getConversationById($post_id)
    {
        $sql="select * from posts where id=$post_id;";
        $result=$this->getRow($sql);
        $identity_id=intval($result["identity_id"]);
        if($identity_id>0)
        {
            $sql="select provider,external_identity,external_username,name,bio,avatar_file_name from identities where id=$identity_id;";
            $identity=$this->getRow($sql);
            $sql="select name,avatar_file_name from users,user_identity where users.id=user_identity.userid and user_identity.identityid=$identity_id";
            $user=$this->getRow($sql);
            $humanidentity=humanIdentity($identity,$user);
            $result["identity"]=$humanidentity;
        }
        return $result;
    }

    public function getConversationByTimeStr($postable_id,$postable_type,$updated_since="",$limit=0)
    {
        $sql="select id,identity_id,title,content as message,postable_id,postable_type,created_at,updated_at from posts where postable_id=$postable_id and postable_type='$postable_type'";
        if($updated_since>0)
            $sql=$sql." and created_at>'$updated_since' ";

        $sql=$sql." order by updated_at desc ";
        if($limit>0)
            $sql=$sql." limit $limit;";

        $result=$this->getAll($sql);
        $identity=array();
        $posts=array();
        if ($result) {
            foreach ($result as $post) {
                $identity_id=$post["identity_id"];
                if ($identity[$identity_id] == "") {
                    $sql="select provider,external_identity,external_username,name,bio,avatar_file_name from identities where id=$identity_id;";
                    $identity=$this->getRow($sql);
                    if ($identity) {
                        $sql="select name,avatar_file_name from users,user_identity where users.id=user_identity.userid and user_identity.identityid=$identity_id";
                        $user=$this->getRow($sql);
                        $humanidentity=humanIdentity($identity,$user);
                        $post["identity"]=$humanidentity;
                        $identity[$identity_id]=$humanidentity;
                    }
                } else {
                    $post["identity"]=$identity[$identity_id];
                }
                // @todo: time fixed
                $strDate = date('Y-m-d H:i:s', strtotime($post['created_at']) );
                $post['created_at']=$strDate;
                $post['updated_at']=$strDate;
                array_push($posts,$post);
            }
        }
        return $posts;

    }

    public function getConversation($postable_id,$postable_type,$updated_since=0,$limit=0)
    {
        $sql="select * from posts where postable_id=$postable_id and postable_type='$postable_type'";
        if($updated_since>0)
            $sql=$sql." and created_at>FROM_UNIXTIME($updated_since) ";

        $sql=$sql." order by updated_at desc ";
        if($limit>0)
            $sql=$sql." limit $limit;";

        $result=$this->getAll($sql);
        $identity=array();
        $posts=array();
        if ($result) {
            foreach ($result as $post) {
                $identity_id=$post["identity_id"];
                if ($identity[$identity_id] == "") {
                    $sql="select provider,external_identity,external_username,name,bio,avatar_file_name from identities where id=$identity_id;";
                    $identity=$this->getRow($sql);
                    if ($identity) {
                        $sql="select name,avatar_file_name from users,user_identity where users.id=user_identity.userid and user_identity.identityid=$identity_id";
                        $user=$this->getRow($sql);
                        //if(trim($identity["name"])=="" )
                        //    $identity["name"]=$user["name"];
                        //if(trim($identity["avatar_file_name"])=="")
                        //    $identity["avatar_file_name"]=$user["avatar_file_name"];

                        //$post["identity"]=$identity;
                        //$post["user"]=$user;
                        $humanidentity=humanIdentity($identity,$user);
                        $post["identity"]=$humanidentity;
                        $identity[$identity_id]=$humanidentity;
                    }
                } else {
                    $post["identity"]=$identity[$identity_id];
                }
                // @todo: time fixed
                $strDate = date('Y-m-d H:i:s', strtotime($post['created_at']) + (60 * 60 * 8));
                $post['created_at']=$strDate;
                $post['updated_at']=$strDate;
                array_push($posts,$post);
            }
        }
        return $posts;

    }


}
