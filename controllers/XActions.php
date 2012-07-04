<?php

class XActions extends ActionController {

    public function doIndex() {
        $modBackground = $this->getModelByName('background');
        $this->setVar('backgrounds', $modBackground->getAllBackground());
        $this->displayView();
    }


    // $_SESSION["tokenIdentity"]["token_expired"] 用来标记是否第一次打开token链接
    // 此参数setVar供view中使用
    public function doIndexOld() {
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


    public function doForbidden() {
        $referer = exGet("s");
        if($referer != ""){
            $referer = urldecode($referer);
        }
        $this->setVar('referer', $referer);
        $this->setVar('cross_id', $cross_id);
        $this->displayView();
    }

}
