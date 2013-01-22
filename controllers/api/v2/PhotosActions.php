<?php

class PhotosActions extends ActionController {

    public function doIndex() {
        $params = $this->params;
        $checkHelper = $this->getHelperByName('check');
        $result = $checkHelper->isAPIAllow('cross', $params['token'], ['cross_id' => $params['id']]);
        if ($result['check'] !== true) {
            if ($result['uid'] === 0)
                apiError(401, 'invalid_auth', '');
            else
                apiError(403, 'not_authorized', "The X you're requesting is private.");
        }
        $crossHelper = $this->getHelperByName('cross');
        $cross = $crossHelper->getCross($params['id']);
        if ($cross) {
            if ($cross->attribute['deleted']) {
                apiError(403, 'not_authorized', "The X you're requesting is private.");
            }
            $modPhotos = $this->getModelByName('Photo');
            $photos = $modPhotos->getPhotosByCrossId($params['id']);
            apiResponse(['photos' => $photos]);
        }
        apiError(400, 'param_error', "The X you're requesting is not found.");
    }


    public function doGetAlbums() {
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
        // get albums
        $modPhoto = $this->getModelByName('Photo');
        $albums   = null;
        switch ($objIdentity->provider) {
            case 'facebook':
                $albums = $modPhoto->getAlbumsFromFacebook($identity_id);
                break;
            case 'instagram':
                break;
            case 'dropbox':
                break;
            default:
                apiError(400, 'unsupported_provider', 'This photo provider is not supported currently.');
        }
        if ($albums === null) {
            apiError(400, 'not_allow', 'Can not access photos, please reauthenticate this identity.');
        }
        apiResponse(['albums' => $albums]);
    }


    public function doAddAlbumsToCross() {
        // check signin
        $checkHelper = $this->getHelperByName('check');
        $params   = $this->params;
        $cross_id = @ (int) $_POST['cross_id'];
        $result   = $checkHelper->isAPIAllow('cross_edit_by_user', $params['token'], ['cross_id' => $cross_id]);
        if ($result['check']) {
            $user_id = $result['uid'];
        } else if ($result['uid'] === 0) {
            apiError(401, 'no_signin', ''); // 需要登录
        } else {
            apiError(403, 'not_authorized', "The X you're requesting is private.");
        }
        // check identity
        $identity_id = @ (int) $_POST['identity_id'];
        if (!$identity_id) {
            apiError(400, 'no_identity_id', ''); // 需要输入identity_id
        }
        $modIdentity = $this->getModelByName('Identity');
        $identity = $modIdentity->getIdentityById($identity_id);
        if (!$identity || $identity->connected_user_id !== $user_id) {
            apiError(400, 'can_not_be_verify', 'This identity does not belong to current user.');
        }
        // check album id
        $album_id    = @ (int) $_POST['album_id'];
        if (!$album_id) {
            apiError(400, 'no_album_id', '');
        }
        // add album to cross
        $modPhoto = $this->getModelByName('Photo');
        $result   = null;
        switch ($identity->provider) {
            case 'facebook':
                $result = $modPhoto->addFacebookAlbumToCross($album_id, $cross_id, $identity_id);
                break;
            case 'instagram':
                break;
            case 'dropbox':
                break;
            default:
                apiError(400, 'unsupported_provider', 'This photo provider is not supported currently.');
        }
        if ($result === null) {
            apiError(400, 'not_allow', 'Can not access photos, please reauthenticate this identity.');
        }
        // get photos
        $photos = $modPhoto->getPhotosByCrossId($cross_id);
        apiResponse(['photos' => $photos]);
    }

}
