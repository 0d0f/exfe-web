<?php

class SActions extends ActionController {

    public function doProfile() {
        $modOauth = $this->getModelByName('OAuth', 'v2');
        $oauthIfo = $modOauth->getSession();
        if ($oauthIfo && $oauthIfo['signin']) {
            $this->setVar('user_id',    $oauthIfo['signin']['user_id']);
            $this->setVar('user_token', $oauthIfo['signin']['new_user_token']);
            $modOauth->resetSession();
        }
        $this->displayView();
    }


    public function doGetInvitation() {
        if (intval($_SESSION['userid']) <= 0) {
            echo json_encode(array('error' => 'forbidden'));
            exit(0);
        }

        // init models
        $modIvit     = $this->getModelByName('invitation');
        $modIdentity = $this->getModelByName('identity');
        $modUser     = $this->getModelByName('user');
        $modCross    = $this->getModelByName('x');

        // Get identity ids
        $identities  = $modIdentity->getIdentitiesByUser($_SESSION['userid']);
        $identityIds = array();
        foreach ($identities as $idI => $idItem) {
            array_push($identityIds, $idItem['id']);
        }

        // Get invitations
        $newInvt = $modIvit->getNewInvitationsByIdentityIds($identityIds);
        foreach ($newInvt as $newInvtI => $newInvtItem) {
            $newInvt[$newInvtI]['sender'] = humanIdentity(
                $modIdentity->getIdentityById($newInvtItem['by_identity_id']),
                $modUser->getUserByIdentityId($newInvtItem['by_identity_id'])
            );
            if (intval($newInvtItem['cross_id'])) {
                $newInvt[$newInvtI]['cross'] = $modCross->getCross(
                    $newInvtItem['cross_id']
                );
            } else {
                unset($newInvt[$newInvtI]);
            }
        }

        echo json_encode($newInvt);
    }


    public function doGetCross() {
        if (intval($_SESSION['userid']) <= 0) {
            echo json_encode(array('error' => 'forbidden'));
            exit(0);
        }

        // init models
        $modIvit     = $this->getModelByName('invitation');
        $modIdentity = $this->getModelByName('identity');
        $modUser     = $this->getModelByName('user');
        $modCross    = $this->getModelByName('x');

        // get crosses
        $today     = strtotime(date('Y-m-d'));
        $upcoming  = $today + 60 * 60 * 24 * 3;
        $sevenDays = $today + 60 * 60 * 24 * 7;
        $fetchArgs = array(
            'upcoming_included'  => isset($_GET['upcoming_included'])  && $_GET['upcoming_included']  === 'false' ? 0 : 1,
            'upcoming_folded'    => isset($_GET['upcoming_folded'])    && $_GET['upcoming_folded']    === 'true'  ? 1 : 0,
            'upcoming_more'      => isset($_GET['upcoming_more'])      && $_GET['upcoming_more']      === 'false' ? 0 : 1,
            'anytime_included'   => isset($_GET['anytime_included'])   && $_GET['anytime_included']   === 'false' ? 0 : 1,
            'anytime_folded'     => isset($_GET['anytime_folded'])     && $_GET['anytime_folded']     === 'true'  ? 1 : 0,
            'anytime_more'       => isset($_GET['anytime_more'])       && $_GET['anytime_more']       === 'true'  ? 1 : 0,
            'sevenDays_included' => isset($_GET['sevenDays_included']) && $_GET['sevenDays_included'] === 'false' ? 0 : 1,
            'sevenDays_folded'   => isset($_GET['sevenDays_folded'])   && $_GET['sevenDays_folded']   === 'true'  ? 1 : 0,
            'sevenDays_more'     => isset($_GET['sevenDays_more'])     && $_GET['sevenDays_more']     === 'true'  ? 1 : 0,
            'later_included'     => isset($_GET['later_included'])     && $_GET['later_included']     === 'false' ? 0 : 1,
            'later_folded'       => isset($_GET['later_folded'])       && $_GET['later_folded']       === 'true'  ? 1 : 0,
            'later_more'         => isset($_GET['later_more'])         && $_GET['later_more']         === 'true'  ? 1 : 0,
            'past_included'      => isset($_GET['past_included'])      && $_GET['past_included']      === 'false' ? 0 : 1,
            'past_folded'        => isset($_GET['past_folded'])        && $_GET['past_folded']        === 'true'  ? 1 : 0,
            'past_more'          => isset($_GET['past_more'])          && $_GET['past_more']          === 'true'  ? 1 : 0,
        );
        $fetchArgs['past_quantity'] = 0;
        if (isset($_GET['past_quantity'])) {
            $fetchArgs['past_quantity'] = intval($_GET['past_quantity']);
        }
        if ($fetchArgs['upcoming_included']
         || $fetchArgs['sevenDays_included']
         || $fetchArgs['later_included']) {
            $futureXs  = $modCross->fetchCross($_SESSION['userid'], $today,
                                               'yes', '`begin_at` DESC');
        }
        if ($fetchArgs['past_included']) {
            $pastXs    = $modCross->fetchCross($_SESSION['userid'], $today,
                                               'no',  '`begin_at` DESC');
        }
        if ($fetchArgs['anytime_included']) {
            $anytimeXs = $modCross->fetchCross($_SESSION['userid'], 0,
                                               'anytime', '`created_at` DESC');
        }

        // sort crosses
        $crosses   = array();
        $xShowing  = 0;
        $maxCross  = 20;
        $minCross  = 3;
        // sort upcoming crosses
        if ($fetchArgs['upcoming_included']
         || $fetchArgs['sevenDays_included']
         || $fetchArgs['later_included']) {
            foreach ($futureXs as $crossI => $crossItem) {
                $futureXs[$crossI]['timestamp']
              = strtotime($crossItem['begin_at']);
                if (!$fetchArgs['upcoming_included']) {
                    continue;
                }
                if ($futureXs[$crossI]['timestamp'] < $upcoming) {
                    $futureXs[$crossI]['sort'] = 'upcoming';
                    array_push($crosses, $futureXs[$crossI]);
                    $xShowing += !$fetchArgs['upcoming_folded'] ? 1 : 0;
                    unset($futureXs[$crossI]);
                }
            }
        }
        // sort anytime crosses
        if ($fetchArgs['anytime_included']) {
            $xQuantity = !$fetchArgs['anytime_more'] && $xShowing >= $maxCross
                       ? $minCross : 0;
            $iQuantity = 0;
            $enough    = false;
            foreach ($anytimeXs as $crossItem) {
                if ($enough) {
                    array_push($crosses,
                               array('sort'=>'anytime', 'more'=>true));
                    continue;
                }
                $crossItem['sort'] = 'anytime';
                array_push($crosses, $crossItem);
                $xShowing += !$fetchArgs['anytime_folded'] ? 1 : 0;
                if ($xQuantity && ++$iQuantity >= $xQuantity) {
                    $enough = true;
                }
            }
            unset($anytimeXs);
        }
        // sort next-seven-days cross
        if ($fetchArgs['sevenDays_included']) {
            $xQuantity = !$fetchArgs['sevenDays_more'] && $xShowing >= $maxCross
                       ? $minCross : 0;
            $iQuantity = 0;
            $enough    = false;
            foreach ($futureXs as $crossI => $crossItem) {
                if ($crossItem['timestamp'] >= $upcoming
                 && $crossItem['timestamp'] <  $sevenDays) {
                    if ($enough) {
                        array_push($crosses,
                                   array('sort'=>'sevenDays', 'more'=>true));
                        continue;
                    }
                    $crossItem['sort'] = 'sevenDays';
                    array_push($crosses, $crossItem);
                    $xShowing += !$fetchArgs['sevenDays_folded'] ? 1 : 0;
                    unset($futureXs[$crossI]);
                    if ($xQuantity && ++$iQuantity >= $xQuantity) {
                        $enough = true;
                    }
                }
            }
        }
        // sort later cross
        if ($fetchArgs['later_included']) {
            $xQuantity = !$fetchArgs['later_more'] && $xShowing >= $maxCross
                       ? $minCross : 0;
            $iQuantity = 0;
            $enough    = false;
            foreach ($futureXs as $crossItem) {
                if ($crossItem['timestamp'] >= $sevenDays) {
                    if ($enough) {
                        array_push($crosses,
                                   array('sort'=>'later', 'more'=>true));
                        continue;
                    }
                    $crossItem['sort'] = 'later';
                    array_push($crosses, $crossItem);
                    $xShowing += !$fetchArgs['later_folded'] ? 1 : 0;
                    unset($futureXs[$crossI]);
                    if ($xQuantity && ++$iQuantity >= $xQuantity) {
                        $enough = true;
                    }
                }
            }
        }
        unset($futureXs);
        // sort past cross
        if ($fetchArgs['past_included']) {
            $xQuantity = $fetchArgs['past_more']
                       ? $maxCross
                       : ($xShowing >= $maxCross ? $minCross : 0);
            $iQuantity = 0;
            $enough    = false;
            foreach ($pastXs as $crossItem) {
                if ($fetchArgs['past_quantity']-- > 0) {
                    continue;
                }
                if ($enough) {
                    array_push($crosses,
                               array('sort'=>'past', 'more'=>true));
                    continue;
                }
                $crossItem['sort'] = 'past';
                array_push($crosses, $crossItem);
                $xShowing += !$fetchArgs['past_folded'] ? 1 : 0;
                if ($xQuantity && ++$iQuantity >= $xQuantity) {
                    $enough = true;
                }
            }
            unset($pastXs);
        }

        // get confirmed informations
        $crossIds = array();
        foreach ($crosses as $crossI => $crossItem) {
            if ($crossItem['id'] !== null) {
                array_push($crossIds, $crossItem['id']);
            }
        }
        $cfedInfo = $modIvit->getIdentitiesIdsByCrossIds($crossIds);

        // get related identities
        $relatedIdentityIds = array();
        foreach ($cfedInfo as $cfedInfoI => $cfedInfoItem) {
            $relatedIdentityIds[$cfedInfoItem['identity_id']] = true;
        }
        $relatedIdentities = $modIdentity->getIdentitiesByIdentityIds(
            array_keys($relatedIdentityIds)
        ) ?: array();

        // get human identities
        $humanIdentities = array();
        foreach ($relatedIdentities as $ridI => $ridItem) {
            $user = $modUser->getUserByIdentityId($ridItem['identity_id']);
            $humanIdentities[$ridItem['id']] = humanIdentity($ridItem, $user);
            //unset($humanIdentities[$ridItem['id']]['activecode']);
        }

        // Add informations into crosses
        foreach ($crosses as $crossI => $crossItem) {
            $crosses[$crossI]['begin_at'] = array(
                'begin_at'        => $crosses[$crossI]['begin_at'],
                'time_type'       => $crosses[$crossI]['time_type'],
                'timezone'        => $crosses[$crossI]['timezone'],
                'origin_begin_at' => $crosses[$crossI]['origin_begin_at'],
            );
            $crosses[$crossI]['exfee']     = array();
            foreach ($cfedInfo as $cfedInfoI => $cfedInfoItem) {
                if ($cfedInfoItem['cross_id'] === $crossItem['id']) {
                    $exfe = $humanIdentities[$cfedInfoItem['identity_id']];
                    $exfe['rsvp'] = $cfedInfoItem['state'];
                    array_push($crosses[$crossI]['exfee'], $exfe);
                }
            }
        }

        echo json_encode($crosses);
    }


    public function doGetUpdate() {
        if (intval($_SESSION['userid']) <= 0) {
            echo json_encode(array('error' => 'forbidden'));
            exit(0);
        }
        $loghelper = $this->getHelperByName('log');
        $rawLogs = $loghelper->getXUpdate($_SESSION['userid'], 'all', urldecode($_GET['updated_since']));

        // clean logs
        $loged   = array();
        foreach ($rawLogs as $logI => $logItem) {
            switch ($logItem['action']) {
                case 'title':
                case 'description':
                case 'begin_at':
                case 'place':
                    if (isset($loged[$logItem['change_dna']])) {
                        unset($rawLogs[$logI]);
                    } else {
                        $loged[$logItem['change_dna']] = $logI;
                    }
                    break;
                case 'conversation':
                    break;
                case 'addexfee':
                case 'delexfee':
                case 'confirmed':
                case 'declined':
                    if (isset($loged[$logItem['change_dna']])) {
                        $rawLogs[$loged[$logItem['change_dna']]]['conflict']
                    = (($rawLogs[$loged[$logItem['change_dna']]]['action'] === 'addexfee'
                     && $logItem['action']                                 === 'delexfee')
                    || ($rawLogs[$loged[$logItem['change_dna']]]['action'] === 'delexfee'
                     && $logItem['action']                                 === 'addexfee'))
                   || (($rawLogs[$loged[$logItem['change_dna']]]['action'] === 'confirmed'
                     && $logItem['action']                                 === 'declined')
                    || ($rawLogs[$loged[$logItem['change_dna']]]['action'] === 'declined'
                     && $logItem['action']                                 === 'confirmed'));
                        unset($rawLogs[$logI]);
                    } else {
                        $loged[$logItem['change_dna']] = $logI;
                    }
                    break;
                default:
                    unset($rawLogs[$logI]);
            }
        }
        foreach ($rawLogs as $logI => $logItem) {
            if ($logItem['conflict']) {
                unset($rawLogs[$logI]);
            }
        }

        // merge logs
        $cleanLogs = array();
        foreach ($rawLogs as $logI => $logItem) {
            if (!isset($cleanLogs[$xId = $logItem['x_id']])) {
                $cleanLogs[$xId] = array(
                    'id'       => $xId,
                    'title'    => $logItem['x_title'],
                );
            }
            $action = $logItem['action'];
            unset($logItem['action']);
            unset($logItem['change_dna']);
            unset($logItem['x_id']);
            unset($logItem['x_title']);
            unset($logItem['x_description']);
            unset($logItem['x_begin_at']);
            unset($logItem['x_time_type']);
            unset($logItem['x_host_identity']);
            unset($logItem['x_place']);
            unset($logItem['log_id']);
            unset($logItem['meta']);
            switch ($action) {
                case 'title':
                case 'description':
                case 'begin_at':
                case 'place':
                    if (!isset($cleanLogs[$xId]['change'])) {
                        $cleanLogs[$xId]['change'] = array();
                    }
                    $cleanLogs[$xId]['change'][$action] = $logItem;
                    break;
                case 'conversation':
                case 'addexfee':
                case 'delexfee':
                case 'confirmed':
                case 'declined':
                    if (!isset($cleanLogs[$xId][$action])) {
                        $cleanLogs[$xId][$action] = array();
                    }
                    array_push($cleanLogs[$xId][$action], $logItem);
            }
        }

        echo json_encode(array_merge($cleanLogs));
    }


    /**
     * check user login status.
     *
     * */
    public function doCheckUserLogin()
    {
        $returnData = array(
            "user_status"       =>0,
            "user_name"         =>"",
            "user_avatar"       =>"",
            "cross_num"         =>0,
            "crosses"           =>"",
            "identity"          =>null,
        );

        if($_SESSION["tokenIdentity"] != "" && $_GET["token"] != ""){
            $global_name=$_SESSION["tokenIdentity"]["identity"]["name"];
            $global_avatar_file_name=$_SESSION["tokenIdentity"]["identity"]["avatar_file_name"];
            $global_external_identity=$_SESSION["tokenIdentity"]["identity"]["external_identity"];
            $global_identity_id=$_SESSION["tokenIdentity"]["identity_id"];
        }else if($_SESSION["identity"] != "") {
            $global_name=$_SESSION["identity"]["name"];
            $global_avatar_file_name=$_SESSION["identity"]["avatar_file_name"];
            $global_external_identity=$_SESSION["identity"]["external_identity"];
            $global_identity_id=$_SESSION["identity_id"];
        } else {
            $indentityData=$this->getModelByName("identity");
            $login_status = $indentityData->loginByCookie("ajax");

            $global_name=$_SESSION["identity"]["name"];
            $global_avatar_file_name=$_SESSION["identity"]["avatar_file_name"];
            $global_external_identity=$_SESSION["identity"]["external_identity"];
            $global_identity_id=$_SESSION["identity_id"];
        }

        if (intval($_SESSION["userid"]) > 0) {
            $userData = $this->getModelByName("user");
            $user=$userData->getUserWithPasswd($_SESSION["userid"]);
            //display user name.
            $global_name=$user["name"];
            $global_avatar_file_name=$user["avatar_file_name"];

            $returnData["no_password"] = !$user['encrypted_password'];
            $returnData["user_status"] = 1;
            $returnData["user_name"]   = $global_name;
            $returnData["user_avatar"] = $global_avatar_file_name;
            $returnData['identity']    = $_SESSION["identity"];
        }
        //Get user panel data(User Crosses)
        $today     = strtotime(date('Y-m-d'));
        $tgnow     = time() + 60 * 60 * 2;
        $tg24hr    = $today + 60 * 60 * 24;
        $upcoming  = $today + 60 * 60 * 24 * 3;
        $crossdata = $this->getModelByName('x');
        $crosses_number = $crossdata->fetchCross($_SESSION['userid'], 0, 'yes', 'begin_at', 1000, 'count');
        $returnData["cross_num"] = $crosses_number;

        $crosses = $crossdata->fetchCross($_SESSION['userid'], $today, 'yes', 'begin_at', 3, 'simple');

        foreach ($crosses as $k=>$v) {
            $crosses[$k]['timestamp'] = strtotime($v['begin_at']);
            if ($crosses[$k]['timestamp'] < $tgnow) {
                $crosses[$k]['sort'] = 'now';
            } else if ($crosses[$k]['timestamp'] < $tg24hr) {
                $crosses[$k]['sort'] = '24hr';
            } else {
                $crosses[$k]['sort'] = 'upcoming';
            }
        }
        $returnData["crosses"] = $crosses;

        header("Content-Type:application/json; charset=UTF-8");
        echo json_encode($returnData);
        exit();
    }


    public function doEditUserIdentityName()
    {
        $returnData = array(
            "error" => 0,
            "msg"   =>"",
            "response" => array()
        );
        header("Content-Type:application/json; charset=UTF-8");

        $userIdentityName = trim(exPost("identity_name"));
        $userIdentity = trim(exPost("identity"));
        $userIdentityProvider = trim(exPost("identity_provider"));

        if($userIdentityName == ""){
            $returnData["error"] = 1;
            $returnData["msg"] = "user name empty.";
            echo json_encode($returnData);
            exit();
        }

        $userID = intval($_SESSION["userid"]);
        if ($userID > 0)
        {
            $identityDataObj = $this->getModelByName("identity");
            $result = $identityDataObj->updateUserIdentityName($userIdentityName,$userIdentity,$userIdentityProvider);
            $returnData["response"]["identity_name"] = $userIdentityName;
        }

        echo json_encode($returnData);
        exit();
    }


    public function doDialogaddidentity()
    {
        $identity=$_POST["identity"];
        $password=$_POST["password"];
        //$repassword=$_POST["retypepassword"];
        $displayname=$_POST["displayname"];
        $autosignin=$_POST["auto_signin"];
        if(intval($autosignin)==1)
            $autosignin=true;

        if(isset($identity) && isset($password) && isset($displayname) )
        {
            $identityData = $this->getModelByName("identity");
            $exist=$identityData->ifIdentityExist($identity);
            if($exist===FALSE)
            {
                $Data = $this->getModelByName("user");
                $userid = $Data->AddUser($password);
                $identityData = $this->getModelByName("identity");
                $provider= $_POST["provider"];
                if($provider==""){
                    $provider="email";
                }
                $identity_id=$identityData->addIdentity($userid,$provider,$identity,array("name"=>$displayname));
                $userid=$identityData->login($identity,$password,$autosignin);
                if(intval($userid)>0)
                {
                    $responobj["response"]["success"]="true";
                    $responobj["response"]["userid"]=$userid;
                    $responobj["response"]["identity_id"]=$identity_id;
                    $responobj["response"]["identity"]=$identity;
                    echo json_encode($responobj);
                    exit();
                }
            }
        }
        $responobj["response"]["success"]="false";
        echo json_encode($responobj);
        exit();
    }


    public function doDialoglogin()
    {
        $identity=$_POST["identity"];
        $password=$_POST["password"];
        $autosignin=$_POST["auto_signin"];
        if(intval($autosignin)==1){
            $autosignin=true;
        }
        if(isset($identity) && isset($password))
        {
            $identityModelObj=$this->getModelByName("identity");

            $identityArrayInfo = explode("@", $identity);
            if(count($identityArrayInfo) < 2){
                $responobj["response"]["success"]="false";
                echo json_encode($responobj);
                exit();
            }

            $currentDomain = $identityArrayInfo[1];
            $specialIdentity = $identityArrayInfo[0];
            if(in_array($currentDomain, $this->specialDomain)){
                if($currentDomain == "google"){
                    $specialIdentity .= "@gmail.com";
                }
                $identityArr = array("provider"=>$currentDomain,"ex_username"=>$specialIdentity);
                $userInfo = $identityModelObj->login($identityArr,$password,$autosignin,false,true);
            } else {
                $userInfo = $identityModelObj->login($identity,$password,$autosignin);
            }

            if(is_array($userInfo)) {
                $responobj["response"]["success"]="true";
                $responobj["response"]["user_info"]=$userInfo;
            } else {
                $responobj["response"]["success"]="false";
            }
        } else {
            $responobj["response"]["success"]="false";
        }
        echo json_encode($responobj);
        exit();
    }


    public function doSetpwd($userPassword, $userDisplayName, $crossID, $crossToken)
    {
        if(strlen($crossToken)>32){
            $crossToken = substr($crossToken,0,32);
        }
        $userData=$this->getModelByName("user");
        $result=$userData->setPasswordByToken($crossID,$crossToken,$userPassword,$userDisplayName);
        if(intval($result["uid"])>0 && intval($result["identity_id"])>0)
        {

            $identity_id=intval($result["identity_id"]);
            $uid=intval($result["uid"]);

            $identityData=$this->getModelByName("identity");
            $userid=$identityData->loginByIdentityId($identity_id,$uid);

            $identity = $identityData->getIdentityById($identity_id);

            // sent welcome email
            $msghelper=$this->getHelperByName("msg");
            $args=array("name"=>$userDisplayName,"external_identity"=>$identity["external_identity"]);
            if($userDisplayName=="")
                $args["name"]=$identity["external_identity"];
            $msghelper->sentWelcomeEmail($args);

            if($userid>0)
                return array("uid"=>$userid);
        }

        return false;
    }


    /**
     * 重新设置密码。
     *
     **/
    public function doResetPassword()
    {
        $actions = exGet("act");
        if($actions == ""){
            $token = exGet("token");
            if($token == ""){
                header("location:/s/linkInvalid");
                exit;
            }
            $userInfo = unpackArray($token);
            if(!is_array($userInfo)){
                header("location:/s/linkInvalid");
                exit;
            }
            $userToken = $userInfo["user_token"];
            $userId = $userInfo["user_id"];
            $userIdentity = $userInfo["user_identity"];
            //验证Token是否过期。
            $tokenTimeStamp = substr($userToken, 32);
            $curTimeStamp = time();
            if(intval($tokenTimeStamp)+5*24*60*60 < $curTimeStamp){
                header("location:/s/linkInvalid");
                exit;
            }

            //验证当前重置密码操作是否有效。
            $userDataObj = $this->getModelByName("user");
            $result = $userDataObj->verifyResetPassword($userId, $userToken);

            if(is_array($result)){
                $this->setVar("userIdentity", $userIdentity);
                $this->setVar("userName", $result["name"]);
                $this->setVar("userToken", $token);
                $this->displayView();
            }else{
                header("location:/s/linkInvalid");
            }
        }else{
            //do update password.
            $returnData = array(
                "error" => 0,
                "msg"   =>""
            );
            $userPassword = exPost("u_pwd");
            $userDisplayName = mysql_real_escape_string(exPost("u_dname"));
            if($userPassword == ""){
                $returnData["error"] = 1;
                $returnData["msg"] = "must set password";
                header("Content-Type:application/json; charset=UTF-8");
                echo json_encode($returnData);
                exit();
            }
            if($userDisplayName == ""){
                $returnData["error"] = 1;
                $returnData["msg"] = "must set display name";
                header("Content-Type:application/json; charset=UTF-8");
                echo json_encode($returnData);
                exit();
            }


            if($actions == "resetpwd"){
                $token = exPost("u_token");
                $userInfo = unpackArray($token);
                $userID = $userInfo["user_id"];
                $userToken = $userInfo["user_token"];
                $identityID = $userInfo["identity_id"];
                $userIdentity = $userInfo["identity"];

                //检查Token是否过期。
                $tokenTimeStamp = substr($userToken, 32);
                $curTimeStamp = time();
                if(intval($tokenTimeStamp)+5*24*60*60 < $curTimeStamp){
                    $returnData["error"] = 1;
                    $returnData["msg"] = "Token is expired.";
                    header("Content-Type:application/json; charset=UTF-8");
                    echo json_encode($returnData);
                    exit();
                }


                $userDataObj = $this->getModelByName("user");
                $result = $userDataObj->doResetUserPassword($userPassword, $userDisplayName, $userID, $identityID, $userToken);

                if(!$result["result"]){
                    $returnData["error"] = 1;
                    $returnData["msg"] = "System Error.";
                    echo json_encode($returnData);
                    exit();
                }

                $identityData = $this->getModelByName("identity");
                $identityData->login($userIdentity,$userPassword,"true");

                if($result["newuser"])
                {
                    $msghelper=$this->getHelperByName("msg");
                    $identity = $identityData->getIdentity(mysql_real_escape_string($userIdentity), "email");

                    $args=array("name"=>$userDisplayName,"external_identity"=>$identity["external_identity"]);

                    if($args["name"]==""){
                        $args["name"]=$identity["external_identity"];
                    }

                    $msghelper->sentWelcomeEmail($args);
                }
            }
            if($actions == "setpwd"){
                $crossID = exPost("c_id");
                $crossToken = exPost("c_token");
                $result = $this->doSetpwd($userPassword, $userDisplayName, $crossID, $crossToken);

                if($result==false){
                    $returnData["error"] = 1;
                    $returnData["msg"] = "System Error.";
                } else {
                    $returnData["uid"]=$result["uid"];
                    $returnData["cross_id"]=$crossID;
                }
            }
            header("Content-Type:application/json; charset=UTF-8");
            echo json_encode($returnData);
        }
    }


    public function doSetOAuthAccountPassword(){
        $returnData = array(
            "error"     => 0,
            "msg"       =>""
        );
        header("Content-Type:application/json; charset=UTF-8");
        $userIdentity = exPost("u_identity");
        $userDisplayName = exPost("u_dname");
        $userProvider = exPost("u_provider");
        $userNewPassword = exPost("u_passwd");
        if($userDisplayName == ""){
            $returnData["error"] = 1;
            $returnData["msg"] = "Display Name cannot be empty.";
            echo json_encode($returnData);
            exit();
        }
        if($userNewPassword == ""){
            $returnData["error"] = 1;
            $returnData["msg"] = "Password cannot be empty.";
            echo json_encode($returnData);
            exit();
        }
        if($userProvider == ""){
            $returnData["error"] = 1;
            $returnData["msg"] = "user provider cannot be empty.";
            echo json_encode($returnData);
            exit();
        }

        $userID = intval($_SESSION["userid"]);
        if($userID <= 0) {
            $returnData["error"] = 1;
            $returnData["msg"] = "Please login first.";
            echo json_encode($returnData);
            exit();
        }

        $identityObj = $this->getModelByName("identity");

        $identityInfo = $identityObj->getIdentity(mysql_real_escape_string($userIdentity), mysql_real_escape_string($userProvider));
        if(is_array($identityInfo)){
            if($identityInfo["userid"] != $userID){
                $returnData["error"] = 1;
                $returnData["msg"] = "Identity error.";
                echo json_encode($returnData);
                exit();
            }
        }

        $userDataObj = $this->getModelByName("user");
        $result = $userDataObj->doSetOAuthAccountPassword($userNewPassword, $userDisplayName, $userID);

        echo json_encode($returnData);
        exit();
    }


    public function doVerifyIdentity(){
        $userToken = exGet("token");
        if($userToken == ""){
            header("location:/s/linkInvalid");
        }else{
            $identityInfo = unpackArray($userToken);
            //如果Token串有问题。
            if(!is_array($identityInfo)){
                header("location:/s/linkInvalid");
                exit;
            }
            $identityID = $identityInfo["identityid"];
            $activeCode = $identityInfo["activecode"];
            //要先判断ActiveCode是否过期。
            $activeCodeTS = substr($activeCode, 32);
            $curTimeStamp = time();
            if(intval($activeCodeTS)+5*24*60*60 < $curTimeStamp){
                header("location:/s/linkInvalid");
                exit;
            }
            $identityHandler = $this->getModelByName("identity");
            $result = $identityHandler->verifyIdentity($identityID, $activeCode);

            if($result["status"] == "ok"){
                if($result["need_set_pwd"] == "no") {
                    $identityHandler->login($result["identity"], $result["password"], true, true);
                }else{
                    $resetPasswordTokenArr = array(
                        "user_id"       =>$result["user_id"],
                        "identity_id"   =>$result["identity_id"],
                        "identity"      =>$result["identity"],
                        "user_token"    =>$result["reset_pwd_token"]
                    );
                    $resetPasswordToken = packArray($resetPasswordTokenArr);
                }
                unset($result["password"]);
                unset($result["user_id"]);
                unset($result["identity_id"]);
                $result["reset_pwd_token"] = $resetPasswordToken;
                $this->setVar("identityInfo", $result);
                $this->displayView();
            }else{
                header("location:/s/linkInvalid");
            }
        }
    }


    /**
     * 发送验证邮件。
     * */
    public function doSendVerifyingMail(){
        $returnData = array(
            "error"     => 1,
            "msg"       =>"",
            "identity"  =>""
        );

        $userIdentity = exPost("identity");
        if($userIdentity == ""){
            $returnData["msg"] = "User Identity is empty";
        }else{
            $identityHandler = $this->getModelByName("identity");
            $result = $identityHandler->getIdentity(mysql_real_escape_string($userIdentity), 'email');
            $identityID = intval($result["id"]);
            if($identityID > 0){
                //要检查User是否存在。
                $checkResult = $identityHandler->checkUserByIdentityID($identityID);
                if(!$checkResult){
                    $modUser = $this->getModelByName('user');
                    $modUser->addUserByIdentityId($identityID, $result["name"]);
                }

                $userName = $identityHandler->getUserNameByIdentityId($identityID);
                $activeCode = $identityHandler->getVerifyingCode($identityID);

                $tokenArray = array(
                    "actions"           =>"verifyIdentity",
                    "identityid"        =>$identityID,
                    "activecode"        =>$activeCode
                );
                $verifyingToken = packArray($tokenArray);
                $args = array(
                    "external_identity"     =>$result["external_identity"],
                    "name"                  =>$result["name"],
                    "user_name"             =>$userName,
                    "avatar_file_name"      =>$result["avatar_file_name"],
                    "token"                 =>$verifyingToken
                );
                //print_r($args);
                //echo $verifyingToken;
                //exit;
                $helperHandler=$this->getHelperByName("identity");
                $jobId=$helperHandler->sentVerifyingEmail($args);
                if($jobId == "") {
                    $returnData["msg"] = "Send mail error.";
                    $returnData["identity"] = $result["external_identity"];
                }else{
                    $returnData["error"] = 0;
                }
            }else{
                $returnData["msg"] = "Get identity error.";
            }
        }
        header("Content-Type:application/json; charset=UTF-8");
        echo json_encode($returnData);
    }


    public function doReportSpam() {
        $token = exGet("token");
        if($token == ""){
            header("location:/s/linkInvalid");
            exit;
        }
        $reportInfo = unpackArray($token);
        //如果Token串有问题。
        if(!is_array($reportInfo)){
            header("location:/s/linkInvalid");
            exit;
        }
        //如果是身份验证邮件的ReportSpam
        if($reportInfo["actions"] == "verifyIdentity"){
            $identityID = $reportInfo["identityid"];
            $activeCode = $reportInfo["activecode"];

            $identityHandler = $this->getModelByName("identity");
            $result = $identityHandler->delVerifyCode($identityID, $activeCode);
        }
        //如果是重置密码邮件的ReportSpam
        if($reportInfo["actions"] == "resetPassword"){
            $userID = $reportInfo["user_id"];
            $resetPasswordToken = $reportInfo["user_token"];
            $userIdentity = $reportInfo["user_identity"];

            $userHandler = $this->getModelByName("user");
            $result = $userHandler->delResetPasswordToken($userID, $resetPasswordToken);
        }
        $this->displayView();
    }


    // upgraded
    private $specialDomain = array("facebook", "twitter", "google");


    // upgraded
    public function doIfIdentityExist() {
        //TODO: private API ,must check session
        $identity=$_GET["identity"];
        $responobj["meta"]["code"]=200;
        $identityData = $this->getModelByName("identity");
        $identityArrayInfo = explode("@", $identity);
        if(count($identityArrayInfo) > 1){
            $currentDomain = $identityArrayInfo[1];
            $specialIdentity = $identityArrayInfo[0];
            if(in_array($currentDomain, $this->specialDomain)){
                if($currentDomain == "google"){
                    $specialIdentity .= "@gmail.com";
                }
                $result = $identityData->ifIdentityExist($specialIdentity, $currentDomain);
                if($result != false){
                    if(array_key_exists("user_avatar", $result)){
                        $responobj["response"]["avatar"]=trim($result["user_avatar"]);
                    }
                    if(intval($result["status"]) == 3){
                        $responobj["response"]["status"]="connected";
                    }else{
                        $responobj["response"]["status"]="empty_pwd";
                    }
                    $responobj["response"]["identity_exist"]="true";
                } else {
                    $responobj["response"]["identity_exist"]="false";
                }
                echo json_encode($responobj);
                exit();
            }
        }
        $result = $identityData->ifIdentityExist($identity);
        //$responobj["meta"]["errType"]="Bad Request";
        //$responobj["meta"]["errorDetail"]="invalid_auth";
        if($result !== false)
        {
            if(intval($result["status"]) == 3){
                $responobj["response"]["status"]="connected";
                if(array_key_exists("user_avatar", $result)){
                    $responobj["response"]["avatar"]=trim($result["user_avatar"]);
                }
            }else{
                $responobj["response"]["status"]="verifying";
            }

            $responobj["response"]["identity_exist"]="true";
        } else {
            $responobj["response"]["identity_exist"]="false";
        }
        echo json_encode($responobj);
        exit();
    }


    // upgraded
    public function doLogin()
    {
        //如果已经登录。则访问这个页面时跳转到Profile页面。
        if(intval($_SESSION["userid"])>0){
            header("location:/s/profile");
        }

        //取得变量。
        $identity=$_POST["identity"];
        $password=$_POST["password"];
        //$repassword=$_POST["retypepassword"];
        $displayname=$_POST["displayname"];
        $autosignin=$_POST["auto_signin"];
        if(intval($autosignin)==1){
            $autosignin=true;
        }

        $isNewIdentity=FALSE;

        //if($identity!="" && $password!="" && $repassword==$password && $displayname!="" )
        if($identity!="" && $password!="" && $displayname!="" )
        {
            $Data = $this->getModelByName("user");
            $userid = $Data->AddUser($password);
            $identityData = $this->getModelByName("identity");
            $provider= $_POST["provider"];
            if($provider=="")
                $provider="email";
            $identityData->addIdentity($userid,$provider,$identity,array("name"=>$displayname));
            //TODO: check return value
            $isNewIdentity=TRUE;
            $this->setVar("displayname", $displayname);

        }

        if($identity!="" && $password!="")
        {
            $Data=$this->getModelByName("identity");
            $userid=$Data->login($identity,$password,$autosignin);
            if(intval($userid)>0)
            {
                //$_SESSION["userid"]=$userid;
                if($isNewIdentity===TRUE)
                    $this->setVar("isNewIdentity", TRUE);

                //if(intval($autosignin)>0)
                //{
                //    //TODO: set cookie
                //    //set cookie
                //}

                if($_GET["url"]!="")
                    header( 'Location:'.$_GET["url"] ) ;
                else if( $isNewIdentity==TRUE)
                    $this->displayView();
                else
                    header( 'Location: /s/profile' ) ;
            } else {
                $this->displayView();
            }
        } else {
            $this->displayView();
        }
    }


    // upgraded
    public function doAdd() {
        $identity = $_GET["identity"];
        $provider = $_GET["provider"];
        $password = $_GET["password"];

        //package as a transaction
        if(intval($_SESSION["userid"])>0) {
            $userID=$_SESSION["userid"];
        } else {
            $userData = $this->getModelByName("user");
            $userID = $userData->addUser($password);
        }
        $identityData = $this->getModelByName("identity");
        $identityData->addIdentity($userID,$provider,$identity);
    }


    // upgraded
    public function doLinkInvalid() {
        $this->displayView();
    }


    // upgraded
    public function doExfee()
    {
        $this->displayView();
    }


    // upgraded
    public function doDeleteIdentity(){
        $returnData = array(
            "error"     => 0,
            "msg"       =>""
        );
        //check user login
        $userID = intval($_SESSION["userid"]);
        if($userID <= 0)
        {
            $returnData["error"] = 1;
            $returnData["msg"] = "Please login first.";
            echo json_encode($returnData);
            exit();
        }
        $identityID = exPost("identity_id");
        //check user identity relation
        $identityObj = $this->getModelByName("identity");
        $checkResult = $identityObj->checkUserIdentityRelation($userID, $identityID);
        if(!$checkResult){
            $returnData["error"] = 1;
            $returnData["msg"] = "identity not belong current users.";
            echo json_encode($returnData);
            exit();
        }
        $result = $identityObj->deleteIdentity($userID, $identityID);
        if(!$result){
            $returnData["error"] = 1;
            $returnData["msg"] = "Delete identity fail.";
            echo json_encode($returnData);
            exit();
        }
        echo json_encode($returnData);
    }


    // upgraded
    public function doChangeDefaultIdentity() {
        $returnData = array(
            "error"     => 0,
            "msg"       =>""
        );

        //check user login
        $userID = intval($_SESSION["userid"]);
        if($userID <= 0)
        {
            $returnData["error"] = 1;
            $returnData["msg"] = "Please login first.";
            echo json_encode($returnData);
            exit();
        }

        $identityID = exPost("identity_id");

        $identityObj = $this->getModelByName("identity");
        $checkResult = $identityObj->checkUserIdentityRelation($userID, $identityID);

        if(!$checkResult){
            $returnData["error"] = 1;
            $returnData["msg"] = "identity not belong current users.";
            echo json_encode($returnData);
            exit();
        }

        $identityObj->changeDefaultIdentity($userID, $identityID);

        echo json_encode($returnData);
    }


    // upgraded
    public function doChangePassword() {
        $returnData = array(
            "error"     => 0,
            "msg"       =>""
        );
        header("Content-Type:application/json; charset=UTF-8");
        $userPassword = exPost("u_pwd");
        $userNewPassword = exPost("u_new_pwd");
        //去掉Re-type
        //$userReNewPassword = exPost("u_re_new_pwd");
        if($userPassword == ""){
            $returnData["error"] = 1;
            $returnData["msg"] = "Password cannot be empty.";
            echo json_encode($returnData);
            exit();
        }
        if($userNewPassword == ""){
            $returnData["error"] = 1;
            $returnData["msg"] = "New password cannot be empty.";
            echo json_encode($returnData);
            exit();
        }
        //去掉Re-type
        /*
        if($userNewPassword != $userReNewPassword){
            $returnData["error"] = 1;
            $returnData["msg"] = "Passwords don’t match.";
            echo json_encode($returnData);
            exit();
        }
        */
        $userID = intval($_SESSION["userid"]);
        if($userID <= 0)
        {
            $returnData["error"] = 1;
            $returnData["msg"] = "Please login first.";
            echo json_encode($returnData);
            exit();
        }
        $userObj = $this->getModelByName("user");

        $result = $userObj->checkUserPassword($userID, $userPassword);
        if(!$result){
            $returnData["error"] = 1;
            $returnData["msg"] = "Passwords error.";
            echo json_encode($returnData);
            exit();
        }
        $userObj->updateUserPassword($userID, $userNewPassword);
        echo json_encode($returnData);
        exit();
    }


    // upgraded
    public function doCheckLogin(){
        //header("Content-Type:application/json; charset=UTF-8");
        if(intval($_SESSION["userid"])>0)
        {
            echo 1;
            $userData = $this->getModelByName("user");
            $user=$userData->getUser($_SESSION["userid"]);
        }else{
            echo 0;
        }
    }


    // upgraded
    public function doGetUserProfile()
    {
        //TODO: private API ,must check session
        $name=$_POST["name"];
        $userid=intval($_SESSION["userid"]);
        if ($userid > 0)
        {
            $userData = $this->getModelByName("user");
            $user=$userData->getUser($userid);
            $responobj["meta"]["code"]=200;
            //$responobj["meta"]["errType"]="Bad Request";
            //$responobj["meta"]["errorDetail"]="invalid_auth";
            $responobj["response"]["user"]=$user;
            echo json_encode($responobj);
            exit();
        }
    }


    // upgraded
    public function doLogoutsession()
    {
        unset($_SESSION["userid"]);
        unset($_SESSION["identity_id"]);
        unset($_SESSION["identity"]);
        unset($_SESSION["tokenIdentity"]);
        //logout session
        session_destroy();
    }


    // upgraded
    public function doLogout()
    {
        $userData = $this->getModelByName("user");
        $userData->doDestroySessionAndCookies();
        header('location:/');
    }


    // upgraded
    public function doSendResetPasswordMail()
    {
        $returnData = array(
            "error" => 0,
            "msg"   =>""
        );
        $userIdentity = exPost("identity");
        if($userIdentity == ""){
            $returnData["error"] = 1;
            $returnData["msg"] = "User Identity is empty";
        }else{

            $userData = $this->getModelByName("user");
            $result=$userData->getResetPasswordToken($userIdentity);
            if($result["token"] != "" && intval($result["uid"]) > 0)
            {
                $userInfo = array(
                    "actions"           =>"resetPassword",
                    "user_id"           =>$result["uid"],
                    "identity_id"       =>$result["identity_id"],
                    "identity"          =>$userIdentity,
                    "user_token"        =>$result["token"]
                );

                $pakageToken = packArray($userInfo);
                $name=$result["name"];
                if($name==""){
                    $name=$userIdentity;
                }
                $args = array(
                    'external_identity' => $userIdentity,
                    'name' => $name,
                    'token' => $pakageToken
                );
                //echo $pakageToken;
                //exit();
                $helper=$this->getHelperByName("identity");
                $jobId=$helper->sendResetPassword($args);
                if($jobId=="")
                {
                    $returnData["error"] = 1;
                    $returnData["msg"] = "mail server error";
                }
            } else {
                $returnData["error"] = 1;
                $returnData["msg"] = "can't reset password";
            }
            //echo "get $userIdentity";
            //@Huoju
            //do send verication email
        }
        //sleep(1);
        header("Content-Type:application/json; charset=UTF-8");
        echo json_encode($returnData);
    }


    // upgraded
    public function doEditUserProfile()
    {
        $returnData = array(
            "error" => 0,
            "msg"   =>"",
            "response" => array()
        );
        header("Content-Type:application/json; charset=UTF-8");

        //TODO: private API ,must check session
        $userName = trim(exPost("user_name"));
        if($userName == ""){
            $returnData["error"] = 1;
            $returnData["msg"] = "user name empty.";
            echo json_encode($returnData);
            exit();
        }

        $userID = intval($_SESSION["userid"]);
        if ($userID > 0)
        {
            $userDataObj = $this->getModelByName("user");
            $userInfo = $userDataObj->saveUser($userName,$userID);
            $returnData["response"]["user"] = $userInfo;
        }

        echo json_encode($returnData);
        exit();
    }

}
