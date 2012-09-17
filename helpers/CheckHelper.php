<?php

class CheckHelper extends ActionController {

    function isAPIAllow($api, $token, $args = array()) {
        $userData     = $this->getModelByName('user');
        $identityData = $this->getModelByName('identity');
        $exfeeData    = $this->getModelByName('exfee');
        $crossData    = $this->getModelByName('cross');

        $token        = $token ?: $_SERVER['HTTP_TOKEN'];
        $objToken     = $userData->getUserToken($token);
        $uid          = $objToken['data']['user_id'];

        if (!$uid) {
            $invToken = $crossData->getCrossAccessToken($token);
            if ($invToken
             && $invToken['data']
             && $invToken['data']['token_type'] === 'cross_access_token'
             && !$invToken['is_expire']) {
                $invitation = $exfeeData->getRawInvitationByCrossIdAndIdentityId(
                    $invToken['data']['cross_id'], $invToken['data']['identity_id']
                );
                if ($invitation) {
                    switch ($api) {
                        case 'cross_edit':
                            if ((int) $args['cross_id'] === $invitation['cross_id']) {
                                return [
                                    'check'          => true,
                                    'uid'            => 0,
                                    'exfee_id'       => $invitation['exfee_id'],
                                    'by_identity_id' => $invitation['identity_id'],
                                ];
                            }
                            break;
                        case 'conversation':
                            if ((int) $args['exfee_id'] === $invitation['exfee_id']) {
                                return [
                                    'check'          => true,
                                    'uid'            => 0,
                                ];
                            }
                            break;
                        case 'conversation_add':
                            if ((int) $args['exfee_id'] === $invitation['exfee_id']) {
                                return [
                                    'check'          => true,
                                    'uid'            => 0,
                                    'by_identity_id' => $invitation['identity_id'],
                                ];
                            }
                    }
                }
            }
            return array('check' => false, 'uid' => 0);
        }

        switch ($api) {
            case 'cross':
            case 'cross_edit':
                $exfee_id=$crossData->getExfeeByCrossId($args["cross_id"]);
                $userids=$exfeeData->getUserIdsByExfeeId($exfee_id, true);
                if (in_array($uid, $userids)) {
                    if ($api=="cross_edit") {
                        $by_identity_id=$args["by_identity_id"];
                        if (intval($by_identity_id)>0) {
                            $r=$identityData->isIdentityBelongsUser($by_identity_id,$uid);
                            if ($r===true) {
                                return array("check"=>true,"uid"=>$uid,"exfee_id"=>$exfee_id,"by_identity_id"=>$by_identity_id);
                            } else {
                                return array("check"=>false);
                            }
                        } else {
                            return array("check"=>false);
                        }
                    }
                    return array("check"=>true,"uid"=>$uid,"exfee_id"=>$exfee_id);
                }
                break;
            case 'cross_add':
                $by_identity_id=$args["by_identity_id"];
                $r=$identityData->isIdentityBelongsUser($by_identity_id,$uid);
                if ($r===true) {
                    return array("check"=>true,"uid"=>$uid,"by_identity_id"=>$by_identity_id);
                } else {
                    return array("check"=>false);
                }
                break;
            case 'conversation':
                $userids = $exfeeData->getUserIdsByExfeeId($args['exfee_id'], true);
                if (in_array($uid, $userids)) {
                    return array('check' => true, 'uid' => $uid);
                }
                break;
            case 'conversation_add':
                $userids = $exfeeData->getUserIdsByExfeeId($args['exfee_id'], true);
                $idntIds = $exfeeData->getIdentityIdsByExfeeId($args['exfee_id']);
                if (in_array($uid, $userids)
                 && in_array($args['identity_id'], $idntIds)) {
                    return array('check' => true, 'uid'=>$uid, 'by_identity_id'=>$args['identity_id']);
                }
                break;
            case 'conversation_del':
                if ($uid==$args["user_id"]) {
                    return array("check"=>true,"uid"=>$uid);
                }
                break;
            case 'user_self':
                return array('check' => $uid == $args['user_id'], 'uid' => $uid);
                break;
            case 'user_crosses':
                if ($uid==$args["user_id"]) {
                    return array("check"=>true,"uid"=>$uid);
                }
                break;
            case 'user_signin':
            case 'user_signup':
                return array("check"=>true);
            case 'user_signout':
            case 'user_edit':
            case 'user_regdevice':
                return [
                    'check' => true,
                    'uid'   => $uid,
                    'fresh' => time() - $objToken['data']['last_authenticate'] <= 60 * 15 // in 15 mins
                ];
        }
        return array("check"=>false);
    }

}
