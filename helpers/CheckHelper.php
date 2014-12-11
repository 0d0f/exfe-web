<?php

class CheckHelper extends ActionController {

    function isAPIAllow($api, $token, $args = array(), $withTokenInfo = false) {
        $userData     = $this->getModelByName('user');
        $identityData = $this->getModelByName('identity');
        $exfeeData    = $this->getModelByName('exfee');
        $crossData    = $this->getModelByName('cross');
        // get token from token service {
        if (isset($_SERVER['HTTP_EXFE_AUTH_VERSION'])
         && (int) $_SERVER['HTTP_EXFE_AUTH_VERSION'] === 1
         && ($objToken = json_decode($_SERVER['HTTP_EXFE_AUTH_DATA'], true))) {
            $objToken  = [
                'key'        => $token,
                'data'       => $objToken,
                'touched_at' => (int) $_SERVER['HTTP_EXFE_AUTH_TOUCHED_AT'],
                'expire_at'  => (int) $_SERVER['HTTP_EXFE_AUTH_EXPIRES_AT'],
            ];
        } else {
            $objToken  = $userData->getUserToken($token);
        }
        // }
        $uid = $objToken['data']['user_id'];

        if (!$uid) {
            $invToken = $crossData->getCrossAccessToken($token);
            if ($invToken
             && $invToken['data']
             && $invToken['data']['token_type'] === 'cross_access_token') {
                $invitation = $exfeeData->getRawInvitationByCrossIdAndIdentityId(
                    $invToken['data']['cross_id'], $invToken['data']['identity_id']
                );
                if ($invitation) {
                    switch ($api) {
                        case 'cross':
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
            } else if ($api === 'user_icses') {
                $calToken = $userData->getUserCalendarToken($token);
                if ($calToken
                 && $calToken['data']
                 && $calToken['data']['token_type'] === 'calendar_token'
                 && $calToken['data']['user_id']    === (int) $args['user_id']) {
                    return ['check' => true, 'uid' => (int) $args['user_id']];
                }
            } else if ($api === 'conversation') {
                $rawInvitation = $exfeeData->getRawInvitationByToken($token);
                $user_id       = $userData->getUserIdByIdentityId($rawInvitation['identity']);
                if ($rawInvitation && $rawInvitation['state'] !== 4) {
                    return ['check' => true, 'uid' => $user_id];
                }
            }
            return array('check' => false, 'uid' => 0);
        }

        // update profile and friends {
        $identityUpdateKey = 'user_identities_update';
        $updateTime = getObjectTouchTime($identityUpdateKey, '-', $uid);
        if (!$updateTime || time() - $updateTime >= 86400) { // 60 * 60 * 24
            touchObject($identityUpdateKey, '-', $uid);
            $hlpQueue = $this->getHelperByName('Queue');
            $user = $userData->getUserById($uid);
            foreach ($user && $user->identities ? $user->identities : [] as $identity) {
                if (in_array($identity->provider, $identityData->providers['authenticate'])) {
                    $oAuthToken = $identityData->getOAuthTokenById($identity->id);
                    if ($oAuthToken) {
                        $hlpQueue->updateIdentity($identity, $oAuthToken);
                        $hlpQueue->updateFriends($identity,  $oAuthToken);
                    }
                } else if ($identity->provider === 'email') {
                    $hlpQueue->updateIdentity($identity, []);
                }
            }
        }
        // }

        switch ($api) {
            case 'cross':
            case 'cross_edit':
                $exfee_id = $crossData->getExfeeByCrossId($args['cross_id']);
                $userids  = $exfeeData->getUserIdsByExfeeId($exfee_id, true);
                if (in_array($uid, $userids)) {
                    if ($api == 'cross_edit') {
                        $by_identity_id = $args['by_identity_id'];
                        if (intval($by_identity_id)>0) {
                            // @todo maybe 安全漏洞！ by @leaskh
                            $r = $identityData->isIdentityBelongsUser($by_identity_id, $uid, false);
                            if ($r === true) {
                                return array('check' => true, 'uid' => $uid, 'exfee_id' => $exfee_id, 'by_identity_id' => $by_identity_id);
                            } else {
                                return array('check' => false);
                            }
                        } else {
                            return array('check' => false);
                        }
                    }
                    return array('check' => true, 'uid' => $uid, 'exfee_id' => $exfee_id);
                }
                break;
            case 'cross_edit_by_user':
                $exfee_id = $crossData->getExfeeByCrossId($args['cross_id']);
                $userids  = $exfeeData->getUserIdsByExfeeId($exfee_id, true);
                if (in_array($uid, $userids)) {
                    return array('check' => true, 'uid' => $uid);
                }
                break;
            case 'cross_add':
                $by_identity_id = $args["by_identity_id"];
                // @todo maybe 安全漏洞！ by @leaskh
                $r = $identityData->isIdentityBelongsUser($by_identity_id, $uid, false);
                if ($r === true) {
                    return array("check" => true, "uid" => $uid, "by_identity_id" => $by_identity_id);
                } else {
                    return array("check" => false);
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
                 || in_array($args['identity_id'], $idntIds)) {
                    return array('check' => true, 'uid' => $uid, 'by_identity_id' => $args['identity_id']);
                }
                break;
            case 'conversation_del':
                if ($uid == $args["user_id"]) {
                    return array("check" => true, "uid" => $uid);
                }
                break;
            case 'user':
                return array('check' => true, 'uid' => $uid);
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
                $rtResult = [
                    'check' => true,
                    'uid'   => $uid,
                    'fresh' => time() - $objToken['data']['last_authenticate'] <= 60 * 15 // in 15 mins
                ];
                if ($withTokenInfo) {
                    $rtResult['token_info'] = $objToken;
                }
                return $rtResult;
        }
        return ['check' => false];
    }

}
