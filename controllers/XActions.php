<?php

class XActions extends ActionController
{

    public function doGather()
    {
        $identity_id = $_SESSION['identity_id'];
        $external_identity = $_SESSION['identity']['external_identity'];

        if ($external_identity !== '') {
            $this->setVar('external_identity', $external_identity);
        }

        if ($_POST['title']) {
            $crossdata=$this->getDataModel('x');
            $placedata=$this->getModelByName('place');

            // @todo: package as a translaction
            if (trim($_POST['place']) !== '') {
                $placeid=$placedata->savePlace($_POST['place']);
            } else {
                $placeid = 0;
            }

            $cross = array(
                'title'       => mysql_real_escape_string($_POST['title']),
                'description' => mysql_real_escape_string($_POST['description']),
                'place_id'    => $placeid,
                'datetime'    => $_POST['datetime']
            );

            $cross_id = $crossdata->gatherCross($identity_id, $cross);

            if ($cross_id) {
                $logdata = $this->getModelByName('log');
                $logdata->addLog('identity', $identity_id, 'gather', 'cross', $cross_id, '', $_POST['title'], '');

                $helper = $this->getHelperByName('exfee');
                $helper->addExfeeIdentify($cross_id, json_decode($_POST['exfee'], true));
                $helper->sendInvitation($cross_id, $identity_id);

                // remove draft
                $XDraft = $this->getModelByName('XDraft');
                $XDraft->delDraft($_POST['draft_id']);

                $result = array('success' => true, 'crossid' => int_to_base62($cross_id));
            } else {
                $result = array('success' => false);
            }
            echo json_encode($result);
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
        $result = $XDraft->saveDraft($identity_id, $cross['title'], $_POST['cross'], $_POST['draft_id'] ?: null);
        echo json_encode(array('draft_id' => $result));
    }

    public function doGetDraft()
    {
        $identity_id = $_SESSION['identity_id'];

        $XDraft = $this->getModelByName('XDraft');
        echo $identity_id && $_POST['draft_id'] ? $XDraft->getDraft($identity_id, $_POST['draft_id']) : json_encode(null);
    }

    //编辑Cross功能。
    public function doCrossEdit()
    {
        $crossDataObj = $this->getDataModel('x');

        $identity_id = $_SESSION['identity_id'];
        $cross_id = base62_to_int($_GET['id']);
        $old_cross=$crossDataObj->getCross($cross_id);
        #if($old_cross)
        #{
        #    $place_id=$old_cross["place_id"];
        #    if(intval($place_id)>0)
        #    {
        #        $placeData=$this->getModelByName("place");
        #        $place=$placeData->getPlace($place_id);
        #        $old_cross["place"]=$place;
        #    }
        #}

        $return_data = array("error"=>0,"msg"=>"");
        if(!array_key_exists("ctitle", $_POST) || trim($_POST["ctitle"]) == ""){
            $return_data["error"] = 1;
            $return_data["msg"] = "The title can not be empty.";

            header("Content-Type:application/json; charset=UTF-8");
            echo json_encode($return_data);
            exit();
        }

        $cross = array(
                "id"          => $cross_id,
                "title"       => mysql_real_escape_string($_POST["ctitle"]),
                "desc"        => mysql_real_escape_string($_POST["cdesc"]),
                "start_time"  => $_POST['ctime'],
                "place"       => mysql_real_escape_string($_POST["cplace"]),
                "identity_id" => $identity_id
        );

        $result = $crossDataObj->updateCross($cross);
        if(!$result){
            $return_data["error"] = 2;
            $return_data["msg"] = "System error.";

            header("Content-Type:application/json; charset=UTF-8");
            echo json_encode($return_data);
            exit(0);
        }

        $xhelper=$this->getHelperByName("x");
        $changed=$xhelper->addCrossDiffLog($cross_id, $identity_id, $old_cross, $cross);
        if($changed!=FALSE)
            $xhelper->sendXChangeMsg($cross_id,$identity_id,$changed,$old_cross);

        // exclude exfee identities that already in cross
        $invitM = $this->getModelByName('invitation');
        $idents = $invitM->getIdentitiesIdsByCrossIds(array($cross_id));

        $exfees    = json_decode($_POST["exfee"], true);
        $ehelper   = $this->getHelperByName("exfee");
        $newExfees = $ehelper->addExfeeIdentify($cross_id, $exfees, $idents);

        $ehelper->sendIdentitiesInvitation($cross_id, $newExfees);

        header("Content-Type:application/json; charset=UTF-8");
        echo json_encode($return_data);
        exit(0);
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
                $this->setVar("login_type", "token");

            }
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
            $conversationPosts=$ConversionData->getConversation(base62_to_int($_GET["id"]),'cross');
            $cross["conversation"]=$conversationPosts;

            $this->setVar("cross", $cross);
            $this->displayView();
        }
    }
}
