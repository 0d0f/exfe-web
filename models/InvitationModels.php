<?php
class InvitationModels extends DataModel{

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
                $invitations[$i]["state"]=intval($invitations[$i]["state"]);
        }
        return $invitations;


        //$sql="select by_identity_id,created_at,cross_id,id,identity_id,lat,lng,state,updated_at,via from invitations where ($str) and cross_id=$cross_id;";
        //$result=$this->getAll($sql);
        //return $result;
    }

    public function rsvp($cross_id,$identity_id,$state)
    {
        $sql="update invitations set state=$state where identity_id=$identity_id and cross_id=$cross_id;";
        $this->query($sql);

        $sql="select state from invitations where identity_id=$identity_id and cross_id=$cross_id;";
        $result=$this->getRow($sql);
        if(intval($result["state"])==intval($state))
            return true;
        else
            return false;
    }

    public function addInvitation($cross_id,$identity_id,$state=0)
    {
        //TODO: ADD token
        $time=time();
        $token=md5(base64_encode(pack('N6', mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand(), uniqid())));
        //$state=INVITATION_MAYBE;
        $sql="insert into invitations (identity_id,cross_id,state,created_at,updated_at,token) values($identity_id,$cross_id,$state,FROM_UNIXTIME($time),FROM_UNIXTIME($time),'$token')";
        $result=$this->query($sql);
        if(intval($result["insert_id"])>0)
            return intval($result["insert_id"]);

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
        //get my invitations
        //find cross_id
        //if (intval($updated_since)==0)
        //    $sql="select distinct cross_id from invitations where  ($str)  order by created_at limit 50";
        //else
        //    $sql="select distinct cross_id from invitations where  ($str) and created_at>FROM_UNIXTIME($updated_since) order by created_at limit 50";
    }

    public function getInvitation_Identities($cross_id,$without_token=false)
    {
        $sql="select a.id invitation_id, a.state ,a.token,a.updated_at ,b.id identity_id,b.provider, b.external_identity, b.name, b.bio,b.avatar_file_name,b.external_username  FROM invitations a,identities b where b.id=a.identity_id and a.cross_id=$cross_id";
        if($without_token==true)
            $sql="select a.id invitation_id, a.state ,a.updated_at ,b.id identity_id,b.provider, b.external_identity, b.name, b.bio,b.avatar_file_name,b.external_username  FROM invitations a,identities b where b.id=a.identity_id and a.cross_id=$cross_id";

        $invitations=$this->getAll($sql);
        for($i=0;$i<sizeof($invitations);$i++)
        {
            if(trim($invitations[$i]["name"])==""  ||  trim($invitations[$i]["b.avatar_file_name"])=="")
            {
                $indentity_id=$invitations[$i]["identity_id"];
                $sql="select name,avatar_file_name from users,user_identity where users.id=user_identity.userid and user_identity.identityid=$indentity_id";
                $user=$this->getRow($sql);
                $invitations[$i]=humanIdentity($invitations[$i],$user);
                #if(trim($invitations[$i]["name"])=="" )
                #    $invitations[$i]["name"]=$user["name"];
                #if(trim($invitations[$i]["avatar_file_name"])=="")
                #    $invitations[$i]["avatar_file_name"]=$user["avatar_file_name"];
            }
                $invitations[$i]["state"]=intval($invitations[$i]["state"]);
        }
        return $invitations;
    }

    public function ifIdentityHasInvitation($identity_id,$cross_id)
    {
        $sql="select id from invitations where identity_id=$identity_id and cross_id=$cross_id;";
        $row=$this->getRow($sql);
        if(intval($row["id"])>0)
            return true;

        return false;
    }

    public function ifIdentityHasInvitationByToken($token,$cross_id)
    {
        $sql="select id from invitations where token='$token' and  cross_id=$cross_id;";
        $row=$this->getRow($sql);
        if(intval($row["id"])>0)
            return true;

        return false;
    }

    public function getConfirmedIdentityIdsByCrossIds($cross_ids)
    {
        if ($cross_ids) {
            $cross_ids = implode(' OR `cross_id` = ', $cross_ids);
            $sql       = "SELECT `identity_id`, `cross_id` FROM `invitations` WHERE (`cross_id` = {$cross_ids}) AND `state` = 1;";
            return $this->getAll($sql);
        } else {
            return array();
        }
    }

}
