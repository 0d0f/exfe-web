<?php

class PhotoxActions extends ActionController {

    public function doIndex() {
        $params = $this->params;
        $checkHelper = $this->getHelperByName('check');
        $result = $checkHelper->isAPIAllow('cross', $params['token'], ['cross_id' => $params['id']]);
        if ($result['check'] !== true) {
            if ($result['uid'] === 0) {
                apiError(401, 'invalid_auth', '');
            } else {
                apiError(403, 'not_authorized', "The PhotoX you're requesting is private.");
            }
        }
        $crossHelper = $this->getHelperByName('cross');
        $cross = $crossHelper->getCross($params['id']);
        if ($cross) {
            if ($cross->attribute['state'] === 'deleted') {
                apiError(403, 'not_authorized', "The PhotoX you're requesting is private.");
            }
            $modPhotos = $this->getModelByName('Photo');
            $photox    = $modPhotos->getPhotoxById(
                $params['id'],
                isset($params['sort'])  ? $params['sort']        : '',
                isset($params['limit']) ? (int) $params['limit'] : 0
            );
            $responses = $modPhotos->getResponsesByPhotoxId($params['id']);
            touchCross($params['id'], $result['uid']);
            apiResponse(['photox' => $photox, 'likes' => $responses]);
        }
        apiError(400, 'param_error', "The PhotoX you're requesting is not found.");
    }


    public function doBrowseSource() {
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
        $photo_providers = ['facebook', 'dropbox', 'flickr', 'instagram'];
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
        // get selected albums & photos
        $modPhoto  = $this->getModelByName('Photo');
        $photox_id = @ (int) $_GET['photox_id'];
        $rawAlbums = $modPhoto->getPhotoIdsByPhotoxId($_GET['photox_id']);
        $album_ids = [];
        $photo_ids = [];
        foreach ($rawAlbums ?: [] as $raItem) {
            if (isset($album_ids["{$raItem['provider']}_{$raItem['external_album_id']}"])) {
                $album_ids["{$raItem['provider']}_{$raItem['external_album_id']}"]++;
            } else {
                $album_ids["{$raItem['provider']}_{$raItem['external_album_id']}"] = 1;
            }
            $photo_ids["{$raItem['provider']}_{$raItem['external_id']}"] = $raItem['id'];
        }
        // get albums
        $rawAlbums = [];
        $rawPhotos = [];
        $failed    = [];
        $album_id  = isset($_GET['album_id']) && $_GET['album_id'] ? $_GET['album_id'] : '';
        foreach ($objIdentities as $objIdentity) {
            switch ($objIdentity->provider) {
                case 'facebook':
                    if ($album_id) {
                        $rawResult = $modPhoto->getPhotosFromFacebook($objIdentity->id, $album_id);
                        if ($rawResult !== null) {
                            $rawResult = ['albums' => [], 'photos' => $rawResult];
                        }
                    } else {
                        $rawResult = $modPhoto->getAlbumsFromFacebook($objIdentity->id);
                    }
                    break;
                case 'dropbox':
                    $rawResult = $modPhoto->getAlbumsFromDropbox($objIdentity->id, $album_id);
                    break;
                case 'flickr':
                    if ($album_id) {
                        $rawResult = $modPhoto->getPhotosFromFlickr($objIdentity->id, $album_id);
                        if ($rawResult !== null) {
                            $rawResult = ['albums' => [], 'photos' => $rawResult];
                        }
                    } else {
                        $rawResult = $modPhoto->getAlbumsFromFlickr($objIdentity->id);
                    }
                    break;
                case 'instagram':
                    if ($album_id) {
                        $rawResult = null;
                        if ($album_id === "{$objIdentity->id}") {
                            $rawResult = $modPhoto->getPhotosFromInstagram($objIdentity->id);
                        }
                        if ($rawResult !== null) {
                            $rawResult = ['albums' => [], 'photos' => $rawResult['photos']];
                        }
                    } else {
                        $rawResult = ['albums' => [[
                            'external_id' => "{$objIdentity->id}",
                            'provider'    => 'instagram',
                            'caption'     => $objIdentity->external_username,
                            'artwork'     => '',
                            'count'       => -1,
                            'size'        => -1,
                            'by_identity' => $objIdentity,
                            'created_at'  => date('Y-m-d H:i:s', time()) . ' +0000',
                            'updated_at'  => date('Y-m-d H:i:s', time()) . ' +0000',
                        ]], 'photos' => []];
                    }
            }
            if ($rawResult) {
                foreach ($rawResult['albums'] as $album) {
                    if (isset($album_ids["{$album['provider']}_{$album['external_id']}"])) {
                        if ($album['provider'] === 'instagram') {
                            $album['imported']  =  $album_ids["{$album['provider']}_{$album['external_id']}"];
                        } else {
                            $album['imported']  =  -1;
                        }
                    } else {
                        $album['imported'] = 0;
                    }
                    if (($key = strtotime($album['updated_at']))
                     && !isset($rawAlbums[$key])) {
                        $rawAlbums[$key] = $album;
                    } else {
                        $rawAlbums[]     = $album;
                    }
                }
                $rawPhotos = $rawResult['photos'];
                foreach ($rawPhotos as $rpI => $rpItem) {
                    if (isset($photo_ids["{$rpItem->provider}_{$rpItem->external_id}"])) {
                        $rawPhotos[$rpI]->id = $photo_ids["{$rpItem->provider}_{$rpItem->external_id}"];
                        $rawPhotos[$rpI]->imported = 1;
                    } else {
                        $rawPhotos[$rpI]->imported = 0;
                    }
                }
            } else if ($rawResult === null) {
                $failed[]  = $objIdentity;
            }
        }
        ksort($rawAlbums);
        $rawAlbums = array_reverse(array_values($rawAlbums));
        apiResponse([
            'albums'            => $rawAlbums,
            'photos'            => $rawPhotos,
            'failed_identities' => $failed,
        ]);
    }


    public function doGetSourcePhotos() {
        // get models
        $modPhoto    = $this->getModelByName('Photo');
        $modIdentity = $this->getModelByName('Identity');
        $checkHelper = $this->getHelperByName('check');
        // check args
        $params = $this->params;
        $ids    = @json_decode($_POST['external_ids']);
        if (!$ids || !is_array($ids)) {
            apiError(404, 'photo_not_found');
        }
        if (count($ids) > 10) {
            apiError(400, 'max 10 photos per request');
        }
        // check signin
        $result = $checkHelper->isAPIAllow('user_edit', $params['token']);
        if ($result['check']) {
            $user_id = $result['uid'];
        } else {
            apiError(401, 'no_signin', ''); // 需要登录
        }
        // get identity
        $raw_identity_id = @ (int) $_POST['identity_id'];
        $identity = $modIdentity->getIdentityById($raw_identity_id);
        if (!$identity || $identity->connected_user_id !== $user_id) {
            apiError(403, 'not_authorized', 'Can not access this identity.');
        }
        switch ($identity->provider) {
            case 'dropbox':
                $photos = $modPhoto->getPhotosFromDropbox(
                    $identity->id, @$_POST['album_id'] ?: '', $ids
                );
                if ($photos) {
                    apiResponse(['photos' => $photos]);
                }
                break;
            default:
                apiError(400, 'unknow_provider', '');
        }
        apiError(400, 'error_getting_photo');
    }


    public function doGetPhoto() {
        // get models
        $modPhoto    = $this->getModelByName('Photo');
        $checkHelper = $this->getHelperByName('check');
        // check args
        $params   = $this->params;
        $id       = @ (int) $_GET['photo_id'] ?: '';
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
        $album_id     = @ $_POST['album_id']         ?: '';
        $ids          = @ json_decode($_POST['ids']) ?: [];
        $stream_id    = @ $_POST['photostream_id']   ?: '';
        $identity_id  = 0;
        // check identity
        if ($stream_id) {
            $modExfee = $this->getModelByName('Exfee');
            $exfee_id = $modExfee->getExfeeIdByCrossId($cross_id);
            $exfee    = $modExfee->getExfeeById($exfee_id);
            foreach ($exfee->invitations as $invitation) {
                if ($invitation->identity->connected_user_id === $user_id) {
                    $identity_id = $invitation->identity->id;
                    break;
                }
            }
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
                if (!$ids || !is_array($ids)) {
                    apiError(400, 'error_ids_array', '');
                }
                $photos = $modPhoto->getPhotosFromInstagram($identity_id);
                if ($photos) {
                    foreach ($photos['photos'] as $i => $photo) {
                        if (!in_array($photo->external_id, $ids)) {
                            unset($photos['photos'][$i]);
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
        touchCross($cross_id, $user_id);
        apiResponse(['photox' => $photox]);
    }


    public function doDelete() {
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
        // del album from cross
        $modPhoto = $this->getModelByName('Photo');
        $provider = @ $_POST['provider'] ?: '';
        $album_id = @ $_POST['album_id'] ?: '';
        $photo_id = @ json_decode($_POST['photo_ids']) ?: [];
        $modPhoto = $this->getModelByName('Photo');
        $result   = isset($_POST['photo_ids'])
                  ? $modPhoto->delPhotosFromPhotoxByPhotoxIdAndPhotoIds($cross_id, $photo_id)
                  : $modPhoto->delAlbumFromPhotoxByPhotoxIdAndProviderAndExternalAlbumId($cross_id, $provider, $album_id);
        if ($result) {
            $modExfee = $this->getModelByName('Exfee');
            $exfee_id = $modExfee->getExfeeIdByCrossId($cross_id);
            $modExfee->updateExfeeTime($exfee_id);
            $photox = $modPhoto->getPhotoxById($cross_id);
            touchCross($cross_id, $user_id);
            apiResponse(['photox' => $photox]);
        }
        apiError(400, 'param_error', "Please retry later.");
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
            if ($cross->attribute['state'] === 'deleted') {
                apiError(403, 'not_authorized', "The PhotoX you're requesting is private.");
            }
            $modPhotos = $this->getModelByName('Photo');
            $responses = $modPhotos->getResponsesByPhotoxId($id);
            touchCross($id, $result['uid']);
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
        foreach ($exfee->invitations as $invitation) {
            if ($invitation->identity->connected_user_id === $user_id) {
                $identity_id = $invitation->identity->id;
                break;
            }
        }
        if (!$identity_id) {
            apiError(403, 'not_authorized', "The PhotoX you're requesting is private.");
        }
        //
        $response = @ $_POST['LIKE'] === 'false' ? '' : 'LIKE';
        $result   = $modPhoto->responseToPhoto($id, $identity_id, $response);
        if ($result) {
            $modExfee->updateExfeeTime($exfee_id);
            touchCross($cross_id, $user_id);
            apiResponse(['like' => $result]);
        }
        apiError(400, 'error_responsing_photo');
    }

}
