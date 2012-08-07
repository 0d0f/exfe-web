<?php

class HomeActions extends ActionController {

    public function doIndex() {
        // load models
        $modOauth      = $this->getModelByName('OAuth', 'v2');
        $modBackground = $this->getModelByName('background');
        // check oauth session
        $oauthIfo      = $modOauth->getSession();
        $twitterSignin = null;
        if ($oauthIfo && $oauthIfo['twitter_signin']) {
            $twitterSignin = [
                'authorization' => $oauthIfo['twitter_signin'],
                'following'     => $oauthIfo['twitter_following'],
                'type'          => 'twitter',
            ];
        }
        $modOauth->resetSession();
        // show page
        $this->setVar('backgrounds',    $modBackground->getAllBackground());
        $this->setVar('twitter_signin', $twitterSignin);
        $this->displayView();
    }

}

