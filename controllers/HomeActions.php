<?php

class HomeActions extends ActionController {

    public function doIndex() {

        // shorttoken debuging {
        // $modAuth = $this->getModelByName('ExfeAuth');
        // $a = $modAuth->generateToken(['a' => 1, 'b' => 2], ['c' => 3, 'd' => 4], 1000, true);
        // $a = $modAuth->getToken('8439', true);
        // $a = $modAuth->updateToken('8439', ['e' => 5, 'f' => 6], true);
        // $a = $modAuth->refreshToken('8439', 10000, true);
        // $a = $modAuth->findToken(['a' => 1, 'b' => 2], true);
        // $a = $modAuth->expireToken('4456', true);
        // $a = $modAuth->expireAllTokens(['a' => 1, 'b' => 2], true);
        // var_dump($a);
        // return;
        // }

        // $mod = $this->getModelByName('Photo');
        // $pris = $mod->getAlbumsFromFacebook(391);
        // //$pris = $mod->getPhotosFromFacebook(391, 10150805288363636);
        // print_r($pris);
        // return;

        // rsvp
        $modExfee = $this->getModelByName('Exfee');
        $modUser  = $this->getModelByName('User');
        $token = mysql_real_escape_string($_GET['token']);
        $rsvp  = strtolower(mysql_real_escape_string($_GET['rsvp']));
        if ($token && $rsvp) {
            if (($objToken = $modExfee->getRawInvitationByToken($token))
             && $objToken['valid']
             && $rsvp === 'accept') {
                $user_id = $modUser->getUserIdByIdentityId($objToken['identity_id']);
                $rsvp    = new stdClass;
                $rsvp->identity_id    = $objToken['identity_id'];
                $rsvp->rsvp_status    = 'ACCEPTED';
                $rsvp->by_identity_id = $objToken['identity_id'];
                $modExfee->updateExfeeRsvpById(
                    $objToken['exfee_id'], [$rsvp], $objToken['identity_id'], $user_id
                );
                header("location: /#!token={$token}");
            } else if ($rsvp === 'accept') {
                header("location: /#!token={$token}/accept");
            }
        }
        // get sms token
        $this->setVar('sms_token', null);
        if (isset($_GET['t'])) {
            $t = mysql_real_escape_string($_GET['t']);
            if (($objToken = $modUser->resolveToken($t, true))) {
                $objToken['origin_token'] = $t;
            }
            $this->setVar('sms_token', $objToken ?: false);
        }
        // case USER_AGENT
        if (!isset($_GET['ipad'])
         && (strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone')
          || strpos($_SERVER['HTTP_USER_AGENT'], 'iPad')
          || strpos($_SERVER['HTTP_USER_AGENT'], 'iPod'))) {
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
        } else if (strpos($_SERVER['HTTP_USER_AGENT'], 'Android')
                || strpos($_SERVER['HTTP_USER_AGENT'], 'BlackBerry')
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
        // load models
        $modOauth      = $this->getModelByName('OAuth');
        $modBackground = $this->getModelByName('Background');
        $modMap        = $this->getModelByName('Map');
        // check oauth session
        $oauthIfo      = $modOauth->getSession();
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
                if (isset($oauthIfo['workflow'])) {
                    if (isset($oauthIfo['workflow']['callback']['url'])) {
                        $oauthRst['refere']             = $oauthIfo['workflow']['callback']['url'];
                    }
                    if (isset($oauthIfo['workflow']['callback']['args'])) {
                        $oauthRst['event']              = $oauthIfo['workflow']['callback']['args'];
                    }
                    if (isset($oauthIfo['workflow']['verification_token'])) {
                        $oauthRst['verification_token'] = $oauthIfo['workflow']['verification_token'];
                    }
                }
            }
        }
        $modOauth->resetSession();
        // show page
        $this->setVar('backgrounds', $modBackground->getAllBackground());
        $this->setVar('oauth',       $oauthRst);
        $this->setVar('location',    $modMap->getCurrentLocation());
        $this->displayView();
    }

}
