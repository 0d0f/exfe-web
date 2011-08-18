<?php

class CheckHelper extends ActionController { 

function isAllow($class,$action,$args="")
{
    if(($class=='x' && $action=='index') || $class=='rsvp')
    {
	$token=$args["token"];
	$cross_id=$args["cross_id"];
	$invitationdata=$this->getModelByName("invitation");
	
	if(intval($cross_id)>0)
	{
	    if($token!="")
	    {
	        $result=$invitationdata->ifIdentityHasInvitationByToken($token,$cross_id);
		if($result===true)
		    return array("allow"=>"true","type"=>"token");
	    }
	    if(intval($_SESSION["identity_id"])>0)
	    {
	       $result=$invitationdata->ifIdentityHasInvitation($_SESSION["identity_id"],$cross_id);
		if($result===true)
		    return array("allow"=>"true","type"=>"session");
	    }
	}
        return array("allow"=>'false');
    }
}

}
