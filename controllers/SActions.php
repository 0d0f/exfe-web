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

    //霍炬之前的上传头像处理方法。也已经废弃。
    public function doUploadavatar()
    {
        if($_GET["a"]=="close")
        {
            $this->displayViewByAction("close");
        }
        require_once "imgcommon.php";
        if(intval($_SESSION["userid"])>0)
        {
            $self_url="/s/uploadavatar";
            $upload_dir = "eimgs"; 				// The directory for the images to be saved in
            $upload_path = $upload_dir."/";				// The path to where the image will be saved
            $large_image_name = $_FILES['image']['name']; 		// New name of the large image
            if($large_image_name=="")
                $large_image_name=$_SESSION["upload_imgname"];
            $max_file = "1148576"; 						// Approx 1MB
            $max_width = "500";							// Max width allowed for the large image
            $thumb_width = "80";						// Width of thumbnail image
            $thumb_height = "80";						// Height of thumbnail image
            $big_thumb_width = "240";						// Width of thumbnail image
            $big_thumb_height = "240";						// Height of thumbnail image
            $this->setVar("thumb_width", $thumb_width);
            $this->setVar("thumb_height", $thumb_height);

            $thumb_image_name = $thumb_width.'_'.$thumb_height."_".$large_image_name; 	// New name of the thumbnail image
            $big_thumb_image_name = $big_thumb_width.'_'.$big_thumb_height."_".$large_image_name; 	// New name of the thumbnail image
            //Image Locations
            $large_image_location = $upload_path.$large_image_name;
            $thumb_image_location = $upload_path.$thumb_image_name;
            $big_thumb_image_location = $upload_path.$big_thumb_image_name;

            $this->setVar("large_image_location", $large_image_location);
            $this->setVar("thumb_image_location", $thumb_image_location);



            //Create the upload directory with the right permissions if it doesn't exist
            if(!is_dir($upload_dir)){
                mkdir($upload_dir, 0777);
                chmod($upload_dir, 0777);
            }

            //Check to see if any images with the same names already exist
            if (file_exists($large_image_location)){
                if(file_exists($thumb_image_location)){
                    $thumb_photo_exists = "<img src=\"/".$upload_path.$thumb_image_name."\" alt=\"Thumbnail Image\"/>";
                }else{
                    $thumb_photo_exists = "";
                }
                $large_photo_exists = $upload_path.$large_image_name;
#$large_photo_exists = "<img src=\"/".$upload_path.$large_image_name."\" alt=\"Large Image\"/>";
            } else {
                $large_photo_exists = "";
                $thumb_photo_exists = "";
            }
            $this->setVar("large_photo_exists", $large_photo_exists);

            if (isset($_POST["upload"])) {
                //Get the file information
                $userfile_name = $_FILES['image']['name'];
                $userfile_tmp = $_FILES['image']['tmp_name'];
                $userfile_size = $_FILES['image']['size'];
                $filename = basename($_FILES['image']['name']);
                $file_ext = substr($filename, strrpos($filename, '.') + 1);
#
#//Only process if the file is a JPG and below the allowed limit
                if((!empty($_FILES["image"])) && ($_FILES['image']['error'] == 0)) {
                    if (($file_ext!="jpg") && ($userfile_size > $max_file)) {
                        $error= "ONLY jpeg images under 1MB are accepted for upload";
                    }
                }else{
                    $error= "Select a jpeg image for upload";
                }
                //Everything is ok, so we can upload the image.
                if (strlen($error)==0){

                    if (isset($_FILES['image']['name'])){

                        //move_uploaded_file($userfile_tmp, $large_image_location);
                        move_uploaded_file($userfile_tmp, $large_image_location);
                        chmod($large_image_location, 0604);
#
                        $width = getWidth($large_image_location);
                        $height = getHeight($large_image_location);

                        $filename = stripslashes($_FILES['image']['name']);
                        $extension = getExtension($filename);
                        $extension = strtolower($extension);
                        //Scale the image if it is greater than the width set above
                        if ($width > $max_width){
                            $scale = $max_width/$width;
                            $uploaded = resizeImage($large_image_location,$width,$height,$scale,$extension);
                        }else{
                            $scale = 1;
                            $uploaded = resizeImage($large_image_location,$width,$height,$scale,$extension);
                        }



                        //Delete the thumbnail file so the user can create a new one
                        if (file_exists($thumb_image_location)) {
                            unlink($thumb_image_location);
                        }
                    }
                    //Refresh the page to show the new uploaded image
                    header("location:".$self_url);
                    $_SESSION["upload_imgname"]=$large_image_name;
                    exit();
                }
            }

            if (isset($_POST["upload_thumbnail"]) && strlen($large_photo_exists)>0) {
                //Get the new coordinates to crop the image.
                $x1 = $_POST["x1"];
                $y1 = $_POST["y1"];
                $x2 = $_POST["x2"];
                $y2 = $_POST["y2"];
                $w = $_POST["w"];
                $h = $_POST["h"];
                //Scale the image to the thumb_width set above
                $big_scale = $big_thumb_width/$w;
                $scale = $thumb_width/$w;

                $filename = stripslashes($large_image_location);
                $extension = getExtension($filename);
                $extension = strtolower($extension);

                $cropped = resizeThumbnailImage($big_thumb_image_location, $large_image_location,$w,$h,$x1,$y1,$big_scale,$extension);
                $cropped = resizeThumbnailImage($thumb_image_location, $large_image_location,$w,$h,$x1,$y1,$scale,$extension);
                $userData = $this->getModelByName("user");
                $userData->saveUserAvatar($large_image_name,$_SESSION["userid"]);
                //Reload the page again to view the thumbnail
                header("location:".$self_url."?a=close");
                exit();
            }

            $this->displayView();
        }
        else
        {
            header( 'Location: /s/login' ) ;
            exit(0);
        }
    }

    public function doProfile()
    {
        if (intval($_SESSION['userid']) <= 0) {
            header( 'Location: /s/login' ) ;
            exit(0);
        }

        // Get identity
        $identityData = $this->getModelByName('identity');
        $identities   = $identityData->getIdentitiesByUser($_SESSION['userid']);
        $this->setVar('identities', $identities);

        // Get user informations
        $userData = $this->getModelByName('user');
        $user = $userData->getUser($_SESSION['userid']);
        $this->setVar('user', $user);

        // Get crosses
        $today     = strtotime(date('Y-m-d'));
        $upcoming  = $today + 60 * 60 * 24 * 3;
        $sevenDays = $today + 60 * 60 * 24 * 7;
        $crossdata = $this->getModelByName('x');
        $crosses   = $crossdata->fetchCross($_SESSION['userid'], $today); // @virushuo says "no mulit-identity" in one user now
        $pastXs    = $crossdata->fetchCross($_SESSION['userid'], $today, 'no', 'begin_at DESC', 20 - count($crosses));
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
        // Get all cross
        $allCross   = $crossdata->fetchCross($_SESSION['userid'], 0, null, null, null);
        $allCrossId = array();
        foreach ($allCross as $crossI => $crossItem) {
            array_push($allCrossId, $crossItem['id']);
        }
        // Get recently logs
        $logdata = $this->getModelByName('log');
        $rawLogs = $logdata->getRecentlyLogsByCrossIds($allCrossId, 'gather');
        // Get confirmed informations
        $crossIds = array();
        foreach ($crosses as $crossI => $crossItem) {
            array_push($crossIds, $crossItem['id']);
        }
        $modIvit = $this->getModelByName('invitation');
        $cfedIds = $modIvit->getIdentitiesIdsByCrossIds($crossIds);
        // Get identities
        $idents  = array();
        foreach ($cfedIds as $cfedIdI => $cfedIdItem) {
            array_push($idents, $cfedIdItem['identity_id']);
        }
        foreach ($rawLogs as $logI => $logItem) {
            // add ids from logs
            array_push($idents, $logItem['from_id']);
            if ($logItem['action'] === 'exfee') {
                switch ($logItem['to_field']) {
                    case 'rsvp':
                        $rawLogs[$logI]['change_summy'] = explode(':', $logItem['change_summy']);
                        $changeId = $rawLogs[$logI]['change_summy'][0];
                        break;
                    case 'addexfee':
                    case 'delexfee':
                        $changeId = $logItem['change_summy'];
                }
                array_push($idents, $changeId);
            }
        }
        // @todo: Temporary unique
        // $idents  = $identityData->getIdentitiesByIdentityIds(array_unique($idents));
        $idents  = $identityData->getIdentitiesByIdentityIds(array_flip(array_flip($idents)));
        // Get human identity
        $hmIdent = array();
        $modUser = $this->getModelByName('user');
        // @todo: Temporary check
        if (is_array($idents)) {
            foreach ($idents as $identI => $identItem) {
                $hmIdent[$identItem['id']] = humanIdentity($identItem, $modUser->getUserByIdentityId($identItem['identity_id']));
            }
        }
        // Add confirmed informations into crosses
        foreach ($crosses as $crossI => $crossItem) {
            $crosses[$crossI]['confirmed'] = array();
            $crosses[$crossI]['numExfee']  = 0;
            foreach ($cfedIds as $cfedIdI => $cfedIdItem) {
                if ($cfedIdItem['cross_id'] === $crossItem['id']) {
                    if ($cfedIdItem['state'] === '1') {
                        array_push($crosses[$crossI]['confirmed'], $hmIdent[$cfedIdItem['identity_id']]);
                    }
                    $crosses[$crossI]['numExfee']++;
                }
            }
        }
        $this->setVar('crosses', $crosses);
        // Improve logs
        $logs        = array();
        $exfeeChange = array();
        $crossChange = array();
        foreach ($rawLogs as $logItem) {
            if (!isset($logs[$logItem['to_id']])) {
                foreach ($allCross as $crossI => $crossItem) {
                    if ($crossItem['id'] === $logItem['to_id']) {
                        $logs[$logItem['to_id']] = $crossItem;
                        unset($allCross[$crossI]);
                    }
                }
                if (!isset($logs[$logItem['to_id']])) {
                    continue;
                }
                $logs[$logItem['to_id']]['activity'] = array();
            }
            $logItem['from_name'] = $hmIdent[$logItem['from_id']];
            if ($logItem['action'] === 'conversation') {
            } else if ($logItem['action'] === 'change') {
                // merge the same field changes
                if (!isset($crossChange[$logItem['to_id']])) {
                    $crossChange[$logItem['to_id']] = array();
                }
                if (isset($crossChange[$logItem['to_id']][$logItem['to_field']])) {
                    continue;
                }
                $crossChange[$logItem['to_id']][$logItem['to_field']] = true;
            } else if ($logItem['action'] === 'rsvp' || $logItem['action'] === 'exfee') {
                switch ($logItem['to_field']) {
                    case '':
                        $changeId = $logItem['from_id'];
                        break;
                    case 'rsvp':
                        $changeId = $logItem['change_summy'][0];
                        break;
                    case 'addexfe':
                    case 'delexfe':
                        $changeId = $logItem['change_summy'];
                }
                if (isset($exfeeChange[$changeId])) {
                    continue;
                }
                $exfeeChange[$changeId] = true;
                $logItem['to_name'] = $hmIdent[$changeId];
            } else {
                continue;
            }
            array_push($logs[$logItem['to_id']]['activity'], $logItem);
        }
        foreach ($logs as $logI => $logItem) {
            if (!$logItem['activity']) {
                unset($logs[$logI]);
            }
        }
        $this->setVar('logs', $logs);
        // Get new invitations
        $idents = array();
        foreach ($identities as $identI => $identItem) {
            array_push($idents, $identItem['id']);
        }
        $newInvt = $modIvit->getNewInvitationsByIdentityIds($idents);
        // Get crosses of invitations
        if($newInvt)
            foreach ($newInvt as $newInvtI => $newInvtItem) {
                $identity = $identityData->getIdentityById($newInvtItem['identity_id']);
                $newInvt[$newInvtI]['sender'] = humanIdentity($identity, $modUser->getUserByIdentityId($newInvtItem['identity_id']));
                $newInvt[$newInvtI]['cross']  = $crossdata->getCross($newInvtItem['cross_id']);
            }
        $this->setVar('newInvt', $newInvt);

        $this->displayView();
    }


    public function doGetInvitation()
    {
        if (intval($_SESSION['userid']) <= 0) {
            //@todo: return error
            header( 'Location: /s/login' ) ;
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
            //@todo: return error
            header( 'Location: /s/login' ) ;
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
        $crosses   = $modCross->fetchCross($_SESSION['userid'], $today);
        $pastXs    = $modCross->fetchCross($_SESSION['userid'], $today, 'no', 'begin_at DESC', 20 - count($crosses));
        $anytimeXs = $modCross->fetchCross($_SESSION['userid'], $today, 'anytime', 'created_at DESC', 3);

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
            $crosses[$crossI]['exfee'] = array();
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
            header( 'Location: /s/login' ) ;
            exit(0);
        }


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
                $responobj["response"]["status"]="veryifing";

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

