<?php

class HomeActions extends ActionController {

    public function doIndex() {
        // rsvp
        $modCross = $this->getModelByName('Cross');
        $modExfee = $this->getModelByName('Exfee');
        $modUser  = $this->getModelByName('User');
        $token = dbescape($_GET['token']);
        $rsvp  = strtolower(dbescape($_GET['rsvp']));
        if ($token && $rsvp) {
            if (($objToken = $modExfee->getRawInvitationByToken($token))
             && $objToken['valid']
             && $rsvp === 'accept') {
                $user_id = $modUser->getUserIdByIdentityId($objToken['identity_id']);
                $rsvp    = new stdClass;
                $rsvp->identity_id    = $objToken['identity_id'];
                $rsvp->rsvp_status    = 'ACCEPTED';
                $rsvp->by_identity_id = $objToken['identity_id'];
                $rawCross = $modCross->getCross($objToken['cross_id']);
                $modExfee->updateExfeeRsvpById(
                    $objToken['exfee_id'], [$rsvp], $objToken['identity_id'],
                    $user_id, (int) $rawCross['state'] === 0
                );
                touchCross($objToken['cross_id'], $user_id);
                header("location: /#!token={$token}");
            } else if ($rsvp === 'accept') {
                header("location: /#!token={$token}/accept");
            }
        }
        // get sms token
        $this->setVar('sms_token', null);
        if (isset($_GET['t'])) {
            $t = dbescape($_GET['t']);
            if (($objToken = $modUser->resolveToken($t))) {
                $objToken['origin_token'] = $t;
            }
            $this->setVar('sms_token', $objToken ?: false);
        }
        // load models
        $modOauth      = $this->getModelByName('OAuth');
        $modBackground = $this->getModelByName('Background');
        $modMap        = $this->getModelByName('Map');
        // check oauth session
        $oauthIfo      = $modOauth->getSession();
        // @todo wechat debug {
        if (@$oauthIfo['provider'] === 'wechat') {
            error_log(json_encode($oauthIfo));
        }
        // }
        // check xcode {
        $smith_id    = 0;
        $exfee_id    = 0;
        $title       = 'EXFE - The group utility for gathering.';
        if (isset($_GET['xcode'])) {
            $invitation = $modExfee->getRawInvitationByToken($_GET['xcode']);
            if ($invitation && $invitation['state'] !== 4) {
                if (in_array($invitation['identity_id'], explode(',', SMITH_BOT))) {
                    $smith_id = $invitation['identity_id'];
                }
                $exfee_id = $invitation['exfee_id'];
                $rawCross = $modCross->getCross($invitation['cross_id']);
                if ($rawCross && $rawCross['title']) {
                    $title = $rawCross['title'];
                }
            }
        } else {
            $rawId  = @$this->tails[1] ?: '';
            $regExp = '/^!(.*)$/';
            if (preg_match($regExp, $rawId)) {
                $cross_id = (int) preg_replace($regExp, '$1', $rawId);
                $exfee_id = (int) $modExfee->getExfeeIdByCrossId($cross_id);
            }
        }
        // }
        $this->setVar('smith_id', $smith_id);
        $this->setVar('exfee_id', $exfee_id);
        $oauthRst      = null;
        if ($oauthIfo) {
            $oauthRst  = ['authorization' => null];
            if ($oauthIfo['oauth_signin']) {
                $oauthRst['authorization']   = $oauthIfo['oauth_signin'];
                $oauthRst['data']            = [
                    'identity'        => $oauthIfo['identity'],
                    'identity_status' => $oauthIfo['identity_status'],
                ];
                if ($oauthIfo['identity']->provider === 'twitter') {
                    $oauthRst['data']['twitter_following'] = $oauthIfo['twitter_following'];
                }
                if (isset($oauthIfo['workflow'])
                 && isset($oauthIfo['workflow']['verification_token'])) {
                    $oauthRst['verification_token'] = $oauthIfo['workflow']['verification_token'];
                }
            }
            if (isset($oauthIfo['workflow'])) {
                if (isset($oauthIfo['workflow']['callback']['url'])) {
                    $oauthRst['refere'] = $oauthIfo['workflow']['callback']['url'];
                }
                if (isset($oauthIfo['workflow']['callback']['args'])) {
                    $oauthRst['event']  = $oauthIfo['workflow']['callback']['args'];
                }
            }
        }
        $modOauth->resetSession();
        // show page
        $this->setVar('title',       $title);
        $this->setVar('backgrounds', $modBackground->getAllBackground());
        $this->setVar('oauth',       $oauthRst);
        $this->setVar('location',    $modMap->getCurrentLocation());
        // case USER_AGENT
        if (!isset($_GET['ipad'])
         && !isset($_COOKIE['ipad'])
         && (strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone')
          || strpos($_SERVER['HTTP_USER_AGENT'], 'iPad')
          || strpos($_SERVER['HTTP_USER_AGENT'], 'iPod')
          || strpos($_SERVER['HTTP_USER_AGENT'], 'Android')
          || strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger'))) {
            $this->displayViewByAction('mobile');
            return;
        } else if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')
               && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 8.0')
                || strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 7.0')
                || strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 6.0')
                || strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 5.5')
                || strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 5.0'))) {
            $this->displayViewByNameAction('matters', 'browser_matters');
            return;
        } else if (strpos($_SERVER['HTTP_USER_AGENT'], 'BlackBerry')
                || strpos($_SERVER['HTTP_USER_AGENT'], 'Nokia')
                || strpos($_SERVER['HTTP_USER_AGENT'], 'Symbian')
                || strpos($_SERVER['HTTP_USER_AGENT'], 'SymbOS')
                || strpos($_SERVER['HTTP_USER_AGENT'], 'Series 60')
                || strpos($_SERVER['HTTP_USER_AGENT'], 'S60')
                || strpos($_SERVER['HTTP_USER_AGENT'], 'Windows Phone')
                || strpos($_SERVER['HTTP_USER_AGENT'], 'Windows CE')
                || strpos($_SERVER['HTTP_USER_AGENT'], 'IEMobile')
                || strpos($_SERVER['HTTP_USER_AGENT'], 'SonyEricsson')
                || strpos($_SERVER['HTTP_USER_AGENT'], 'Kindle')
                || strpos($_SERVER['HTTP_USER_AGENT'], 'NetFront')
                || strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mini')
                || strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mobi')
                || strpos($_SERVER['HTTP_USER_AGENT'], 'J2ME')
                || strpos($_SERVER['HTTP_USER_AGENT'], 'MIDP')
                || strpos($_SERVER['HTTP_USER_AGENT'], 'Skyfire')
                || strpos($_SERVER['HTTP_USER_AGENT'], 'Fennec')) {
            // @todo
            // return;
        }
        if (isset($_GET['ipad'])) {
            setcookie('ipad', true, time() + 60 * 60 * 24);
        }
        $this->displayView();
    }

}
