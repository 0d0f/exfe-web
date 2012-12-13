<?php

class HomeActions extends ActionController {

    public function doIndex() {
        // rsvp
        $modExfee = $this->getModelByName('Exfee');
        $token = mysql_real_escape_string($_GET['token']);
        $rsvp  = strtolower(mysql_real_escape_string($_GET['rsvp']));
        if ($token && $rsvp) {
            if (($objToken = $modExfee->getRawInvitationByToken($token))
             && $objToken['valid']
             && $rsvp === 'accept') {
                $modUser = $this->getModelByName('User');
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
        // case USER_AGENT
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone')
         || strpos($_SERVER['HTTP_USER_AGENT'], 'iPad')
         || strpos($_SERVER['HTTP_USER_AGENT'], 'iPod')) {
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
