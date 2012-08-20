<?php

class HomeActions extends ActionController {

    public function doIndex() {
        // load models
        $modOauth      = $this->getModelByName('OAuth', 'v2');
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
