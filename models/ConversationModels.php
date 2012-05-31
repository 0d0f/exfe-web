<?php

class ConversationModels extends DataModel {

    // v1_v2_bridge
    protected function getExfeeIdByCrossId($cross_id) {
        $sql      = "SELECT `exfee_id` FROM `crosses` WHERE `id` = {$cross_id}";
        $dbResult = $this->getRow($sql);
        return intval($dbResult['exfee_id']);
    }


    // v1_v2_bridge
    public function updateExfeeTime($exfee_id)
    {
        $sql="update invitations set exfee_updated_at=NOW() where `cross_id`=$exfee_id;";
        $this->query($sql);
    }


    public function addConversation($postable_id, $postable_type, $identity_id, $title, $content,$date="") {
        $cross_id=$postable_id;
        $postable_id = $this->getExfeeIdByCrossId($postable_id);
        if (intval($postable_id) > 0 && $postable_type === 'exfee') {
            // @todo: check if identity_id belongs this cross
            if($date=="")
            {
                $date=time();
            }
            $craete_at=date("Y-m-d H:i:s",$date);

            $sql = "select id,state from  invitations where identity_id={$identity_id} and cross_id={$postable_id};";
            $row = $this->getRow($sql);
            if (intval($row['id']) > 0) {
                $content = mysql_real_escape_string($content);
                $title = mysql_real_escape_string($title);
                $sql = "insert into posts (identity_id,title,content,postable_id,postable_type,created_at,updated_at) values($identity_id,'$title','$content',$postable_id,'$postable_type',NOW(),NOW())";

		        $result = $this->query($sql);
                if (intval($result["insert_id"]) > 0) {
                    $cross_updated=array();
                    $updated=array("updated_at"=>date('Y-m-d H:i:s',time()),"identity_id"=>$identity_id);
                    $cross_updated["conversation"]=$updated;
                    // v1_v2_bridge {
                    if (($exfee_id = $this->getExfeeIdByCrossId($cross_id))) {
                        $this->updateExfeeTime($exfee_id);
                    }
                    // }
                    saveUpdate($cross_id,$cross_updated);
                    return intval($result["insert_id"]);
                }
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
        $postable_id = $this->getExfeeIdByCrossId($postable_id);
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
                array_push($posts,$post);
            }
        }
        return $posts;

    }


    public function getConversation($postable_id,$postable_type,$updated_since=0,$limit=0)
    {
        $postable_id = $this->getExfeeIdByCrossId($postable_id);
        //$sql="select * from posts where postable_id=$postable_id and postable_type='$postable_type'";
        $sql="select * from posts where postable_id=$postable_id and postable_type='exfee'";
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
                array_push($posts,$post);
            }
        }
        return $posts;
    }

}
