<?php

class XActions extends ActionController {

    public function doGather()
    {
        $identity_id = $_SESSION['identity_id'];
        $myidentity  = null;
        if ($_SESSION['identity']['external_identity']) {
            $myidentity = $_SESSION['identity'];
            $myidentity['identityid'] = $identity_id;
            $myidentity['external_identity'] = strtolower($myidentity['external_identity']);
        }
        $this->setVar('myidentity', $myidentity);

        if ($_POST['title']) {
            if ($identity_id) {
                $idntdata = $this->getModelByName('identity');
                // @todo: inorder to gather X, user must be verified
                if (1) {
             // if ($idntdata->checkIdentityStatus($identity_id) === 3) {}
                    $crossdata=$this->getDataModel('x');
                    $placedata=$this->getModelByName('place');

                    // @todo: package as a translaction
                    $place = json_decode($_POST['place'], true);
                    if (trim($place['line1']) !== '') {
                        $placeid = $placedata->savePlace($place);
                    } else {
                        $placeid = 0;
                    }

                    $cross = array(
                        'title'        => mysql_real_escape_string(htmlspecialchars($_POST['title'])),
                        'description'  => mysql_real_escape_string(htmlspecialchars($_POST['description'])),
                        'place_id'     => intval($placeid),
                        'datetime'     => mysql_real_escape_string(htmlspecialchars($_POST['begin_at'])),
                        'ori_datetime' => mysql_real_escape_string(htmlspecialchars($_POST['origin_begin_at'])),
                        'timezone'     => mysql_real_escape_string(htmlspecialchars($_POST['timezone'])),
                        'background'   => mysql_real_escape_string(htmlspecialchars($_POST['background'])),
                    );

                    $cross_id = $crossdata->gatherCross(
                        $identity_id, $cross,
                        json_decode($_POST['exfee'], true), mysql_real_escape_string($_POST['draft_id'])
                    );

                    if ($cross_id) {
                        $result = array('success' => true, 'crossid' => $cross_id);
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
        } else {
            $modBackground = $this->getModelByName('background');
            $this->setVar('backgrounds', $modBackground->getAllBackground());
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


    public function doCrossEdit()
    {
        $crossDataObj = $this->getDataModel('x');
        $placeData    = $this->getModelByName('place');

        $identity_id = $_SESSION['identity_id'];
        if (isset($_SESSION["tokenIdentity"]["identity_id"])) {
            $identity_id = $_SESSION["tokenIdentity"]["identity_id"];
        }

        $cross_id = intval($_GET['id']);
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
            if (($old_cross = $crossDataObj->getCross($cross_id))) {
                if (intval($old_cross["place_id"]) > 0) {
                    $old_cross["place"] = $placeData->getPlace($old_cross["place_id"]);
                    unset($old_cross["place_id"]);
                }
            }

            if (!isset($_POST['title']) || trim($_POST['title']) == ''){
                $return_data['error'] = 1;
                $return_data['msg'] = 'The title can not be empty.';

                header('Content-Type:application/json; charset=UTF-8');
                echo json_encode($return_data);
                exit();
            }

            $cross = array(
                'id'              => intval($cross_id),
                'title'           => mysql_real_escape_string(htmlspecialchars($_POST['title'])),
                'desc'            => mysql_real_escape_string(htmlspecialchars($_POST['desc'])),
                'start_time'      => mysql_real_escape_string($_POST['time']),
                'timezone'        => mysql_real_escape_string(htmlspecialchars($_POST['timezone'])),
                'origin_begin_at' => mysql_real_escape_string(htmlspecialchars($_POST['origin_begin_at'])),
                'place'           => json_decode($_POST['place'], true),
                'identity_id'     => intval($identity_id)
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

        if (($new_cross = $crossDataObj->getCross($cross_id))) {
            if(intval($new_cross["place_id"]) > 0) {
                $new_cross["place"] = $placeData->getPlace($new_cross["place_id"]);
                unset($new_cross["place_id"]);
            }
        }

        if (!isset($_POST['exfee_only']) || !$_POST['exfee_only']) {
            $changed = $xhelper->addCrossDiffLog($cross_id, $identity_id, $old_cross, $new_cross);
        }

        if ($newExfees || $delExfees) {
            $changed = true;
        }
        if ($changed != false) {
            $invitationData=$this->getModelByName("invitation");
            $invitations=$invitationData->getInvitation_Identities($cross_id,true,null,true);
            $new_cross["invitations"]=$invitations;
            $xhelper->sendXChangeMsg($new_cross, $identity_id, $changed,$new_cross["title"]);
        }
        if ((is_array($newExfees)==TRUE && sizeof($newExfees) > 0 )||(is_array($delExfees)==TRUE && sizeof($delExfees) > 0))
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
            $invitations=$invitationData->getInvitation_Identities($cross_id,true,null,true);
            $new_cross["invitations"]=$invitations;
            $xhelper->sendXInvitationChangeMsg($cross_id,$identity_id,$changed_identity,$new_cross,$new_cross["title"]);
            //send identity invitation changes msg
        }
        exit(0);
    }


    public function doIndex() {
        $this->displayView();
    }


    // $_SESSION["tokenIdentity"]["token_expired"] 用来标记是否第一次打开token链接
    // 此参数setVar供view中使用
    public function doIndexOld()
    {
        // init models
        $modIdentity   = $this->getModelByName('identity');
        $modUser       = $this->getModelByName('user');
        $modPlace      = $this->getModelByName('place');
        $modInvitation = $this->getModelByName('invitation');
        $modConversion = $this->getModelByName('conversation');
        $modData       = $this->getModelByName('x');

        // init helper
        $hlpCheck      = $this->getHelperByName('check');
        $hlpLog        = $this->getHelperByName('log');

        $identity_id   = 0;
        $cross_id      = intval($_GET['id']);
        $token = exGet('token');

        //如果是通过Token进来，且已经登录，则Session要过期。
        if($token != "" && intval($_SESSION['userid'])>0){
            $modUser->doDestroySessionAndCookies();
        }

        if (intval($cross_id) > 0) {
            $result = $modData->checkCrossExists($cross_id);
            if ($result == NULL) {
                header('location:/error/404?e=theMissingCross');
                exit;
            }
        } else {
            header('location:/error/404?e=theMissingCross');
        }

        $check = $hlpCheck->isAllow('x', 'index', array('cross_id' => $cross_id, 'token' => $token));
        if ($check['allow'] === 'false') {
            $referer_uri = SITE_URL . "/!{$cross_id}";
            header('Location: /x/forbidden?s=' . urlencode($referer_uri) . "&x={$cross_id}");
            exit(0);
        }
        if ($check['type'] === 'token') {
            $identity = $modIdentity->loginWithXToken($cross_id, $token);
            $identity_id = $identity['identity_id'];
            $user = $modUser->getUserByIdentityId($identity_id);
            if (intval($user) === 0) {
                $modUser->addUserByToken($cross_id, $identity['name'], $token);
            }
            if ($_SESSION['identity_id'] === $identity_id) {
                $check['type'] = 'session';
                $status        = $modIdentity->checkIdentityStatus($identity_id);
                if ($status !== STATUS_CONNECTED) {
                    $modIdentity->setRelation($identity_id, STATUS_CONNECTED);
                }
                header("Location: /!{$_GET['id']}");
            }
        } else if ($check['type'] === 'session' || $check['type'] === 'cookie') {
            $identity_id = $_SESSION['identity_id'];
        }
        $showlogin = '';
        if ($check['type'] === 'token') {
            $this->setVar('login_type', 'token');
            if (trim($user['encrypted_password']) === '') {
                $showlogin = 'setpassword';
            } else { // if ($identity_id !== $_SESSION['identity_id'])
                $showlogin = 'login';
            }
            $user = $modUser->getUserByIdentityId($identity_id);
            if ($_SESSION['tokenIdentity']['token_expired'] === 'true') {
                $this->setVar('token_expired', 'true');
            }
        }

        // Get token
        $user_token = $user && $user['id'] ? $modUser->getAuthToken($user['id']) : '';
        $this->setVar('user_token', $user_token);

        $this->setVar('showlogin', $showlogin);
        $this->setVar('token', $_GET['token']);

        $cross = $modData->getCross($cross_id);
        $cross['title'] = htmlspecialchars($cross['title']);
        $cross['description'] = $cross['description'];

        if ($cross) {
            $place_id = $cross['place_id'];
            $cross_id = $cross['id'];
            if (intval($place_id) > 0) {
                $place = $modPlace->getPlace($place_id);
                $place['line1'] = htmlspecialchars($place['line1']);
                $place['line2'] = htmlspecialchars($place['line2']);
            } else {
                $place['line1'] = '';
                $place['line2'] = '';
            }
            $cross['place'] = $place;
            $invitations = $modInvitation->getInvitation_Identities($cross_id);

            if (intval($_SESSION['userid']) > 0) {
                $user = $modUser->getUser($_SESSION['userid']);
                $this->setVar('user', $user);
                $myidentities = $modIdentity->getIdentitiesIdsByUser($_SESSION['userid']);
            } else {
                $myidentities = array($identity_id);
            }
            $myidentity = $modIdentity->getIdentityById($identity_id);
            $myidentity['external_identity'] = strtolower($myidentity['external_identity']);

            $humanMyIdentity = humanIdentity($myidentity, $user);
            $this->setVar('myidentity', $humanMyIdentity);

            if ($invitations) {
                foreach ($invitations as $idx => $invitation) {
                    if (in_array($invitation['identity_id'], $myidentities)) {
                        $this->setVar('myrsvp', $invitation['state']);
                        if (intval($invitation['state']) > 0) {
                            $this->setVar('interested', 'yes');
                        }
                    }
                    unset($invitations[$idx]['token']);
                    $invitations[$idx]['host'] = $invitation['identity_id'] === $cross['host_id'];
                }
            }
            $cross['exfee'] = $invitations;

            $cross['conversation'] = $modConversion->getConversation($cross_id, 'cross');

            $history = $hlpLog->getMergedXUpdate($_SESSION['userid'], $cross_id, date('Y-m-d H:i:s', time() - 60*60*24*7));
            foreach ($history as $hI => $hItem) {
                foreach ($hItem as $hItemI => $hItemItem) {
                    $scrap = false;
                    switch ($hItemI) {
                        case 'change_dna':
                        case 'meta':
                            $scrap = true;
                            break;
                        default:
                            $scrap = substr($hItemI, 0, 2) === 'x_';
                    }
                    if ($scrap) {
                        unset($history[$hI][$hItemI]);
                    }
                }
            }
            $cross['history'] = $history;

            $this->setVar('cross', $cross);
            $this->displayView();
        }
    }


    public function doForbidden()
    {
        $referer = exGet("s");
        if($referer != ""){
            $referer = urldecode($referer);
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
