<?php

class InvitationModels extends DataModel
{

    public function rsvpIdentities($cross_id,$identity_id_list,$state,$userid)
    {

        for($i=0;$i<sizeof($identity_id_list);$i++)
        {
            $identity_id_list[$i]= "a.identity_id=".$identity_id_list[$i];
        }
        $str=implode(" or ",$identity_id_list);

        $sql="update invitations a set state=$state where ($str) and cross_id=$cross_id;";
        $this->query($sql);


        $sql="select a.id invitation_id, a.state ,a.updated_at ,b.id identity_id,b.provider, b.external_identity, b.name, b.bio,b.avatar_file_name,b.external_username  FROM invitations a,identities b where b.id=a.identity_id and a.cross_id=$cross_id and ($str)";

        $invitations=$this->getAll($sql);
        for($i=0;$i<sizeof($invitations);$i++)
        {
            if(trim($invitations[$i]["name"])==""  ||  trim($invitations[$i]["b.avatar_file_name"])=="")
            {
                if(intval($userid)>0)
                {
                    $indentity_id=$invitations[$i]["identity_id"];
                    $sql="select name,avatar_file_name from users  where id=$userid";
                    $user=$this->getRow($sql);
                    $invitations[$i]=humanIdentity($invitations[$i],$user);
                }
                else
                    $invitations[$i]=humanIdentity($invitations[$i],array());
            }
                $invitations[$i]["user_id"]=intval($userid);
                $invitations[$i]["state"]=intval($invitations[$i]["state"]);
        }
        return $invitations;


        //$sql="select by_identity_id,created_at,cross_id,id,identity_id,lat,lng,state,updated_at,via from invitations where ($str) and cross_id=$cross_id;";
        //$result=$this->getAll($sql);
        //return $result;
    }

    public function rsvp($cross_id,$identity_id,$state)
    {
        $time=time();
        $sql="update invitations set state=$state,updated_at=FROM_UNIXTIME($time) where identity_id=$identity_id and cross_id=$cross_id;";
        $this->query($sql);

        //$sql="update invitations set  where cross_id=$cross_id and token='$token';";
        //$this->query($sql);

        $sql="select id,state,tokenexpired from invitations where identity_id=$identity_id and cross_id=$cross_id;";
        $result=$this->getRow($sql);
        if(intval($result["state"])===intval($state))
            $result["success"]=1;
        else
            $result["success"]=0;
        return $result;
    }

    public function addInvitation($cross_id,$identity_id,$state=0,$my_identity_id=0)
    {
        //TODO: ADD token
        $time=time();
        $token=md5(base64_encode(pack('N6', mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand(), uniqid())));
        //$state=INVITATION_MAYBE;
        $sql="insert into invitations (identity_id,cross_id,state,by_identity_id,created_at,updated_at,token) values($identity_id,$cross_id,$state,$my_identity_id,FROM_UNIXTIME($time),FROM_UNIXTIME($time),'$token');";
        $result=$this->query($sql);
        if(intval($result["insert_id"])>0)
            return intval($result["insert_id"]);
    }

    public function delInvitation($cross_id, $identity_id)
    {
        $sql="DELETE FROM `invitations` WHERE `cross_id` = '{$cross_id}' AND `identity_id` = {$identity_id};";
        return $this->query($sql);
    }

    public function getInvitatedIdentityByUseridAndCrossList($userid,$cross_ids)
    {
        $sql="select identityid from user_identity where userid=$userid;";
        $identity_id_list=$this->getColumn($sql);
        for($i=0;$i<sizeof($identity_id_list);$i++)
        {
            $identity_id_list[$i]= "identity_id=".$identity_id_list[$i];
        }
        $str=implode(" or ",$identity_id_list);

        $crossstr="";
        if(sizeof($cross_ids)==1)
            $crossstr="cross_id=$cross_ids[0]";
        else if(sizeof($cross_ids)>1)
        {
            for($i=0;$i<sizeof($cross_ids);$i++)
            {
                $cross_ids[$i]= "cross_id=".$cross_ids[$i];
            }
            $crossstr=implode(" or ",$cross_ids);
        }
        //SELECT * FROM `invitations` WHERE (cross_id=1 or cross_id=2 or cross_id=3) and (identity_id=1 or identity_id=3)
        $sql="select cross_id,identity_id from invitations where ($crossstr) and ($str);";
        $identity_id_list=$this->getAll($sql);
        return $identity_id_list;
    }

    public function getInvitatedIdentityByUserid($userid,$cross_id)
    {

        $sql="select identityid from user_identity where userid=$userid;";
        $identity_id_list=$this->getColumn($sql);
        for($i=0;$i<sizeof($identity_id_list);$i++)
        {
            $identity_id_list[$i]= "identity_id=".$identity_id_list[$i];
        }
        $str=implode(" or ",$identity_id_list);

        $sql="select identity_id from invitations where cross_id=$cross_id and ($str);";
        $identity_id_list=$this->getColumn($sql);
        return $identity_id_list;
    }

    public function getInvitation_Identities($cross_id, $without_token=false, $filter=null, $withAllIdentities = true)
    {
        $sql="select a.id invitation_id, a.state, a.by_identity_id, a.token,a.updated_at ,b.id identity_id,b.provider, b.external_identity, b.name, b.bio,b.avatar_file_name,b.external_username  FROM invitations a,identities b where b.id=a.identity_id and a.cross_id=$cross_id";
        if($without_token==true)
            $sql="select a.id invitation_id, a.state, a.by_identity_id, a.updated_at ,b.id identity_id,b.provider, b.external_identity, b.name, b.bio,b.avatar_file_name,b.external_username FROM invitations a,identities b where b.id=a.identity_id and a.cross_id=$cross_id";

        $invitations=$this->getAll($sql);
        if (is_array($arrFilter)) {
            foreach ($invitations as $invitationI => $invitationItem) {
                if (in_array($invitationItem['identity_id'], $filter)) {
                    unset($invitations[$invitationI]);
                }
            }
        }

        for($i=0;$i<sizeof($invitations);$i++)
        {
            if(trim($invitations[$i]["name"])=="" || trim($invitations[$i]["b.avatar_file_name"])=="")
            {
                $indentity_id=$invitations[$i]["identity_id"];
                $sql="select name,avatar_file_name,userid from users,user_identity where users.id=user_identity.userid and user_identity.identityid=$indentity_id";
                $user=$this->getRow($sql);
                $invitations[$i]=humanIdentity($invitations[$i],$user);

                $userid=$user["userid"];
                if(intval($userid)>0 && $withAllIdentities==true)
                {
                    $sql="select b.id identity_id,a.status,b.provider, b.external_identity, b.name, b.bio,b.avatar_file_name,b.external_username FROM user_identity a,identities b where  a.identityId=b.id and a.userId=$userid; ";
                    $identities=$this->getAll($sql);
                    $invitations[$i]["identities"]=$identities;

                }
            }
            $invitations[$i]["user_id"]=intval($userid);
            $invitations[$i]["state"]=intval($invitations[$i]["state"]);
            // fixed empty external_identity
            if (!$invitations[$i]['external_identity']) {
                switch ($invitations[$i]['provider']) {
                    case 'twitter':
                        $invitations[$i]['external_identity'] = "@{$invitations[$i]['external_username']}@twitter";
                }
            }
        }
        return $invitations;
    }

    public function getInvitation_Identities_ByIdentities($cross_id, $identities_id_list,$without_token=false, $filter=null)
    {
        $id_list=array();
        for($i=0;$i<sizeof($identities_id_list);$i++)
        {
            if(intval($identities_id_list[$i])>0)
                array_push($id_list, "identity_id=".$identities_id_list[$i]);
        }

        if(sizeof($id_list)>0)
        {
            $identities_sql="(";
            $identities_sql.=implode(" or ",$id_list);
            $identities_sql.=")";
            //(identity_id=1 or identity_id=13);
            $sql="select a.id invitation_id, a.state ,a.token,a.updated_at, a.by_identity_id,b.id identity_id,b.provider, b.external_identity, b.name, b.bio,b.avatar_file_name,b.external_username  FROM invitations a,identities b where b.id=a.identity_id and a.cross_id=$cross_id and $identities_sql";
            if($without_token==true)
                $sql="select a.id invitation_id, a.state ,a.updated_at, a.by_identity_id, b.id identity_id,b.provider, b.external_identity, b.name, b.bio,b.avatar_file_name,b.external_username FROM invitations a,identities b where b.id=a.identity_id and a.cross_id=$cross_id";

            $invitations=$this->getAll($sql);

            if (is_array($arrFilter)) {
                foreach ($invitations as $invitationI => $invitationItem) {
                    if (in_array($invitationItem['identity_id'], $filter)) {
                        unset($invitations[$invitationI]);
                    }
                }
            }

            for($i=0;$i<sizeof($invitations);$i++)
            {
                if(trim($invitations[$i]["name"])=="" || trim($invitations[$i]["b.avatar_file_name"])=="")
                {
                    $indentity_id=$invitations[$i]["identity_id"];
                    $sql="select name,avatar_file_name,userid from users,user_identity where users.id=user_identity.userid and user_identity.identityid=$indentity_id";
                    $user=$this->getRow($sql);
                    $invitations[$i]=humanIdentity($invitations[$i],$user);

                    $userid=$user["userid"];
                    if(intval($userid)>0)
                    {
                        //$sql="select b.id identity_id,b.status,b.provider, b.external_identity, b.name, b.bio,b.avatar_file_name,b.external_username  FROM user_identity a,identities b where  a.identityId=b.id and a.userId=$userid; ";
                        $sql="select b.id identity_id,a.status,b.provider, b.external_identity, b.name, b.bio,b.avatar_file_name,b.external_username FROM user_identity a,identities b where  a.identityId=b.id and a.userId=$userid; ";
                        $identities=$this->getAll($sql);
                        $invitations[$i]["identities"]=$identities;

                    }
                }
                $invitations[$i]["user_id"]=intval($userid);
                $invitations[$i]["state"]=intval($invitations[$i]["state"]);
            }
        }
        return $invitations;
    }

    public function getInvitation_APNIdentities($cross_id)
    {

    }

    public function ifIdentityHasInvitation($identity_id,$cross_id)
    {
        $sql="select id from invitations where identity_id=$identity_id and cross_id=$cross_id;";
        $row=$this->getRow($sql);
        if(intval($row["id"])>0){
            return true;
        }
        return false;
    }

    public function ifUserHasInvitation($user_id, $cross_id)
    {
        $returnResult = false;
        $sql = "SELECT identityid FROM user_identity WHERE userid={$user_id}";
        $identityArr = $this->getAll($sql);
        $sql = "SELECT identity_id FROM invitations WHERE cross_id={$cross_id}";
        $crossIdentity = $this->getAll($sql);

        foreach($identityArr as $v){
            foreach($crossIdentity as $vv){
                if($v["identityid"] == $vv["identity_id"]){
                    $returnResult = true;
                }
            }
        }
        return $returnResult;
    }

    public function ifIdentityHasInvitationByToken($token,$cross_id)
    {
        $sql="select id,tokenexpired from invitations where token='$token' and  cross_id=$cross_id;";
        $row=$this->getRow($sql);
        if($row && intval($row["tokenexpired"])<2) {
            return array("allow"=>"true","tokenexpired"=>"false");
        } else if ($row && intval($row["tokenexpired"])>=2) {
            return array("allow"=>"true","tokenexpired"=>"true");
        }
        return array("allow"=>"false");
    }
    public function ifIdentityHasInvitationByIdentity($identity,$cross_id)
    {
        $sql="select id from identities where external_identity='$identity';";
        $row=$this->getRow($sql);
        if(intval($row["id"])>0 && intval($cross_id)>0)
        {
            $identity_id=intval($row["id"]);
            $sql="select id from invitations where identity_id=$identity_id and  cross_id=$cross_id;";
            $row=$this->getRow($sql);
            if(intval($row["id"])>0)
                return $identity_id;
        }
        return false;
    }

    public function getIdentitiesIdsByCrossIds($cross_ids)
    {
        if ($cross_ids) {
            $cross_ids = implode(' OR `cross_id` = ', $cross_ids);
            $sql       = "SELECT `identity_id`, `cross_id`, `state` FROM `invitations` WHERE `cross_id` = {$cross_ids};";
            return $this->getAll($sql);
        } else {
            return array();
        }
    }

    public function getNewInvitationsByIdentityIds($identity_ids, $limit = 10)
    {
        $identity_ids = implode(' OR `identity_id` = ', $identity_ids);
        $sql = "SELECT * FROM `invitations` WHERE (`identity_id` = {$identity_ids}) AND `state` = 0 ORDER by `updated_at` DESC LIMIT {$limit};";
        return $this->getAll($sql) ?: array();
    }
    public function getYESInvitationsByCrossId($cross_id,$my_identity_id)
    {
        $sql="SELECT identity_id FROM invitations WHERE cross_id=$cross_id AND state=".INVITATION_YES." AND identity_id<>$my_identity_id;";
        $result=$this->getColumn($sql);
        return $result;

    }

}
