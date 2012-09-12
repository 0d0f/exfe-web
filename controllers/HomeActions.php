<?php

class HomeActions extends ActionController {

    public function doIndex() {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')
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
        $modBackground = $this->getModelByName('background');
        // check oauth session
        $oauthIfo      = $modOauth->getSession();
        $oauthRst      = null;
        if ($oauthIfo && isset($oauthIfo['provider']) && $oauthIfo['provider']) {
            $oauthRst  = [
                'authorization' => null,
                'provider'      => $oauthIfo['provider'],
            ];
            if ($oauthIfo['oauth_signin']) {
                $oauthRst['authorization']   = $oauthIfo['oauth_signin'];
                $oauthRst['identity_id']     = $oauthIfo['identity_id'];
                $oauthRst['identity_status'] = $oauthIfo['identity_status'];
                if ($oauthIfo['provider'] === 'twitter') {
                    $oauthRst['twitter_following'] = $oauthIfo['twitter_following'];
                }
                if (isset($oauthIfo['workflow'])) {
                    if (isset($oauthIfo['workflow']['callback']['url'])) {
                        $oauthRst['callback']          = $oauthIfo['workflow']['callback']['url'];
                    }
                    if (isset($oauthIfo['workflow']['callback']['args'])) {
                        $oauthRst['args']              = $oauthIfo['workflow']['callback']['args'];
                    }
                }
            }
        }
        $modOauth->resetSession();
        // show page
        $this->setVar('backgrounds', $modBackground->getAllBackground());
        $this->setVar('oauth',       $oauthRst);
        $this->displayView();
    }

}
