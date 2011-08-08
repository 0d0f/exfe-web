<?php
class InvitationModels extends DataModel{

    public function rsvp($cross_id,$identity_id,$state)
    {
	$sql="update invitations set state=$state where identity_id=$identity_id and cross_id=$cross_id;";	
    	$this->query($sql);

	$sql="select state from invitations where identity_id=$identity_id and cross_id=$cross_id;";
	$result=$this->getRow($sql);
	if(intval($result["state"])==intval($state))
	    return TRUE;
	else
	    return FALSE;
    }
    public function addInvitation($cross_id,$identity_id,$state=INVITATION_MAYBE)
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
	return $invitations;

    }
}
