<?php
class XActions extends ActionController {

    public function doGather()
    {
        $identity_id=$_SESSION["identity_id"];
        $external_identity=$_SESSION["identity"]["external_identity"];

        if ($external_identity!="") {
            $this->setVar("external_identity", $external_identity);
        }

        if ($_POST["title"]!="") {
            $crossdata=$this->getDataModel("x");
            $placedata=$this->getModelByName("place");

            //TODO: package as a translaction
            if(trim($_POST["place"])!="")
                $placeid=$placedata->savePlace($_POST["place"]);
            else
                $placeid=0;

            $_POST["place_id"]=$placeid;
            $cross_id = $crossdata->gatherCross($identity_id, $_POST);

            $helper=$this->getHelperByName("exfee");
            $helper->addExfeeIdentify($cross_id,$_POST["exfee_list"]);
            $helper->sendInvitation($cross_id);

            //TODO: redirect to this exfe page
            $cross_id_base62 = int_to_base62($cross_id);
            header( 'Location: /!' . $cross_id_base62 ) ;
            exit(0);
        }
        $this->displayView();
    }


    public function doSaveDraft()
    {
        $identity_id = $_SESSION['identity_id'];

        if (!$identity_id) {
            return;
        }

        $cross  = json_decode($_POST['cross'], true);
        $XDraft = $this->getModelByName('XDraft');
        $XDraft->saveDraft($identity_id, $cross['title'], $_POST['cross']);
    }


    public function doGetDraft()
    {
        $identity_id = $_SESSION['identity_id'];

        $XDraft = $this->getModelByName('XDraft');
        echo $identity_id ? $XDraft->getDraft($identity_id) : json_encode(null);
    }


    // $_SESSION["tokenIdentity"]["token_expired"] 用来标记是否第一次打开token链接
    // 此参数setVar供view中使用
    public function doIndex()
    {
        $identity_id=0;
        $identityData=$this->getModelByName("identity");
        $cross_id=base62_to_int($_GET["id"]);
        $token=$_GET["token"];

        $checkhelper=$this->getHelperByName("check");
        $check=$checkhelper->isAllow("x","index",array("cross_id"=>$cross_id,"token"=>$token));
        if($check["allow"]=="false")
        {
            header( 'Location: /s/login' ) ;
            exit(0);
        }
        if($check["type"]=="token")
        {
            $identity_id=$identityData->loginWithXToken($cross_id, $token);
            if($_SESSION["identity_id"]==$identity_id)
                $check["type"]="session";

            $status=$identityData->checkIdentityStatus($identity_id);
            if($status!=STATUS_CONNECTED)
            {
                $identityData->setRelation($identity_id,STATUS_CONNECTED);
            }
        }
        else if($check["type"]=="session" || $check["type"]=="cookie")
            $identity_id=$_SESSION["identity_id"];
        $showlogin="";
        if($check["type"]=="token")
        {
            $identityData=$this->getModelByName("user");
            $user=$identityData->getUserByIdentityId($identity_id);
            if($_SESSION["tokenIdentity"]["token_expired"]=="true")
            {
                if(trim($user["encrypted_password"])=="")
                    $showlogin= "setpassword";
                //if user password="" then show set password box
                //else show login
                else if($identity_id!=$_SESSION["identity_id"])
                    $showlogin= "login";
                $this->setVar("token_expired", "true");

            }

            //$tokenSession["token_expired"]="true";
            //               $_SESSION["tokenIdentity"]=$tokenSession;

        }
        $this->setVar("showlogin", $showlogin);
        $this->setVar("token", $_GET["token"]);

        $Data=$this->getModelByName("x");
        $cross=$Data->getCross(base62_to_int($_GET["id"]));
        if($cross)
        {
            $place_id=$cross["place_id"];
            $cross_id=$cross["id"];
            if(intval($place_id)>0)
            {
                $placeData=$this->getModelByName("place");
                $place=$placeData->getPlace($place_id);
                $cross["place"]=$place;
            }
            $invitationData=$this->getModelByName("invitation");
            $invitations=$invitationData->getInvitation_Identities($cross_id);
            $identityData=$this->getModelByName("identity");

            if(intval($_SESSION["userid"])>0)
            {
                $userData = $this->getModelByName("user");
                $user=$userData->getUser($_SESSION["userid"]);
                $this->setVar("user", $user);

                $myidentities=$identityData->getIdentitiesIdsByUser($_SESSION["userid"]);
            }
            else
            {
                $myidentities=array($identity_id);
            }
            $myidentity=$identityData->getIdentityById($identity_id);

            $humanMyIdentity=humanIdentity($myidentity,$user);
            $this->setVar("myidentity", $humanMyIdentity);

            $host_exfee=array();
            $normal_exfee=array();

            if($invitations)
                foreach ($invitations as $invitation)
                {
                    if(in_array($invitation["identity_id"],$myidentities)==true)
                    {
                        $this->setVar("myrsvp",$invitation["state"]);
                        if(intval($invitation["state"])>0)
                            $this->setVar("interested","yes");
                    }
                    //$invitation["identity_id"]
                    // $invitation["state"];
                    if ($invitation["identity_id"]==$cross["host_id"])
                        array_push($host_exfee,$invitation);
                    else
                        array_push($normal_exfee,$invitation);
                }

            $cross["host_exfee"]=$host_exfee;
            $cross["normal_exfee"]=$normal_exfee;

            $ConversionData=$this->getModelByName("conversation");
            $conversationPosts=$ConversionData->getConversion(base62_to_int($_GET["id"]),'cross');
            $cross["conversation"]=$conversationPosts;

            $this->setVar("cross", $cross);
            $this->displayView();
        }
    }
}
