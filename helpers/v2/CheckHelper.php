<?php

class CheckHelper extends ActionController {

    function isAPIAllow($api, $token, $args = array()) {
        $userData     = $this->getModelByName('user',     'v2');
        $identityData = $this->getModelByName('identity', 'v2');
        $exfeeData    = $this->getModelByName('exfee',    'v2');
        $crossData    = $this->getModelByName('cross',    'v2');

        $uid = intval($userData->getUserIdByToken($token));

        if (!$uid) {
            $invitation = $exfeeData->getRawInvitationByToken($token);
            if ($invitation && $invitation['valid']) {
                switch ($api) {
                    case 'cross_edit':
                        if ((int) $args['cross_id'] === $invitation['cross_id']) {
                            return ['check' => true, 'uid' => 0, 'exfee_id' => $invitation['exfee_id'], 'by_identity_id' => $invitation['identity_id']];
                        }
                        break;
                    case 'conversation':
                        if ((int) $args['exfee_id'] === $invitation['exfee_id']) {
                            return ['check' => true, 'uid' => 0];
                        }
                        break;
                    case 'conversation_add':
                        if ((int) $args['exfee_id'] === $invitation['exfee_id']) {
                            return ['check' => true, 'uid' => 0, 'by_identity_id' => $invitation['identity_id']];
                        }
                }
            }
            return array('check' => false, 'uid' => 0);
        }

        switch ($api) {
            case 'cross':
            case 'cross_edit':
                $exfee_id=$crossData->getExfeeByCrossId($args["cross_id"]);
                $userids=$exfeeData->getUserIdsByExfeeId($exfee_id);
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
                $userids = $exfeeData->getUserIdsByExfeeId($args['exfee_id']);
                if (in_array($uid, $userids)) {
                    return array('check' => true, 'uid' => $uid);
                }
                break;
            case 'conversation_add':
                $userids = $exfeeData->getUserIdsByExfeeId($args['exfee_id']);
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
                break;
            case 'user_edit':
                return array('check' => true, 'uid' => $uid);
                break;
        }
        return array("check"=>false);
    }

}
