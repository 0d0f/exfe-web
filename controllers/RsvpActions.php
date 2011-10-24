<?php

class RSVPActions extends ActionController {

    public function checkallow($cross_id,$token)
    {
        $checkhelper=$this->getHelperByName("check");
        $check=$checkhelper->isAllow("rsvp","",array("cross_id"=>$cross_id,"token"=>$token));
        if($check["allow"]=="false")
        {
            header( 'Location: /s/login' ) ;
            exit(0);
        }
        if($check["type"]=="token")
        {
            $identityData=$this->getModelByName("identity");
            $identity_id=$identityData->loginWithXToken($cross_id, $token);
            $status=$identityData->checkIdentityStatus($identity_id);
            if($status!=STATUS_CONNECTED)
            {
                $identityData->setRelation($identity_id,STATUS_CONNECTED);
            }
        }
        else if($check["type"]=="session")
            $identity_id=$_SESSION["identity_id"];
        return $identity_id;
    }

    public function doSave()
    {
        $rsvp=$_POST["rsvp"];
        $cross_id=$_POST["cross_id"];
        $token=$_POST["token"];

        switch ($rsvp) {
            case 'yes':
                $state = INVITATION_YES;
                break;
            case 'no':
                $state = INVITATION_NO;
                break;
            case 'maybe':
                $state = INVITATION_MAYBE;
        }
        $responobj["meta"]["code"]=200;

        $checkhelper=$this->getHelperByName("check");
        $check=$checkhelper->isAllow("rsvp","",array("cross_id"=>$cross_id,"token"=>$token));
        if(($check["allow"]!="false" && $check["tokenexpired"]=="false") ||($check["allow"]!="false" && $check["tokenexpired"]=="") )
        {
            $identity_id=$_SESSION["tokenIdentity"]["identity_id"];
            if(intval($identity_id)==0)
                $identity_id=$_SESSION["identity_id"];
            if(intval($state)>0 && intval($identity_id)>0 )
            {
                $responobj["response"]["identity_id"]=$identity_id;
                $responobj["response"]["state"]=$rsvp;

                $r=$this->save($cross_id,$identity_id,$state);

                if(intval($r["success"])==1)
                    $responobj["response"]["success"]="true";
                else
                    $responobj["response"]["success"]="false";
                $responobj["response"]["token_expired"]=$r["tokenexpired"];
            }
            else
                $responobj["response"]["success"]="false";
        }
        else
        {
                $responobj["response"]["success"]="false";
                $responobj["response"]["error"]="need login";
        }
        echo json_encode($responobj);
        exit();

    }
    public function save($cross_id,$identity_id,$state)
    {
        $invitationData=$this->getModelByName("invitation");
        $r=$invitationData->rsvp($cross_id,$identity_id,$state);
        //if($state==INVITATION_YES)
        //{
        //    //if(intval($_SESSION['userid'])>0)
        //    //{
        //    //    $userid=intval($_SESSION['userid']);
        //    //    $identityData=$this->getModelByName("identity");
        //    //    $belong=$identityData->ifIdentityIdBelongsUser($identity_id,$userid); // conformed himself status
        //    //    if(intval($belong)>0)
        //    //    {
        //    //        $relationData=$this->getHelperByName("relation");
        //    //        $relationData->saveRelationsByXInvitation($userid,$identity_id,$cross_id);
        //    //    }

        //    //}
        //}

        $logdata=$this->getModelByName("log");
        $logdata->addLog('identity', $identity_id, 'rsvp', 'cross', $cross_id, '', $state);
        return $r;
    }

    public function doYES()
    {
        $cross_id=intval($_GET["id"]);
        $cross_id_base62=int_to_base62($cross_id);
        $token=$_GET["token"];
        $state=INVITATION_YES;

        $checkhelper=$this->getHelperByName("check");
        $check=$checkhelper->isAllow("rsvp","",array("cross_id"=>$cross_id,"token"=>$token));
        if($check["allow"]!="false")
        {
            $identity_id=$_SESSION["tokenIdentity"]["identity_id"];
            if(intval($identity_id)==0)
                $identity_id=$_SESSION["identity_id"];
            if(intval($state)>0 && intval($identity_id)>0 )
            {
                $this->save($cross_id,$identity_id,$state);
            }
        }

       if($token!="")
           header( "Location: /!$cross_id_base62?token=$token" ) ;
       else
           header( "Location: /!$cross_id_base62" ) ;

        exit(0);
        #$cross_id=intval($_GET["id"]);
        #$token=$_GET["token"];

        #$identity_id=$this->checkallow($cross_id,$token);

        #if(intval($identity_id)>0)
        #{
        #    $state=INVITATION_YES;
        #    $invitationData=$this->getModelByName("Invitation");
        #    $invitationData->rsvp($cross_id,$identity_id,$state);
        #    $cross_id_base62=int_to_base62($cross_id);
        #}
        #if($token!="")
        #    header( "Location: /!$cross_id_base62?token=$token" ) ;
        #else
        #    header( "Location: /!$cross_id_base62" ) ;
        #exit(0);
    }

    public function doNO()
    {

        $cross_id=intval($_GET["id"]);
        $cross_id_base62=int_to_base62($cross_id);
        $token=$_GET["token"];
        $state=INVITATION_NO;

        $identity_id=$this->checkallow($cross_id,$token);

        if(intval($identity_id)>0)
        {
        //$checkhelper=$this->getHelperByName("check");
        //$check=$checkhelper->isAllow("rsvp","",array("cross_id"=>$cross_id,"token"=>$token));
        //if($check["allow"]!="false")
        //{
            $identity_id=$_SESSION["tokenIdentity"]["identity_id"];
            if(intval($identity_id)==0)
                $identity_id=$_SESSION["identity_id"];
            if(intval($state)>0 && intval($identity_id)>0 )
            {
                $this->save($cross_id,$identity_id,$state);
            }
        }

       if($token!="")
           header( "Location: /!$cross_id_base62?token=$token" ) ;
       else
           header( "Location: /!$cross_id_base62" ) ;

        exit(0);
        #$identity_id=$this->checkallow($cross_id,$token);

        #if(intval($identity_id)>0)
        #{
        #    $state=INVITATION_NO;
        #    $invitationData=$this->getModelByName("Invitation");
        #    $invitationData->rsvp($cross_id,$identity_id,$state);
        #    $cross_id_base62=int_to_base62($cross_id);
        #    if($token!="")
        #        header( "Location: /!$cross_id_base62?token=$token" ) ;
        #    else
        #        header( "Location: /!$cross_id_base62" ) ;
        #}
        #exit(0);
    }

    public function doMaybe()
    {
        $cross_id=intval($_GET["id"]);
        $cross_id_base62=int_to_base62($cross_id);
        $token=$_GET["token"];
        $state=INVITATION_MAYBE;

        $checkhelper=$this->getHelperByName("check");
        $check=$checkhelper->isAllow("rsvp","",array("cross_id"=>$cross_id,"token"=>$token));
        if($check["allow"]!="false")
        {
            $identity_id=$_SESSION["tokenIdentity"]["identity_id"];
            if(intval($identity_id)==0)
                $identity_id=$_SESSION["identity_id"];
            if(intval($state)>0 && intval($identity_id)>0 )
            {
                $this->save($cross_id,$identity_id,$state);
            }
        }

       if($token!="")
           header( "Location: /!$cross_id_base62?token=$token" ) ;
       else
           header( "Location: /!$cross_id_base62" ) ;

        exit(0);

        #$cross_id=intval($_GET["id"]);
        #$token=$_GET["token"];

        #$identity_id=$this->checkallow($cross_id,$token);

        #if(intval($identity_id)>0)
        #{
        #    $state=INVITATION_MAYBE;
        #    $invitationData=$this->getModelByName("Invitation");
        #    $invitationData->rsvp($cross_id,$identity_id,$state);
        #    $cross_id_base62=int_to_base62($cross_id);
        #    if($token!="")
        #        header( "Location: /!$cross_id_base62?token=$token" ) ;
        #    else
        #        header( "Location: /!$cross_id_base62" ) ;
        #}
        #exit(0);
    }

    public function doAccept()
    {
        $invitationData = $this->getModelByName("Invitation");

        if (intval($_SESSION['userid']) > 0
         && intval($identity_id = $_SESSION["identity_id"]) > 0
         && ($cross_id = base62_to_int($_GET["xid"]))
         && $invitationData->rsvp($cross_id, $identity_id, INVITATION_YES) === true) {
            header("Location: /s/profile");
        } else {
            header('Location: /s/login');
        }

        exit(0);
    }

}
