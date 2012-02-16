<?php
class UsersActions extends ActionController {

    public function doIndex()
    {
    }
    public function doGetProfile()
    {
        $params=$this->params;
        $uid=$params["id"];

        $checkhelper=$this->getHelperByName("check");
        $check=$checkhelper->isAPIAllow("user_getprofile",$params["token"],array("user_id"=>$params["id"]));
        if($check["check"]==false)
        {
            $responobj["meta"]["code"]=403;
            $responobj["meta"]["error"]="forbidden";
            echo json_encode($responobj);
            exit(0);
        }
        $identityData=$this->getModelByName("identity");
        $userData=$this->getModelByName("user");
        $identity=$identityData->getIdentitiesByUser($uid);
        $user=$userData->getUser($uid);

        $responobj["meta"]["code"]=200;
        $responobj["response"]["identities"]=$identity;
        $responobj["response"]["user"]=$user;
        echo json_encode($responobj);
        exit(0);
    }
    public function doLogin()
    {
        $userData=$this->getModelByName("user");
        $user=$_POST["user"];
        $password=$_POST["password"];
        $result=$userData->loginForAuthToken($user,$password);
        if($result)
        {
            $responobj["meta"]["code"]=200;
            $responobj["response"]=$result;;
        }
        else
        {
            $responobj["meta"]["code"]=404;
            $responobj["meta"]["err"]="login error";
        }

        echo json_encode($responobj);
        exit(0);
    }
    public function doGetUpdate()
    {
        $params=$this->params;
        $uid=$params["id"];
        $device_identity_id=$params["ddid"];

        $checkhelper=$this->getHelperByName("check");
        $check=$checkhelper->isAPIAllow("user_getupdate",$params["token"],array("user_id"=>$params["id"]));
        if($check["check"]==false)
        {
            $responobj["meta"]["code"]=403;
            $responobj["meta"]["error"]="forbidden";
            echo json_encode($responobj);
            exit(0);
        }
        $shelper=$this->getHelperByName("s");
        $rawLogs=$shelper->GetAllUpdate($uid, urldecode($params["updated_since"]), 200);
		
		// merge logs by @Leask {
        $preItemIdx  = 0;
        $preItemDna  = '';
        $preItemTime = 0;
        foreach ($rawLogs as $logI => $logItem) {
            $curDna  = ($logItem['by_identity']['user_id'] ?: $logItem['by_identity']['id']) . "_{$logItem['action']}_{$logItem['x_id']}";
			$curTime = strtotime($logItem['time']);
			switch ($logItem['action']) {
				case 'addexfee':
            	case 'delexfee':
                case 'confirmed':
                case 'declined':
				case 'interested':
					$expansion = true;
					break;
				default:
					$expansion = false;
			}
            if ($curDna === $preItemDna && abs($preItemTime - $curTime) <= 153) { // 2:33
				if ($expansion) {
					array_push($rawLogs[$preItemIdx]['to_identity'], $logItem['to_identity']);
				}
            	unset($rawLogs[$logI]);
			} else {
				if ($expansion) {
            		$rawLogs[$logI]['to_identity'] = array($logItem['to_identity']);
					$preItemIdx  = $logI;
				}
            }
			$preItemDna  = $curDna;
			$preItemTime = $curTime;
        }
		$cleanLogs = array_merge($rawLogs);
		// }

        $identityhelper=$this->getHelperByName("identity");
        $identityhelper->cleanIdentityBadgeNumber($device_identity_id,$uid);

        $responobj["meta"]["code"]=200;
        $responobj["response"]=$cleanLogs;
        echo json_encode($responobj);

    }
    public function doX()
    {
        //check if this token allow 
        $params=$this->params;
        $checkhelper=$this->getHelperByName("check");
        $check=$checkhelper->isAPIAllow("user_x",$params["token"],array("user_id"=>$params["id"]));
        if($check["check"]==false)
        {
            $responobj["meta"]["code"]=403;
            $responobj["meta"]["error"]="forbidden";
            echo json_encode($responobj);
            exit(0);
        }
        //print "user/x";
        //print $params["id"];
        //print "<br/>";
        //print $params["updated_since"];

        $Data=$this->getModelByName("X");
        $crosses=$Data->getCrossByUserId(intval($params["id"]),urldecode($params["updated_since"]));
        if($crosses=="")
            $crosses=array();
        
        $conversationData=$this->getModelByName("conversation");
        $identityData=$this->getModelByName("identity");
        $invitationData=$this->getModelByName("invitation");
        $userData=$this->getModelByName("user");
        for($i=0;$i<sizeof($crosses);$i++)
        {
            $cross_id=intval($crosses[$i]["id"]);
            if($cross_id>0)
            {
                #$conversations=$conversationData->getConversation($cross_id,'cross',10);
                $conversations = $conversationData->getConversationByTimeStr($cross_id,"cross",urldecode($params["updated_since"]));
                $crosses[$i]["conversations"]=$conversations;
                $identity=$identityData->getIdentityById(intval($crosses[$i]["host_id"]));
                $user=$userData->getUserByIdentityId(intval($crosses[$i]["host_id"]));
                $crosses[$i]["host"]=humanIdentity($identity,$user);
                $invitations=$invitationData->getInvitation_Identities($crosses[$i]["id"],true);
                $crosses[$i]["invitations"]=$invitations;
                //invitations
            }
        }



        $responobj["meta"]["code"]=200;
        $responobj["response"]["crosses"]=$crosses;
        echo json_encode($responobj);
        
        //get x by id and updated_since
        
    }
    
    public function doRegdevicetoken()
    {
        //check if this token allow 
        $params=$this->params;
        $checkhelper=$this->getHelperByName("check");
        $uid=$params["id"];
        $check=$checkhelper->isAPIAllow("user_regdevicetoken",$params["token"],array("user_id"=>$params["id"]));
        if($check["check"]==false)
        {
            $responobj["meta"]["code"]=403;
            $responobj["meta"]["error"]="forbidden";
            echo json_encode($responobj);
            exit(0);
        }
        $devicetoken=$_POST["devicetoken"];
        $provider=$_POST["provider"];
        $devicename=$_POST["devicename"];
        $userData=$this->getModelByName("user");
        $identity_id=$userData->regDeviceToken($devicetoken,$devicename,$provider,$uid);
        if(intval($identity_id)>0)
        {
            $responobj["meta"]["code"]=200;
            $responobj["response"]["device_token"]=$devicetoken;
            $responobj["response"]["identity_id"]=$identity_id;
        }
        else
        {
            $responobj["meta"]["code"]=500;
            $responobj["meta"]["error"]="reg device token error";
        }
        echo json_encode($responobj);
        exit(0);
        //add devicetoken with $check["uid"]
    }

}
