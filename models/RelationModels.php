<?php
class RelationModels extends DataModel{
    public function saveRelations($userid,$r_identityid)
    {
//,$name,$external_identity
        if(intval($userid)>0 && intval($r_identityid)>0)
        {
            $sql="select userid from user_relations where userid=$userid and r_identityid=$r_identityid";
            $result=$this->getRow($sql);
            if(sizeof($result)==0)
            {
                $sql="select name,external_identity,provider from identities where id=$r_identityid";
                $identity=$this->getRow($sql);
                if($identity)
                {
                    if($identity["provider"]=="email")
                    {
                        $name=$identity["name"];
                        $external_identity=$identity["external_identity"];
                        $provider=$identity["provider"];
                        $sql="insert into user_relations (userid,r_identityid,name,external_identity,provider) values($userid,$r_identityid,'$name','$external_identity','$provider')"; 
                        $result=$this->query($sql);
                        if(intval($result["insert_id"])>0)
                            return intval($result["insert_id"]);
                    }
                }
            }
        }
        return 0;
    }
    public function saveRelationsWithIds($userid,$identityid_list,$my_identity_id)
    {
        if ($identityid_list) {
            $identity_ids = implode(' OR `r_identityid` = ', $identityid_list);
            $sql = "SELECT r_identityid FROM `user_relations` WHERE userid=$userid and (`r_identityid` = {$identity_ids});";
            $ids=$this->getColumn($sql);
            $relation_ids=array();
            foreach($identityid_list as $identity_id)
            {
                if(!in_array($identity_id , $ids))
                {
                    array_push($relation_ids,$identity_id);

                }
            }

            if($relation_ids)
            {
                //$sql="select name,external_identity,provider from identities where id=$r_identityid";
                $identity_str = implode(' OR `id` = ', $relation_ids);
                $sql = "SELECT id,name,external_identity,provider FROM `identities` WHERE (`id` = {$identity_str});";
                $identities=$this->getAll($sql);
                if($identities)
                {
                    $value="";
                    foreach($identities as $identity)
                    {
                        $r_identityid=$identity["id"];
                        $name=$identity["name"];
                        $external_identity=$identity["external_identity"];
                        $provider=$identity["provider"];
                        $value = $value. "($userid,$r_identityid,'$name','$external_identity','$provider'),";
                    }
                    $value[strlen($value)-1]=";";
                    $sql="insert into user_relations (userid,r_identityid,name,external_identity,provider) values $value"; 
                    $this->query($sql);
                }
            }
            // add my_id for other's relationship

            $identity_ids = implode(' OR `identityid` = ', $identityid_list);
            $sql ="select userid from user_identity where identityid={$identity_ids}";
            $userids=$this->getColumn($sql);

            $useridlist= implode(' OR `userid` = ', $userids);
            
            $sql="select userid from user_relations where r_identityid=$my_identity_id and (userid=$useridlist)";
            $existuserids=$this->getColumn($sql);
            $newuserids=array();
            foreach($userids as $userid)
            {
                if(!in_array($userid, $existuserids))
                    array_push($newuserids,$userid);
            }
            $sql = "SELECT id,name,external_identity,provider FROM identities WHERE `id` = $my_identity_id;";
            $my_identity=$this->getRow($sql);
            if($newuserids)
            {
                $value ="";
                foreach($newuserids as $userid)
                {
                    $name=$my_identity["name"];
                    $external_identity=$my_identity["external_identity"];
                    $provider=$my_identity["provider"];
                    $value = $value. "($userid,$my_identity_id,'$name','$external_identity','$provider'),";
                }
                $value[strlen($value)-1]=";";
                $sql="insert into user_relations (userid,r_identityid,name,external_identity,provider) values $value"; 
                $this->query($sql);
            }
        }
    }

}

