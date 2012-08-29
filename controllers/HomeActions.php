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
        $twitterSignin = null;
        if ($oauthIfo) {
            $twitterSignin
          = $oauthIfo['twitter_signin']
          ? ['authorization'   => $oauthIfo['twitter_signin'],
             'identity_id'     => $oauthIfo['twitter_identity_id'],
             'following'       => $oauthIfo['twitter_following'],
             'identity_status' => $oauthIfo['twitter_identity_status'],
             'type'            => 'twitter']
          : ['authorization'   => null,
             'type'            => 'twitter'];
        }
        $modOauth->resetSession();
        // show page
        $this->setVar('backgrounds',    $modBackground->getAllBackground());
        $this->setVar('twitter_signin', $twitterSignin);
        $this->displayView();
    }

}
