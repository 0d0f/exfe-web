<?php

class PhotosActions extends ActionController {

    public function doGetFacebookAlbums() {
        // check signin
        $checkHelper = $this->getHelperByName('check');
        $params = $this->params;
        $result = $checkHelper->isAPIAllow('user_edit', $params['token']);
        if ($result['check']) {
            $user_id = $result['uid'];
        } else {
            apiError(401, 'no_signin', ''); // 需要登录
        }
        // get user
        $modUser = $this->getModelByName('User');
        $user    = $modUser->getUserById($user_id);
        if (!$user) {
            apiError(401, 'no_signin', ''); // 需要登录
        }
        // check identity
        $identity_id = @ (int) $_POST['identity_id'];
        if (!$identity_id) {
            apiError(400, 'no_identity_id', ''); // 需要输入identity_id
        }
        $objIdentity = null;
        foreach ($user->identities as $identity) {
            if ($identity->id === $identity_id) {
                $objIdentity = $identity;
                break;
            }
        }
        if (!$objIdentity) {
            apiError(400, 'can_not_be_verify', 'This identity does not belong to current user.');
        }
        if ($objIdentity->status !== 'CONNECTED') {
            apiError(400, 'can_not_be_verify', 'This identity is not connecting to current user.');
        }
        if ($objIdentity->provider !== 'facebook') {
            apiError(400, 'bad_request', 'This identity is not a Facebook identity.');
        }
        // get albums
        $modPhoto = $this->getModelByName('Photo');
        $albums = $modPhoto->getAlbumsFromFacebook($identity_id);
        if ($albums === null) {
            apiError(400, 'not_allow', 'Can not access photos, please reauthenticate this identity.');
        }
        apiResponse(['albums' => $albums]);
    }


    public function addAlbumsToCross() {

    }

}
