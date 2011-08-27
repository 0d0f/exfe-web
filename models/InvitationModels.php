<?php
class InvitationModels extends DataModel{

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
    
    public function getInvitation_Identities($cross_id)
    {
	$sql="select a.id invitation_id, a.state ,a.token,a.updated_at ,b.id identity_id,b.provider, b.external_identity, b.name, b.bio,b.avatar_file_name,b.external_username  FROM invitations a,identities b   where b.id=a.identity_id and a.cross_id=$cross_id";
    	$invitations=$this->getAll($sql);
	for($i=0;$i<sizeof($invitations);$i++)
	{
	    if(trim($invitations[$i]["name"])==""  ||  trim($invitations[$i]["b.avatar_file_name"])=="")
	    {
		$indentity_id=$invitations[$i]["identity_id"];
		$sql="select name,avatar_file_name from users,user_identity where users.id=user_identity.userid and user_identity.identityid=$indentity_id";
		$user=$this->getRow($sql);
		if(trim($invitations[$i]["name"])=="" )
		    $invitations[$i]["name"]=$user["name"];
		if(trim($invitations[$i]["avatar_file_name"])=="")
		    $invitations[$i]["avatar_file_name"]=$user["avatar_file_name"];
	    }
	}
	return $invitations;

    }
    public function ifIdentityHasInvitation($identity_id,$cross_id)
    {
	$sql="select id from invitations where identity_id=$identity_id and  cross_id=$cross_id;";
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
}
