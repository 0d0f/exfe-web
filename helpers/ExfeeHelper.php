<?php

class ExfeeHelper extends ActionController { 
    public function addExfeeIdentify($cross_id,$exfee_list)
    {
	//TODO: package as a transaction
	$exfees=explode(",",$exfee_list);
	foreach($exfees as $exfee)
	{
	    //TODO:parser exfee format
	   $identity_id=0; 

	    if(trim($exfee)!="")
	    {
		$identityData = $this->getModelByName("identity"); 
	    	// if exfee exist?
		//13:hj@exfe.com
		$exfee_split=explode(":",$exfee);
		if(sizeof($exfee_split)==2)
		{
		    $identity_id=intval($exfee_split[0]);
		    $identity=$exfee_split[1];
		}
		else
		{
		    $identity=$exfee;
		}
		if(intval($identity_id)==0)
		{
		   $identity_id=$identityData->ifIdentityExist($identity);
		   if($identity_id===FALSE)
	    	   {
	    	       //TODO: add new Identity, need check this identity provider, now default "email"
	    	       // add identity
	    	       $identity_id=$identityData->addIdentityWithoutUser("email",$identity);
	    	   }
		}

	    	// add invitation
	    	$invitationdata=$this->getModelByName("invitation");
	    	$invitationdata->addInvitation($cross_id,$identity_id);
	    }
	}
    }
    public function sendInvitation($cross_id)
    {
    	$invitationdata=$this->getModelByName("invitation");
    	$invitations=$invitationdata->getInvitation_Identities($cross_id);

	$crossData=$this->getModelByName("X");
	$cross=$crossData->getCross($cross_id);

	require 'lib/Resque.php';
	date_default_timezone_set('GMT');
	Resque::setBackend('127.0.0.1:6379');
	
	foreach($invitations as $invitation)
	{

	    $args = array(
	    	'title' => $cross["title"],
	    	'description' => $cross["description"],
	    	'cross_id_base62' => int_to_base62($cross_id),
	    	'invitation_id' => $invitation["invitation_id"],
	    	'token' => $invitation["token"],
	    	'identity_id' => $invitation["identity_id"],
	    	'provider' => $invitation["provider"],
	    	'external_identity' => $invitation["external_identity"],
	    	'name' => $invitation["name"],
	    	'avatar_file_name' => $invitation["avatar_file_name"]
	    );
	    
	    $jobId = Resque::enqueue($invitation["provider"],$invitation["provider"]."_job" , $args, true);
	    //echo "Queued job ".$jobId."\n\n";
	 }
    }

}
