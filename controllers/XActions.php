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
            if ($identity_id) {
                $idntdata = $this->getModelByName('identity');
                // @todo: inorder to gather X, user must be verified
                if (1) {
             // if ($idntdata->checkIdentityStatus($identity_id) === 3) {
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
                        $helper->addExfeeIdentify($cross_id, json_decode($_POST['exfee'], true), $identity_id);
                        $helper->sendInvitation($cross_id, $identity_id);

                        // remove draft
                        $XDraft = $this->getModelByName('XDraft');
                        $XDraft->delDraft($_POST['draft_id']);

                        $result = array('success' => true, 'crossid' => int_to_base62($cross_id));
                    } else {
                        $result = array('success' => false, 'error' => 'unknow');
                    }
                } else {
                    $result = array('success' => false, 'error' => 'notverified');
                }
            } else {
                $result = array('success' => false, 'error' => 'notlogin');
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
        $return_data = array('error' => 0, 'msg' => '');

        if (!$identity_id && !$_SESSION['tokenIdentity']) {
            echo json_encode(array('success' => false));
            return;
        }

        if ($_SESSION['tokenIdentity'] && $_SESSION['tokenIdentity']['token_expired']) {
            echo json_encode(array('success' => false, 'error' => 'token_expired'));
            return;
        }
        $newExfees=array();
        $allExfees=array();
        $delExfees=array();

        if (!isset($_POST['exfee_only']) || !$_POST['exfee_only']) {
            $old_cross=$crossDataObj->getCross($cross_id);
            if($old_cross)
            {
                $place_id=$old_cross["place_id"];
                if(intval($place_id)>0)
                {
                    $placeData=$this->getModelByName("place");
                    $place=$placeData->getPlace($place_id);

                    $old_cross["place_line1"]=$place["line1"];
                    $old_cross["place_line2"]=$place["line2"];
                    unset($old_cross["place_id"]);
                }
            }

            if (!array_key_exists('ctitle', $_POST) || trim($_POST['ctitle']) == ''){
                $return_data['error'] = 1;
                $return_data['msg'] = 'The title can not be empty.';

                header('Content-Type:application/json; charset=UTF-8');
                echo json_encode($return_data);
                exit();
            }

            $crossDesc = strip_tags(exPost('cdesc'));
            $placeLineOne = strip_tags(exPost('cplaceline1'));
            $placeLineTwo = strip_tags(exPost('cplaceline2'));
            $cross = array(
                'id'          => $cross_id,
                'title'       => mysql_real_escape_string(trim($_POST['ctitle'])),
                'desc'        => mysql_real_escape_string(trim($crossDesc)),
                'start_time'  => $_POST['ctime'],
                'place_line1' => mysql_real_escape_string(trim($placeLineOne)),
                'place_line2' => mysql_real_escape_string(trim($placeLineTwo)),
                'identity_id' => $identity_id
            );

            $result = $crossDataObj->updateCross($cross);
            if (!$result) {
                $return_data['error'] = 2;
                $return_data['msg'] = 'System error.';

                header('Content-Type:application/json; charset=UTF-8');
                echo json_encode($return_data);
                exit();
            }
            header('Content-Type:application/json; charset=UTF-8');
            echo json_encode($return_data);
        }

        // exclude exfee identities that already in cross
        $invitM = $this->getModelByName('invitation');
        $idents = $invitM->getIdentitiesIdsByCrossIds(array($cross_id));

        $exfees    = json_decode($_POST['exfee'], true);
        $ehelper   = $this->getHelperByName('exfee');
        $exfees_list = $ehelper->addExfeeIdentify($cross_id, $exfees, $identity_id, $idents);

        $newExfees=$exfees_list["newexfees"];
        $allExfees=$exfees_list["allexfees"];
        $delExfees=$exfees_list["delexfees"];

        $allExfee_ids=array();
        $newExfee_ids=array();
        $delExfee_ids=array();

        foreach($allExfees as $id=>$conformed)
            array_push($allExfee_ids,$id);
        foreach($newExfees as $id=>$conformed)
            array_push($newExfee_ids,$id);
        foreach($delExfees as $id=>$conformed)
            array_push($delExfee_ids,$id);

        $ehelper->sendIdentitiesInvitation($cross_id, $newExfee_ids,$allExfee_ids);


        $xhelper = $this->getHelperByName('x');

        $new_cross=$crossDataObj->getCross($cross_id);
        if($new_cross)
        {
            $place_id=$new_cross["place_id"];
            if(intval($place_id)>0)
            {
                $placeData=$this->getModelByName("place");
                $place=$placeData->getPlace($place_id);

                $new_cross["place_line1"]=$place["line1"];
                $new_cross["place_line2"]=$place["line2"];
                unset($new_cross["place_id"]);
            }
        }

        $changed = $xhelper->addCrossDiffLog($cross_id, $identity_id, $old_cross, $new_cross);
        if($newExfees || $delExfees)
            $change=true;
        if($changed != false) {
            $invitationData=$this->getModelByName("invitation");
            $invitations=$invitationData->getInvitation_Identities($cross_id,true,null,false);
            $new_cross["identities"]=$invitations;
            $xhelper->sendXChangeMsg($new_cross, $identity_id, $changed,$old_cross["title"]);
        }
        if((is_array($newExfees)==TRUE && sizeof($newExfees) >0 )||(is_array($delExfees)==TRUE && sizeof($delExfees) >0))
        {
            $identityData = $this->getModelByName('identity');
            $new_identities=$identityData->getIdentitiesByIdentityIds($newExfee_ids);
            $del_identities=$identityData->getIdentitiesByIdentityIds($delExfee_ids);
            for($idx=0;$idx<sizeof($new_identities);$idx++)
                $new_identities[$idx]["rsvp"]=$newExfees[$new_identities[$idx]["id"]];
            for($idx=0;$idx<sizeof($del_identities);$idx++)
                $del_identities[$idx]["rsvp"]=$delExfees[$del_identities[$idx]["id"]];
            
            $changed_identity["delexfees"]=$del_identities;
            $changed_identity["newexfees"]=$new_identities;
            //$allExfee_ids
            //$newExfee_ids//=array();
            //$delExfee_ids//=array();
            $invitationData=$this->getModelByName("invitation");
            $invitations=$invitationData->getInvitation_Identities($cross_id,true,null,false);
            $new_cross["identities"]=$invitations;
            $xhelper->sendXInvitationChangeMsg($cross_id,$identity_id,$changed_identity);
            //send identity invitation changes msg
        }
        exit(0);
    }

    // $_SESSION["tokenIdentity"]["token_expired"] 用来标记是否第一次打开token链接
    // 此参数setVar供view中使用
    public function doIndex()
    {
        $identity_id=0;
        $identityData=$this->getModelByName("identity");
        $base62_cross_id = $_GET["id"];
        $cross_id=base62_to_int($base62_cross_id);
        $token=$_GET["token"];

        $checkhelper=$this->getHelperByName("check");
        $check=$checkhelper->isAllow("x","index",array("cross_id"=>$cross_id,"token"=>$token));
        if ($check["allow"] == "false") {
            $referer_uri = SITE_URL."/!".$base62_cross_id;
            header('Location: /x/forbidden?s='.urlencode($referer_uri).'&x='.$cross_id);
            exit(0);
        }
        if($check["type"]=="token")
        {
            $identity_id=$identityData->loginWithXToken($cross_id, $token);

            $identityData=$this->getModelByName("user");
            $user=$identityData->getUserByIdentityId($identity_id);

            if(intval($user)==0)
            {
                $identityData->addUserByToken($cross_id,"","",$token);
            }

            if($_SESSION["identity_id"]==$identity_id)
            {
                $check["type"]="session";

                $identityData=$this->getModelByName("identity");
                $status=$identityData->checkIdentityStatus($identity_id);
                if($status!=STATUS_CONNECTED)
                {
                    $identityData->setRelation($identity_id,STATUS_CONNECTED);
                }
                header('Location: /!'.$_GET["id"]);
            }
        }
        else if($check["type"]=="session" || $check["type"]=="cookie")
            $identity_id=$_SESSION["identity_id"];
        $showlogin="";
        if($check["type"]=="token")
        {
            $this->setVar("login_type", "token");
            if(trim($user["encrypted_password"])=="")
                $showlogin= "setpassword";
            else //if($identity_id!=$_SESSION["identity_id"])
                $showlogin= "login";

            $identityData=$this->getModelByName("user");
            $user=$identityData->getUserByIdentityId($identity_id);
            if($_SESSION["tokenIdentity"]["token_expired"]=="true")
                $this->setVar("token_expired", "true");
        }
        $this->setVar("showlogin", $showlogin);
        $this->setVar("token", $_GET["token"]);

        $Data=$this->getModelByName("x");
        $cross=$Data->getCross(base62_to_int($_GET["id"]));
        $cross["title"] = htmlspecialchars($cross["title"]);
        $cross["description"] = $cross["description"];

        if($cross)
        {
            $place_id=$cross["place_id"];
            $cross_id=$cross["id"];
            if(intval($place_id)>0)
            {
                $placeData=$this->getModelByName("place");
                $place=$placeData->getPlace($place_id);
                $place["line1"]=htmlspecialchars($place["line1"]);
                $place["line2"]=htmlspecialchars($place["line2"]);
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

    public function doForbidden()
    {
        $referer = exGet("s");
        if($referer != "")
            $referer = urldecode($referer);
        $cross_id = exGet("x");
        if($cross_id != "" && intval($_SESSION["userid"]) > 0){
            $cross_id = intval($cross_id);
            $checkhelper=$this->getHelperByName("check");
            $check=$checkhelper->isAllow("x","index",array("cross_id"=>$cross_id,"token"=>""));
            if($check["allow"] != "false"){
                header("location:/!".int_to_base62($cross_id));
            }else{
                header("location:/s/profile");
            }
        }
        $this->setVar('referer', $referer);
        $this->setVar('cross_id', $cross_id);
        $this->displayView();
    }

    //检查Cross是否属于当前登录用户。
    public function doCheckforbidden(){
        $returnData = array(
            "success"   =>0,
            "msg"       =>""
        );
        $cross_id = exPost("cid");
        if($cross_id == ""){
            $returnData["msg"] = "Cross ID empty";
        }else{
            $checkhelper=$this->getHelperByName("check");
            $check=$checkhelper->isAllow("x","index",array("cross_id"=>$cross_id,"token"=>""));
            if($check["allow"] != "false"){
                $returnData["success"] = 1;
            }
        }

        header('Content-Type:application/json; charset=UTF-8');
        echo json_encode($returnData);
    }

}
