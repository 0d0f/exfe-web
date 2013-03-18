<?php

class PhotoxActions extends ActionController {

    public function doIndex() {
        $params = $this->params;
        $checkHelper = $this->getHelperByName('check');
        $result = $checkHelper->isAPIAllow('cross', $params['token'], ['cross_id' => $params['id']]);
        if ($result['check'] !== true) {
            if ($result['uid'] === 0)
                apiError(401, 'invalid_auth', '');
            else
                apiError(403, 'not_authorized', "The PhotoX you're requesting is private.");
        }
        $crossHelper = $this->getHelperByName('cross');
        $cross = $crossHelper->getCross($params['id']);
        if ($cross) {
            if ($cross->attribute['deleted']) {
                apiError(403, 'not_authorized', "The PhotoX you're requesting is private.");
            }
            $modPhotos = $this->getModelByName('Photo');
            $photox = $modPhotos->getPhotoxById($params['id']);
            apiResponse(['photox' => $photox]);
        }
        apiError(400, 'param_error', "The PhotoX you're requesting is not found.");
    }


    public function doGetSourceAlbums() {
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
        $identity_id     = @ (int) $_GET['identity_id'];
        $objIdentities   = []; 
        $photo_providers = ['facebook', 'dropbox', 'flickr'];
        foreach ($user->identities as $identity) {
            if ($identity->status === 'CONNECTED'
             && in_array($identity->provider, $photo_providers)) {
                if ($identity_id) {
                    if ($identity->id === $identity_id) {
                        $objIdentities[] = $identity;
                        break;
                    }
                } else {
                    $objIdentities[] = $identity;
                }
            }
        }
        if (!$objIdentities) {
            apiError(400, 'no_supported_identities', ''); // 需要输入identity_id
        }
        // get albums
        $modPhoto  = $this->getModelByName('Photo');
        $rawAlbums = [];
        $failed    = [];
        foreach ($objIdentities as $objIdentity) {
            switch ($objIdentity->provider) {
                case 'facebook':
                    $rawResult = $modPhoto->getAlbumsFromFacebook($objIdentity->id);
                    break;
                case 'dropbox':
                    $album_id  = isset($_GET['album_id']) && $_GET['album_id'] ? $_GET['album_id'] : '';
                    $rawResult = $modPhoto->getAlbumsFromDropbox($objIdentity->id, $album_id);
                    break;
                case 'flickr':
                    $rawResult = $modPhoto->getAlbumsFromFlickr($objIdentity->id);
            }
            if ($rawResult) {
                foreach ($rawResult as $album) {
                    if (($key = strtotime($album['updated_at']))
                     && !isset($rawAlbums[$key])) {
                        $rawAlbums[$key] = $album;
                    } else {
                        $rawAlbums[]     = $album;
                    }
                }
            } else if ($rawResult === null) {
                $failed[]  = $objIdentity;
            }
        }
        ksort($rawAlbums);
        $rawAlbums = array_reverse(array_values($rawAlbums));
        apiResponse(['albums' => $rawAlbums, 'failed_identities' => $failed]);
    }


    public function doGetSourcePhotos() {
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
        $photos   = null;
        switch ($objIdentity->provider) {
            case 'instagram':
                $photos = $modPhoto->getPhotosFromInstagram($identity_id);
                break;
            default:
                apiError(400, 'unsupported_provider', 'This photo provider is not supported currently.');
        }
        if ($photos === null) {
            apiError(400, 'not_allow', 'Can not access photos, please reauthenticate this identity.');
        }
        apiResponse($photos);
    }


    public function doGetPhoto() {
        // get models
        $modPhoto    = $this->getModelByName('Photo');
        $checkHelper = $this->getHelperByName('check');
        // check args
        $params   = $this->params;
        $id       = @ (int) $_POST['id'] ?: '';
        $cross_id = $modPhoto->getCrossIdByPhotoId($id);
        if (!$id || !$cross_id) {
            apiError(404, 'photo_not_found');
        }
        // check signin
        $result   = $checkHelper->isAPIAllow('cross_edit_by_user', $params['token'], ['cross_id' => $cross_id]);
        if ($result['check']) {
            $user_id = $result['uid'];
        } else if ($result['uid'] === 0) {
            apiError(401, 'no_signin', ''); // 需要登录
        } else {
            apiError(403, 'not_authorized', "The PhotoX you're requesting is private.");
        }
        //
        $photo = $modPhoto->getPhotoById($id);
        if ($photo) {
            switch ($photo->provider) {
                case 'photostream':
                    $photo = $modPhoto->getPhotoFromPhotoStream($photo);
                    break;
                case 'flickr':
                    $photo = $modPhoto->getPhotoFromFlickr($photo);
            }
        }
        if ($photo) {
            apiResponse(['photo' => $photo]);
        }
        apiError(400, 'error_getting_photo');
    }


    public function doAdd() {
        // check signin
        $checkHelper = $this->getHelperByName('check');
        $params   = $this->params;
        $cross_id = @ (int) $params['id'];
        $result   = $checkHelper->isAPIAllow('cross_edit_by_user', $params['token'], ['cross_id' => $cross_id]);
        if ($result['check']) {
            $user_id = $result['uid'];
        } else if ($result['uid'] === 0) {
            apiError(401, 'no_signin', ''); // 需要登录
        } else {
            apiError(403, 'not_authorized', "The PhotoX you're requesting is private.");
        }
        // check args
        $album_id     = @ $_POST['album_id']       ?: '';
        $min_id       = @ $_POST['min_id']         ?: '';
        $max_id       = @ $_POST['max_id']         ?: '';
        $stream_id    = @ $_POST['photostream_id'] ?: '';
        $identity_id  = 0;
        // check identity
        if ($stream_id) {
            $modExfee = $this->getModelByName('Exfee');
            $exfee_id = $modExfee->getExfeeIdByCrossId($cross_id);
            $exfee    = $modExfee->getExfeeById($exfee_id);
            $bak_id   = 0;
            foreach ($exfee->invitations as $invitation) {
                if ($invitation->identity->connected_user_id === $user_id) {
                    if ($invitation->rsvp_status === 'NOTIFICATION') {
                        $bak_id      = $invitation->identity->id;
                    } else {
                        $identity_id = $invitation->identity->id;
                        break;
                    }
                }
            }
            $identity_id = $identity_id ?: $bak_id;
            if (!$identity_id) {
                apiError(400, 'server_error');
            }
        } else {
            $identity_id = @ (int) $_POST['identity_id'];
            if (!$identity_id) {
                apiError(400, 'no_identity_id', ''); // 需要输入identity_id
            }
            $modIdentity = $this->getModelByName('Identity');
            $identity = $modIdentity->getIdentityById($identity_id);
            if (!$identity || $identity->connected_user_id !== $user_id) {
                apiError(400, 'can_not_be_verify', 'This identity does not belong to current user.');
            }
        }
        // add album to cross
        $modPhoto  = $this->getModelByName('Photo');
        $result    = null;
        if ($stream_id) {
            $identity->provider = 'photostream';
        }
        switch ($identity->provider) {
            case 'facebook':
                if (!$album_id) {
                    apiError(400, 'no_album_id', '');
                }
                $result = $modPhoto->addFacebookAlbumToCross($album_id, $cross_id, $identity_id);
                break;
            case 'dropbox':
                if (!$album_id) {
                    apiError(400, 'no_album_id', '');
                }
                $result = $modPhoto->addDropboxAlbumToCross($album_id, $cross_id, $identity_id);
                break;
            case 'instagram':
                if (!$min_id || !$max_id) {
                    apiError(400, 'no_min_id_or_max_id', '');
                }
                $arr_min_id = explode('_', $min_id);
                $arr_max_id = explode('_', $max_id);
                $min_id = (int) array_shift($arr_min_id);
                $max_id = (int) array_shift($arr_max_id);
                $photos = $modPhoto->getPhotosFromInstagram($identity_id);
                if ($photos) {
                    foreach ($photos['photos'] as $i => $photo) {
                        $arr_cur_id = explode('_', $photo->external_id);
                        $cur_id = (int) array_shift($arr_cur_id);
                        if ($min_id > $cur_id || $max_id < $cur_id) {
                            unset($photos[$i]);
                        }
                    }
                    $result = $modPhoto->addPhotosToCross(
                        $cross_id, $photos['photos'], $identity_id
                    );
                }
                break;
            case 'photostream':
                $result = $modPhoto->addPhotoStreamToCross($stream_id, $cross_id, $identity_id);
                break;
            case 'flickr':
                $result = $modPhoto->addFlickrAlbumToCross($album_id, $cross_id, $identity_id);
                break;
            default:
                apiError(400, 'unsupported_provider', 'This photo provider is not supported currently.');
        }
        if ($result === null) {
            apiError(400, 'not_allow', 'Can not access photos, please reauthenticate this identity.');
        }
        // get photos
        $photox = $modPhoto->getPhotoxById($cross_id);
        apiResponse(['photox' => $photox]);
    }


    public function doGetLikes() {
        $checkHelper = $this->getHelperByName('check');
        $params = $this->params;
        $id     = @ (int) $params['id'];
        $result = $checkHelper->isAPIAllow('cross', $params['token'], ['cross_id' => $id]);
        if ($result['check'] !== true) {
            if ($result['uid'] === 0)
                apiError(401, 'invalid_auth', '');
            else
                apiError(403, 'not_authorized', "The PhotoX you're requesting is private.");
        }
        $crossHelper = $this->getHelperByName('cross');
        $cross = $crossHelper->getCross($id);
        if ($cross) {
            if ($cross->attribute['deleted']) {
                apiError(403, 'not_authorized', "The PhotoX you're requesting is private.");
            }
            $modPhotos = $this->getModelByName('Photo');
            $responses = $modPhotos->getResponsesByPhotoxId($id);
            apiResponse(['likes' => $responses]);
        }
        apiError(400, 'param_error', "The PhotoX you're requesting is not found.");
    }


    public function doLike() {
        // get models
        $modPhoto    = $this->getModelByName('Photo');
        $checkHelper = $this->getHelperByName('check');
        // check args
        $params   = $this->params;
        $id       = @ (int) $_POST['id'] ?: '';
        $cross_id = $modPhoto->getCrossIdByPhotoId($id);
        if (!$id || !$cross_id) {
            apiError(404, 'photo_not_found');
        }
        // check signin
        $result   = $checkHelper->isAPIAllow('cross_edit_by_user', $params['token'], ['cross_id' => $cross_id]);
        if ($result['check']) {
            $user_id = $result['uid'];
        } else if ($result['uid'] === 0) {
            apiError(401, 'no_signin', ''); // 需要登录
        } else {
            apiError(403, 'not_authorized', "The PhotoX you're requesting is private.");
        }
        //
        $modExfee = $this->getModelByName('Exfee');
        $exfee_id = $modExfee->getExfeeIdByCrossId($cross_id);
        $exfee    = $modExfee->getExfeeById($exfee_id);
        $bak_id   = 0;
        foreach ($exfee->invitations as $invitation) {
            if ($invitation->identity->connected_user_id === $user_id) {
                if ($invitation->rsvp_status === 'NOTIFICATION') {
                    $bak_id      = $invitation->identity->id;
                } else {
                    $identity_id = $invitation->identity->id;
                    break;
                }
            }
        }
        $identity_id = $identity_id ?: $bak_id;
        if (!$identity_id) {
            apiError(403, 'not_authorized', "The PhotoX you're requesting is private.");
        }
        //
        $response = @ $_POST['LIKE'] === 'false' ? '' : 'LIKE';
        $result   = $modPhoto->responseToPhoto($id, $identity_id, $response);
        if ($result) {
            apiResponse(['like' => $result]);
        }
        apiError(400, 'error_responsing_photo');
    }

}
