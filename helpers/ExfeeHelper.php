<?php

class ExfeeHelper extends ActionController { 
    public function addExfeeIdentify($cross_id,$exfee_list)
    {
	//TODO: package as a transaction
	$exfees=explode(",",$exfee_list);
	foreach($exfees as $exfee)
	{
	    //TODO:parser exfee format
	    

	    $identityData = $this->getModelByName("identity"); 
	    // if exfee exist?
	    $identity_id=$identityData->ifIdentityExist($exfee);
	    if($identity_id===FALSE)
	    {
		//TODO: add new Identity, need check this identity provider, now default "email"
		// add identity
		$identity_id=$identityData->addIdentityWithoutUser("email",$exfee);
	    }

	    // add invitation
	    $invitationdata=$this->getModelByName("invitation");
	    $invitationdata->addInvitation($cross_id,$identity_id);
	}
    }

}
