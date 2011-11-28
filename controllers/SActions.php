<?php

class SActions extends ActionController
{

    public function doTestUser()
    {
        $identityData = $this->getModelByName("identity");
        $identityData->setRelation($_GET["identity_id"]);

    }

    public function doAdd()
    {
        $identity= $_GET["identity"];
        $provider= $_GET["provider"];
        $password = $_GET["password"];


        //package as a  transaction
        if(intval($_SESSION["userid"])>0)
        {
            $userid=$_SESSION["userid"];
        }
        else
        {
            $Data = $this->getModelByName("user");
            $userid = $Data->addUser($password);
        }
        $identityData = $this->getModelByName("identity");
        $identityData->addIdentity($userid,$provider,$identity);
    }

    //上传头像文件。
    public function doUploadAvatarFile(){
        //list of valid extensions, ex. array("jpeg", "xml", "bmp")
        $allowedExtensions = array("jpeg", "gif", "png", "jpg", "bmp");
        //max file size in bytes
        $sizeLimit = 10 * 1024 * 1024;
        //The directory for the images to be saved in
        $upload_dir = "eimgs";

        $exFileUploader = $this->getHelperByName("fileUploader");
        $exFileUploader->initialize($allowedExtensions, $sizeLimit);
        $result = $exFileUploader->handleUpload($upload_dir);
        if(!$result["error"]){
            $img_name = $result["file_name"];
            $img_ext = $result["file_ext"];
            $img_path = $result["file_path"];
            //图片还要经过处理后再给客户端。
            require_once "imgcommon.php";
            $img_info = array(
                "source_image"      =>$img_path."/".$img_name,
                "target_image"      =>$img_path."/"."240_240_".$img_name,
                "width"             =>240,
                "height"            =>240,
            );
            asidoResizeImg($img_info);
        }

        // to pass data through iframe you will need to encode all html tags
        //echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
        echo json_encode($result);
    }

    //截剪头像。
    public function doUploadAvatarNew(){
        require_once "imgcommon.php";
        $img_name = $_POST["iName"];
        $img_height = $_POST["iHeight"];
        $img_width = $_POST["iWidth"];
        $img_x = $_POST["iX"];
        $img_y = $_POST["iY"];
        $img_dir = "eimgs";
        $img_path = getHashFilePath($img_dir, $img_name);

        $img_info = array(
            "source_image"      =>$img_path."/"."240_240_".$img_name,
            "target_image"      =>$img_path."/"."80_80_".$img_name,
            "width"             =>$img_width,
            "height"            =>$img_height,
            "x"                 =>$img_x,
            "y"                 =>$img_y
        );
        asidoResizeImg($img_info, $crop=true);

        $return_data = array(
            "status"    =>0,
            "msg"       =>""
        );


        $userData = $this->getModelByName("user");
        $userData->saveUserAvatar($img_name,$_SESSION["userid"]);

        header("Content-Type:application/json; charset=UTF-8");
        echo json_encode($return_data);
        exit(0);
    }

    //HTML5图片的处理方法，废弃没用。
    /*
    public function doUploadAvatarNew_bak(){
        if(intval($_SESSION["userid"])>0)
        {
            $self_url="/s/uploadAvatarNew";
            $upload_dir = "eimgs"; 				// The directory for the images to be saved in
            $upload_path = $upload_dir."/";				// The path to where the image will be saved

            $large_image_name = $_POST['iName']; 		// New name of the large image
            $image_name = md5(randStr(20).$large_image_name.getMicrotime()).".jpg";

            $return_data = array(
                "status"    =>0,
                "msg"       =>""
            );

           if(!empty($_POST["iSmallFile"])){
                $image_base64_data = $_POST["iSmallFile"];
                $image_encode_data = substr($image_base64_data, strpos($image_base64_data, ","), strlen($image_base64_data));
                $image_data = base64_decode($image_encode_data);
                $im = imagecreatefromstring($image_data);
                $small_image_name = "80_80_".$image_name;
                if($im !== false) {
                    header('Content-Type: image/jpeg');
                    imagejpeg($im,$upload_path.$small_image_name);
                    imagedestroy($im);
                } else {
                    $return_data["status"] = 1;
                    $return_data["msg"] = 'Save Image error.';
                }
            }

           if(!empty($_POST["iBigFile"])){
                $image_base64_data = $_POST["iBigFile"];
                $image_encode_data = substr($image_base64_data, strpos($image_base64_data, ","), strlen($image_base64_data));
                $image_data = base64_decode($image_encode_data);
                $im = imagecreatefromstring($image_data);
                $small_image_name = "240_240_".$image_name;
                if($im !== false) {
                    header('Content-Type: image/jpeg');
                    imagejpeg($im,$upload_path.$small_image_name);
                    imagedestroy($im);
                } else {
                    $return_data["status"] = 1;
                    $return_data["msg"] = 'Save Image error.';
                }
            }

            $userData = $this->getModelByName("user");
            $userData->saveUserAvatar($image_name,$_SESSION["userid"]);

            header("Content-Type:application/json; charset=UTF-8");
            echo json_encode($return_data);
            exit(0);
        }

    }
    */


    public function doProfile()
    {
        if (intval($_SESSION['userid']) <= 0) {
            header('Location: /s/login') ;
            exit(0);
        }

        // init models
        $modIdentity = $this->getModelByName('identity');
        $modUser     = $this->getModelByName('user');

        // Get identities
        $identities  = $modIdentity->getIdentitiesByUser($_SESSION['userid']);
        $this->setVar('identities', $identities);

        // Get user informations
        $user        = $modUser->getUser($_SESSION['userid']);
        $this->setVar('user', $user);

        $this->displayView();
    }


    public function doGetInvitation()
    {
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
            $newInvt[$newInvtI]['base62id']
          = int_to_base62($newInvtItem['cross_id']);
            $identity = $modIdentity->getIdentityById(
                $newInvtItem['identity_id']
            );
            $newInvt[$newInvtI]['sender'] = humanIdentity(
                $identity,
                $modUser->getUserByIdentityId($newInvtItem['identity_id'])
            );
            $newInvt[$newInvtI]['cross'] = $modCross->getCross(
                $newInvtItem['cross_id']
            );
        }

        echo json_encode($newInvt);
    }


    public function doGetCross()
    {
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
        $maxCross  = 20;
        $today     = strtotime(date('Y-m-d'));
        $upcoming  = $today + 60 * 60 * 24 * 3;
        $sevenDays = $today + 60 * 60 * 24 * 7;


        $crosses   = $modCross->fetchCross($_SESSION['userid'], $today);
        $pastXs    = $modCross->fetchCross($_SESSION['userid'], $today, 'no',
                                           'begin_at DESC', 20-count($crosses));
        $anytimeXs = $modCross->fetchCross($_SESSION['userid'], $today,
                                           'nodate', 'created_at DESC', 3);

        // sort crosses
        foreach ($crosses as $crossI => $crossItem) {
            $crosses[$crossI]['timestamp'] = strtotime($crossItem['begin_at']);
            if ($crosses[$crossI]['timestamp'] < $upcoming) {
                $crosses[$crossI]['sort'] = 'upcoming';
            } else if ($crosses[$crossI]['timestamp'] < $sevenDays) {
                $crosses[$crossI]['sort'] = 'sevenDays';
            } else {
                $crosses[$crossI]['sort'] = 'later';
            }
        }
        foreach ($pastXs as $pastXI => $pastXItem) {
            $pastXItem['sort'] = 'past';
            array_push($crosses, $pastXItem);
        }
        foreach ($anytimeXs as $anytimeXI => $anytimeXItem) {
            $anytimeXItem['sort'] = 'anytime';
            array_push($crosses, $anytimeXItem);
        }

        // get confirmed informations
        $crossIds = array();
        foreach ($crosses as $crossI => $crossItem) {
            array_push($crossIds, $crossItem['id']);
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
            unset($humanIdentities[$ridItem['id']]['activecode']);
        }

        // Add confirmed informations into crosses
        foreach ($crosses as $crossI => $crossItem) {
            $crosses[$crossI]['base62id'] = int_to_base62($crossItem['id']);
            $crosses[$crossI]['exfee']    = array();
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


    public function doGetUpdate()
    {
        if (intval($_SESSION['userid']) <= 0) {
            echo json_encode(array('error' => 'forbidden'));
            exit(0);
        }

        // init models
        $modIdentity = $this->getModelByName('identity');
        $modUser     = $this->getModelByName('user');
        $modCross    = $this->getModelByName('x');
        $modLog      = $this->getModelByName('log');

        // Get all cross
        $rawCross = $modCross->fetchCross($_SESSION['userid'], 0, null, null, null);
        $allCross = array();
        foreach ($rawCross as $crossI => $crossItem) {
            $allCross[$crossItem['id']] = $crossItem;
        }
        $allCrossIds = array_keys($allCross);

        // Get recently logs
        $rawLogs = $modLog->getRecentlyLogsByCrossIds($allCrossIds);

        // clean logs
        $loged   = array();
        foreach ($rawLogs as $logI => $logItem) {
            $xId = $logItem['to_id'];
            $rawLogs[$logI]['from_id']
          = $logItem['from_id'] = intval($logItem['from_id']);
            switch ($logItem['action']) {
                case 'gather':
                    $changeDna = "{$xId}_title";
                    if (isset($loged[$changeDna])
                    && !isset($rawLogs[$loged[$changeDna]]['oldtitle'])) {
                        $rawLogs[$loged[$changeDna]]['oldtitle']
                      = $logItem['change_summy'];
                    }
                    unset($rawLogs[$logI]);
                    break;
                case 'change':
                    $changeDna = "{$xId}_{$logItem['to_field']}";
                    if (isset($loged[$changeDna])) {
                        if ($logItem['to_field'] === 'title') {
                            if (!isset(
                                    $rawLogs[$loged[$changeDna]]['oldtitle']
                                )) {
                                $rawLogs[$loged[$changeDna]]['oldtitle']
                              = $logItem['change_summy'];
                            }
                        }
                        unset($rawLogs[$logI]);
                    } else {
                        $loged[$changeDna] = $logI;
                    }
                    break;
                case 'conversation':
                    $changeDna = "{$xId}_conversation";
                    if (isset($loged[$changeDna])) {
                        unset($rawLogs[$logI]);
                        $rawLogs[$loged[$changeDna]]['num_conversation']++;
                    } else {
                        $loged[$changeDna] = $logI;
                        $rawLogs[$loged[$changeDna]]['num_conversation'] = 1;
                    }
                    break;
                case 'rsvp':
                case 'exfee':
                    $doSkip = false;
                    switch ($logItem['to_field']) {
                        case '':
                            $changeDna = "{$xId}_exfee_{$logItem['from_id']}";
                            $rawLogs[$logI]['change_summy']
                          = $logItem['change_summy']
                          = intval($logItem['change_summy']);
                            $dnaValue  = array(
                                'action'    => 'rsvp',
                                'offset'    => $logI,
                                'soft_rsvp' => $logItem['change_summy'] === 0
                                            || $logItem['change_summy'] === 3,
                            );
                            break;
                        case 'rsvp':
                            $rawLogs[$logI]['change_summy'] = explode(
                                ':',
                                $logItem['change_summy']
                            );
                            $rawLogs[$logI]['change_summy']
                          = array_map('intval',$rawLogs[$logI]['change_summy']);
                            $changeDna = "{$xId}_exfee_"
                                       . "{$rawLogs[$logI]['change_summy'][0]}";
                            $dnaValue  = array('action' => 'rsvp',
                                               'offset' => $logI);
                            break;
                        case 'addexfe':
                        case 'delexfe':
                            $changeDna = "{$xId}_exfee_"
                                       . "{$logItem['change_summy']}";
                            $dnaValue  = array('action' => $logItem['to_field'],
                                               'offset' => $logI);
                            break;
                        default:
                            $doSkip = true; // 容错处理
                    }
                    if ($doSkip) {
                        unset($rawLogs[$logI]);
                        break;
                    }
                    if (isset($loged[$changeDna])) {
                        if ($dnaValue['action'] === 'addexfe'
                         && $loged[$changeDna]['action'] === 'rsvp'
                         && $loged[$changeDna]['soft_rsvp']) {
                            $rawLogs[$loged[$changeDna]['offset']] = $logItem;
                            $loged[$changeDna] = $dnaValue;
                        } else if (($loged[$changeDna]['action'] === 'addexfe'
                          && $dnaValue['action'] === 'delexfe')
                         || ($loged[$changeDna]['action'] === 'delexfe'
                          && $dnaValue['action'] === 'addexfe')) {
                            $loged[$changeDna]['action'] = 'skipped';
                            unset($rawLogs[$loged[$changeDna]['offset']]);
                        }
                        unset($rawLogs[$logI]);
                    } else {
                        $loged[$changeDna] = $dnaValue;
                    }
            }
        }

        // merge logs
        $cleanLogs = array();
        $xlogsHash = array();
        $relatedIdentityIds = array();
        foreach ($rawLogs as $logI => $logItem) {
            $xId = $logItem['to_id'];
            if (!isset($xlogsHash[$xId])) {
                $xlogsHash[$xId]
              = array_push($cleanLogs, array('cross_id' => $xId)) - 1;
            }
            switch ($logItem['action']) {
                case 'change':
                    $cleanLogs[$xlogsHash[$xId]]['change'][$logItem['to_field']]
                  = array('time'      => $logItem['time'],
                          'by_id'     => $logItem['from_id'],
                          'new_value' => $logItem['change_summy'],
                          'old_value' => isset($logItem['oldtitle'])
                                       ? $logItem['oldtitle'] : null);
                    array_push($relatedIdentityIds, $logItem['from_id']);
                    break;
                case 'conversation':
                    $cleanLogs[$xlogsHash[$xId]]['conversation']
                  = array('time'      => $logItem['time'],
                          'by_id'     => $logItem['from_id'],
                          'message'   => $logItem['change_summy'],
                          'num_msgs'  => $logItem['num_conversation']);
                    array_push($relatedIdentityIds, $logItem['from_id']);
                    break;
                case 'rsvp':
                case 'exfee':
                    switch ($logItem['to_field']) {
                        case '':
                        case 'rsvp':
                            if (is_array($logItem['change_summy'])) {
                                list($toExfee,$action)=$logItem['change_summy'];
                            } else {
                                $toExfee = $logItem['from_id'];
                                $action  = $logItem['change_summy'];
                            }
                            if ($action === 1) {
                                $action = 'confirmed';
                            } else if ($action === 2) {
                                $action = 'declined';
                            } else {
                                break;
                            }
                            if (!isset($cleanLogs[$xlogsHash[$xId]][$action])) {
                                $cleanLogs[$xlogsHash[$xId]][$action] = array();
                            }
                            array_push(
                                $cleanLogs[$xlogsHash[$xId]][$action],
                                array('time'  => $logItem['time'],
                                      'by_id' => $logItem['from_id'],
                                      'to_id' => $toExfee)
                            );
                            array_push($relatedIdentityIds,$logItem['from_id']);
                            array_push($relatedIdentityIds,$toExfee);
                            break;
                        case 'addexfe':
                        case 'delexfe':
                            $action = $logItem['action'];
                            if (!isset($cleanLogs[$xlogsHash[$xId]][$action])) {
                                $cleanLogs[$xlogsHash[$xId]][$action] = array();
                            }
                            array_push(
                                $cleanLogs[$xlogsHash[$xId]][$action],
                                array('time'  => $logItem['time'],
                                      'by_id' => $logItem['from_id'],
                                      'to_id' => $logItem['change_summy'])
                            );
                            array_push($relatedIdentityIds,$logItem['from_id']);
                            array_push($relatedIdentityIds,
                                       $logItem['change_summy']);
                    }
            }
            if (count($cleanLogs[$xlogsHash[$xId]]) === 1) {
                array_pop($cleanLogs);
                unset($xlogsHash[$xId]);
            }
        }

        // get human identities
        $humanIdentities = array();
        $relatedIdentities = $modIdentity->getIdentitiesByIdentityIds(
            array_flip(array_flip($relatedIdentityIds))
        );
        foreach ($relatedIdentities as $ridI => $ridItem) {
            $user = $modUser->getUserByIdentityId($ridItem['identity_id']);
            $humanIdentities[$ridItem['id']] = humanIdentity($ridItem, $user);
        }

        // merge cross details and humanIdentities
        foreach ($cleanLogs as $logI => $logItem) {
            $cleanLogs[$logI]['base62id'] = int_to_base62($logItem['cross_id']);
            $cleanLogs[$logI]['title']
          = $allCross[$logItem['cross_id']]['title'];
            $cleanLogs[$logI]['begin_at']
          = $allCross[$logItem['cross_id']]['begin_at'];
            foreach (array('change',  'confirmed', 'declined',
                           'addexfe', 'delexfe') as $action) {
                if (isset($logItem[$action])) {
                    foreach ($logItem[$action] as $actionI => $actionItem) {
                        $cleanLogs[$logI][$action][$actionI]['by_name']
                      = $humanIdentities[$actionItem['by_id']]['name'];
                        if (!isset(
                                $cleanLogs[$logI][$action][$actionI]['to_id'])
                            ) {
                            continue;
                        }
                        $cleanLogs[$logI][$action][$actionI]['to_name']
                      = $humanIdentities[$actionItem['to_id']]['name'];
                    }
                }
            }
            if (isset($logItem['conversation'])) {
                $cleanLogs[$logI]['conversation']['by_name']
              = $humanIdentities[$logItem['conversation']['by_id']]['name'];
            }
        }

        echo json_encode($cleanLogs);
    }


    public function doIfIdentityExist()
    {
        //TODO: private API ,must check session
        $identity=$_GET["identity"];
        $identityData = $this->getModelByName("identity");
        $exist=$identityData->ifIdentityExist($identity);

        $responobj["meta"]["code"]=200;
        //$responobj["meta"]["errType"]="Bad Request";
        //$responobj["meta"]["errorDetail"]="invalid_auth";

        if($exist!==FALSE)
        {
            if(intval($exist["status"])==3)
                $responobj["response"]["status"]="connected";
            else
                $responobj["response"]["status"]="verifying";

            $responobj["response"]["identity_exist"]="true";
        }
        else
            $responobj["response"]["identity_exist"]="false";
        echo json_encode($responobj);
        exit();
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
            "crosses"           =>""
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

        if(intval($_SESSION["userid"])>0)
        {
            $userData = $this->getModelByName("user");
            $user=$userData->getUser($_SESSION["userid"]);
            //display user name.
            $global_name=$user["name"];
            if($user["avatar_file_name"] == ""){
                $global_avatar_file_name = "default.png";
            }else{
                $global_avatar_file_name=$user["avatar_file_name"];
            }

            $returnData["user_status"] = 1;
            $returnData["user_name"] = $global_name;
            $returnData["user_avatar"] = $global_avatar_file_name;
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
            $crosses[$k]["id"] = int_to_base62($v["id"]);
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

    public function doSaveUserIdentity()
    {
        //TODO: private API ,must check session
        $name=$_POST["name"];
        $userid=intval($_SESSION["userid"]);
        if ($userid > 0)
        {
            $userData = $this->getModelByName("user");
            $user=$userData->saveUser($name,$userid);
            $responobj["meta"]["code"]=200;
            //$responobj["meta"]["errType"]="Bad Request";
            //$responobj["meta"]["errorDetail"]="invalid_auth";
            $responobj["response"]["user"]=$user;
            echo json_encode($responobj);
            exit();
        }
    }

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

    public function doLogoutsession()
    {
        unset($_SESSION["userid"]);
        unset($_SESSION["identity_id"]);
        unset($_SESSION["identity"]);
        unset($_SESSION["tokenIdentity"]);
        //logout session
        session_destroy();
    }

    public function doLogout()
    {
        unset($_SESSION["userid"]);
        unset($_SESSION["identity_id"]);
        unset($_SESSION["identity"]);
        unset($_SESSION["tokenIdentity"]);
        session_destroy();

        unset($_COOKIE["uid"]);
        unset($_COOKIE["id"]);
        unset($_COOKIE["loginsequ"]);
        unset($_COOKIE["logintoken"]);

        setcookie('uid', NULL, -1,"/",".exfe.com");
        setcookie('id', NULL, -1,"/",".exfe.com");
        setcookie('loginsequ', NULL,-1,"/",".exfe.com");
        setcookie('logintoken',NULL,-1,"/",".exfe.com");

        header('location:/');

    }

    public function doLogin()
    {
        //如果已经登录。则访问这个页面时跳转到Profile页面。
        if(intval($_SESSION["userid"])>0){
            header("location:/s/profile");
        }

        //取得变量。
        $identity=$_POST["identity"];
        $password=$_POST["password"];
        $repassword=$_POST["retypepassword"];
        $displayname=$_POST["displayname"];
        $autosignin=$_POST["auto_signin"];
        if(intval($autosignin)==1){
            $autosignin=true;
        }

        $isNewIdentity=FALSE;

        if($identity!="" && $password!="" && $repassword==$password && $displayname!="" )
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

    public function doDialogaddidentity()
    {
        $identity=$_POST["identity"];
        $password=$_POST["password"];
        $repassword=$_POST["retypepassword"];
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
                if($provider=="")
                    $provider="email";
                $identity_id=$identityData->addIdentity($userid,$provider,$identity,array("name"=>$displayname));
                $userid=$identityData->login($identity,$password,$autosignin);
                if(intval($userid)>0)
                {
                    // sent welcome email
                    /*
                    if($provider=="email")
                    {
                        $msghelper=$this->getHelperByName("msg");
                        $args=array("name"=>$displayname,"external_identity"=>$identity);
                        if($displayname=="")
                            $args["name"]=$identity;
                        $msghelper->sentWelcomeEmail($args);
                    }
                     */

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
            $Data=$this->getModelByName("identity");
            $userid=$Data->login($identity,$password,$autosignin);

            if(intval($userid)>0)
            {
                $responobj["response"]["success"]="true";
                $responobj["response"]["userid"]=$userid;
            }
            else
                $responobj["response"]["success"]="false";
        }
        else
            $responobj["response"]["success"]="false";
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
                header("location:/x/forbidden");
            }else{
                $userInfo = unpackArray($token);
                $userToken = $userInfo["user_token"];
                $userId = $userInfo["user_id"];
                $userIdentity = $userInfo["user_identity"];

                //验证当前重置密码操作是否有效。
                $userDataObj = $this->getModelByName("user");
                $result = $userDataObj->verifyResetPassword($userId, $userToken);

                if(is_array($result)){
                    $this->setVar("userIdentity", $userIdentity);
                    $this->setVar("userName", $result["name"]);
                    $this->setVar("userToken", $token);
                    $this->displayView();
                }else{
                    header("location:/x/forbidden");
                }
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
                $result["error"] = 1;
                $result["msg"] = "must set password";
                header("Content-Type:application/json; charset=UTF-8");
                echo json_encode($returnData);
                exit();
            }
            if($userDisplayName == ""){
                $result["error"] = 1;
                $result["msg"] = "must set display name";
                header("Content-Type:application/json; charset=UTF-8");
                echo json_encode($returnData);
                exit();
            }


            if($actions == "resetpwd"){
                $token = exPost("u_token");
                $userInfo = unpackArray($token);
                $userId = $userInfo["user_id"];
                $userToken = $userInfo["user_token"];
                $userIdentity= $userInfo["user_identity"];

                $userDataObj = $this->getModelByName("user");
                $result = $userDataObj->doResetUserPassword($userPassword, $userDisplayName, $userId, $userIdentity,$userToken);

                if(!$result["result"]){
                    $result["error"] = 1;
                    $result["msg"] = "System Error.";
                }
                else
                {
                    $identityData = $this->getModelByName("identity");
                    $identityData->login($userIdentity,$userPassword,"true");

                    if($result["newuser"])
                    {
                        $msghelper=$this->getHelperByName("msg");
                        $identity = $identityData->getIdentity($userIdentity);

                        $args=array("name"=>$userDisplayName,"external_identity"=>$identity["external_identity"]);

                        if($args["name"]=="")
                            $args["name"]=$identity["external_identity"];

                        $msghelper->sentWelcomeEmail($args);
                    }
                }
            }
            if($actions == "setpwd"){
                $crossID = exPost("c_id");
                $crossToken = exPost("c_token");
                $result = $this->doSetpwd($userPassword, $userDisplayName, $crossID, $crossToken);

                if($result==false){
                    $returnData["error"] = 1;
                    $returnData["msg"] = "System Error.";
                }
                else
                {
                    $returnData["uid"]=$result["uid"];
                    $returnData["cross_id"]=int_to_base62($crossID);
                }
            }
            header("Content-Type:application/json; charset=UTF-8");
            echo json_encode($returnData);
        }
    }

    /**
     * 忘记密码发送验证邮件
     *
     **/
    public function doSendVerification()
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
            $result=$userData->setPasswordToken($userIdentity);
            if($result["token"]!="" && intval($result["uid"])>0)
            {
                $userInfo = array(
                    "user_id"           =>$result["uid"],
                    "user_identity"     =>$userIdentity,
                    "user_token"        =>$result["token"]
                );

                $pakageToken = packArray($userInfo);
                $name=$result["name"];
                if($name=="")
                    $name=$userIdentity;
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

    public function doActive() {
        $identity_id=intval($_GET["id"]);
        $activecode=$_GET["activecode"];
        if($identity_id>0)
        {
            $identityData= $this->getModelByName("identity");
            $result=$identityData->activeIdentity($identity_id,$activecode);

            $identity = $identityData->getIdentityById($identity_id);
            if($result["result"]=="verified")
            {
                $identityData->loginByIdentityId($identity_id,0,$identity["external_identity"]);
            }
        }
        $this->setVar("result",$result);
        $this->displayView();
    }

    public function doSendActiveEmail() {
        $returnData = array(
            "error"     => 0,
            "msg"       =>"",
            "identity"  =>""
        );

        #if(intval($_SESSION["userid"])>0)
        #{
            $external_identity=$_POST["identity"];
            $identityData= $this->getModelByName("identity");
            $identity=$identityData->getIdentity($external_identity);
            $identity_id=intval($identity["id"]);
            #$identity_id=$identityData->ifIdentityBelongsUser($external_identity,$_SESSION["userid"]);
            if($identity_id>0)
            {
                $r=$identityData->reActiveIdentity($identity_id);
                if($r!==FALSE)
                {
                }
                //belongs this user, send activecode and update identities table.
                $args = array(
                         'identityid' => $r["id"],
                         'external_identity' => $r["external_identity"],
                         'name' => $r["name"],
                         'avatar_file_name' => $r["avatar_file_name"],
                         'activecode' => $r["activecode"]
                 );
                if($r["provider"]=="email")
                {
                    $helper=$this->getHelperByName("identity");
                    $jobId=$helper->sentActiveEmail($args);
                    if($jobId == "")
                    {
                        $returnData["error"] = 1;
                        $returnData["msg"] = "Send mail error.";
                        $returnData["identity"] = $r["external_identity"];
                    }
                }
            }

        #}
        header("Content-Type:application/json; charset=UTF-8");
        echo json_encode($returnData);
    }
}

