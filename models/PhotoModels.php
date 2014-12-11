<?php

require_once dirname(dirname(__FILE__)) . '/lib/Instagram.php';


class PhotoModels extends DataModel {

    public function packPhoto($rawPhoto) {
        $hlpIdentity = $this->getHelperByName('Identity');
        $identity = $hlpIdentity->getIdentityById($rawPhoto['by_identity_id']);
        $location = $rawPhoto['location_lng'] &&  $rawPhoto['location_lat'] ? new Place(
            0,
            $rawPhoto['location_title'],
            $rawPhoto['location_description'],
            $rawPhoto['location_lng'],
            $rawPhoto['location_lat'],
            $rawPhoto['provider'],
            $rawPhoto['location_external_id'],
            $rawPhoto['created_at'],
            $rawPhoto['updated_at']
        ) : null;
        $fullsize_expired_at  = (isset($rawPhoto['fullsize_expired_at'])
                              && $rawPhoto['fullsize_expired_at']
                               ? date('Y-m-d H:i:s', strtotime($rawPhoto['fullsize_expired_at']))
                               : '0000-00-00 00:00:00') . ' +0000';
        $preview_expired_at   = (isset($rawPhoto['preview_expired_at'])
                              && $rawPhoto['preview_expired_at']
                               ? date('Y-m-d H:i:s', strtotime($rawPhoto['preview_expired_at']))
                               : '0000-00-00 00:00:00') . ' +0000';
        $images   = [
            'fullsize' => [
                'height'     => (int) $rawPhoto['fullsize_height'],
                'width'      => (int) $rawPhoto['fullsize_width'],
                'url'        =>       $rawPhoto['fullsize_url'],
                'expired_at' =>       $rawPhoto['fullsize_expired_at'],
            ],
            'preview'  => [
                'height'     => (int) $rawPhoto['preview_height'],
                'width'      => (int) $rawPhoto['preview_width'],
                'url'        =>       $rawPhoto['preview_url'],
                'expired_at' =>       $rawPhoto['preview_expired_at'],
            ],
        ];
        $images['original']  =  $images['fullsize'];
        // if (isset($rawPhoto['fullsize_hash'])) {
        //     $images['fullsize']['hash']  = $rawPhoto['fullsize_hash'];
        // }
        // if (isset($rawPhoto['preview_hash'])) {
        //     $images['preview']['hash'] = $rawPhoto['preview_hash'];
        // }
        return new Photo(
            $rawPhoto['id'], $rawPhoto['caption'], $identity,
            $rawPhoto['created_at'], $rawPhoto['updated_at'],
            $rawPhoto['provider'],   $rawPhoto['external_id'],
            $location, $images,      $rawPhoto['external_album_id']
        );
    }


    public function getPhotoxById($id, $sort = '', $limit = 0) {
        //
        $hlpExfee = $this->getHelperByName('Exfee');
        $exfee_id = $hlpExfee->getExfeeIdByCrossId($id);
        $exfee    = $hlpExfee->getExfeeById($exfee_id);
        $users    = [];
        foreach ($exfee->invitations as $invitation) {
            if ($invitation->rsvp_status !== 'NOTIFICATION'
             && $invitation->identity->connected_user_id >= 0) {
                $users[$invitation->identity->connected_user_id] = $invitation->identity;
            }
        }
        //
        switch ($sort) {
            case 'imported_time_desc':
                $sort = '`id` DESC';
                break;
            default:
                $sort = '`external_created_at`';
        }
        $limit = (int) $limit;
        $limit = $limit ? "LIMIT {$limit}" : '';
        $photos = [];
        $rawPhotos = $this->getAll(
            "SELECT * FROM `photos` WHERE `cross_id` = {$id} ORDER BY {$sort} {$limit}"
        );
        foreach ($rawPhotos ?: [] as $rawPhoto) {
            if ($rawPhoto['provider'] === 'photostream') {
                $rawPhoto['fullsize_url'] = '';
            }
            $photos[] = $this->packPhoto($rawPhoto);
        }
        //
        foreach ($photos as $i => $photo) {
            $user_id = $photo->by_identity->connected_user_id;
            if (isset($users[$user_id])) {
                $photos[$i]->by_identity = $users[$user_id];
            }
        }
        return new PhotoX($id, $photos);
    }


    public function getPhotoIdsByPhotoxId($id) {
        $rawPhotos = $id ? $this->getAll(
            "SELECT   `id`, `provider`, `external_album_id`, `external_id`
             FROM     `photos`
             WHERE    `cross_id` = {$id}"
        ) : null;
        return $rawPhotos ?: null;
    }


    public function delAlbumFromPhotoxByPhotoxIdAndProviderAndExternalAlbumId($id, $provider, $external_album_id) {
        return $id && $provider && $external_album_id ? $this->query(
            "DELETE FROM `photos`
             WHERE `cross_id`           =  {$id}
             AND   `provider`           = '{$provider}'
             AND   `external_album_id`  = '{$external_album_id}'"
        ) : null;
    }


    public function delPhotosFromPhotoxByPhotoxIdAndPhotoIds($id, $photo_ids) {
        $photo_ids = implode(", ", $photo_ids);
        return $id && $photo_ids ? $this->query(
            "DELETE FROM `photos`
             WHERE `cross_id` = {$id} AND `id` in ({$photo_ids})"
        ) : null;
    }


    public function getPhotoById($id) {
        $rawPhoto = $this->getRow("SELECT * FROM `photos` WHERE `id` = {$id}");
        return $rawPhoto ? $this->packPhoto($rawPhoto) : null;
    }


    public function getCrossIdByPhotoId($id) {
        $rawPhoto = $this->getRow("SELECT `cross_id` FROM `photos` WHERE `id` = {$id}");
        return $rawPhoto && isset($rawPhoto['cross_id']) ? (int) $rawPhoto['cross_id'] : null;
    }


    public function updateExfeeTime($cross_id) {
        $hlpExfe  = $this->getHelperByName('Exfee');
        $exfee_id = $hlpExfe->getExfeeIdByCrossId($cross_id);
        return $hlpExfe->updateExfeeTime($exfee_id, true);
    }


    public function addPhotosToCross($cross_id, $photos, $identity_id) {
        if ($cross_id && is_array($photos) && $identity_id) {
            foreach ($photos as $photo) {
                $photo->images             = (array) $photo->images;
                $photo->images['fullsize'] = (array) $photo->images['fullsize'];
                $photo->images['preview']  = (array) $photo->images['preview'];
                $strSql = "
                    `updated_at`           =  NOW(),
                    `external_created_at`  = '" . date('Y-m-d H:i:s', strtotime($photo->created_at))      . "',
                    `external_updated_at`  = '" . date('Y-m-d H:i:s', strtotime($photo->updated_at))      . "',
                    `location_title`       = '" . ($photo->location ? $photo->location->title       : '') . "',
                    `location_description` = '" . ($photo->location ? $photo->location->description : '') . "',
                    `location_external_id` = '" . ($photo->location ? $photo->location->external_id : '') . "',
                    `location_lng`         = '" . ($photo->location ? $photo->location->lng         : '') . "',
                    `location_lat`         = '" . ($photo->location ? $photo->location->lat         : '') . "',
                    `caption`              = '{$photo->caption}',
                    `fullsize_url`         = '{$photo->images['fullsize']['url']}',
                    `fullsize_width`       =  {$photo->images['fullsize']['width']},
                    `fullsize_height`      =  {$photo->images['fullsize']['height']},
                    `preview_url`          = '{$photo->images['preview']['url']}',
                    `preview_width`        =  {$photo->images['preview']['width']},
                    `preview_height`       =  {$photo->images['preview']['height']}
                ";
                $curImg = $this->getRow(
                    "SELECT * FROM `photos`
                     WHERE `cross_id`          =  {$cross_id}
                     AND   `provider`          = '{$photo->provider}'
                     AND   `external_album_id` = '{$photo->external_album_id}'
                     AND   `external_id`       = '{$photo->external_id}'"
                );
                if ($curImg) {
                    $this->query(
                        "UPDATE `photos` SET           {$strSql}
                         WHERE  `cross_id`          =  {$cross_id}
                         AND    `provider`          = '{$photo->provider}'
                         AND    `external_album_id` = '{$photo->external_album_id}'
                         AND    `external_id`       = '{$photo->external_id}'"
                    );
                } else {
                    $this->query(
                        "INSERT INTO `photos` SET
                         `cross_id`            =  {$cross_id},
                         `provider`            = '{$photo->provider}',
                         `external_album_id`   = '{$photo->external_album_id}',
                         `external_id`         = '{$photo->external_id}',
                         `by_identity_id`      =  {$identity_id},
                         `created_at`          =  NOW(), {$strSql}"
                    );
                }
            }
            $this->updateExfeeTime($cross_id);
            return true;
        }
        return false;
    }


    public function addPhotosByGobus($photox_id, $album_id, $recipient) {
        $hlpQueue = $this->getHelperByName('Queue');
        return $hlpQueue->fireBus(
            [$recipient], '-', 'POST',
            EXFE_AUTH_SERVER . '/thirdpart/photographers?album_id='
          . urlencode($album_id) . "&photox_id={$photox_id}",
            'once', time(), $recipient
        ) ? true : null;
    }


    public function getResponsesByPhotoxId($id) {
        //
        $hlpExfee = $this->getHelperByName('Exfee');
        $exfee_id = $hlpExfee->getExfeeIdByCrossId($id);
        $exfee    = $hlpExfee->getExfeeById($exfee_id);
        $users    = [];
        foreach ($exfee->invitations as $invitation) {
            if ($invitation->rsvp_status !== 'NOTIFICATION'
             && $invitation->identity->connected_user_id >= 0) {
                $users[$invitation->identity->connected_user_id] = $invitation->identity;
            }
        }
        //
        $photo_ids = $this->getColumn(
            "SELECT `id` FROM `photos` WHERE `cross_id` = {$id}"
        );
        //
        $hlpResponse = $this->getHelperByName('Response');
        $rawResponse = $hlpResponse->getResponsesByObjectTypeAndObjectIds('photo', $photo_ids);
        if ($rawResponse) {
            foreach ($rawResponse as $rgI => $rgItem) {
                foreach ($rgItem as $rJ => $rJtem) {
                    $user_id = $rJtem->by_identity->connected_user_id;
                    if (isset($users[$user_id])) {
                        $rawResponse[$rgI][$rJ]->by_identity = $users[$user_id];
                    }
                }
            }
            return $rawResponse;
        }
        return [];
    }


    public function responseToPhoto($id, $identity_id, $response) {
        $hlpResponse = $this->getHelperByName('Response');
        return $hlpResponse->responseToObject('photo', $id, $identity_id, $response);
    }


    // facebook {

    public function getAlbumsFromFacebook($identity_id) {
        require_once(dirname(dirname(__FILE__)) . '/lib/httpkit.php');
        $hlpIdentity = $this->getHelperByName('Identity');
        $identity = $hlpIdentity->getIdentityById($identity_id);
        if ($identity
         && $identity->connected_user_id > 0
         && $identity->provider === 'facebook') {
            $token = $hlpIdentity->getOAuthTokenById($identity_id);
            if ($token
             && $token['oauth_token']
             && $token['oauth_expires'] > time()) {
                $objCurl = curl_init(
                    "https://graph.facebook.com/me/albums?access_token={$token['oauth_token']}"
                );
                curl_setopt($objCurl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($objCurl, CURLOPT_CONNECTTIMEOUT, 10);
                $data = curl_exec($objCurl);
                curl_close($objCurl);
                if ($data && ($data = json_decode($data, true)) && isset($data['data'])) {
                    $albums = [];
                    foreach ($data['data'] as $album) {
                        $cover_photo = httpKit::request(
                            "https://graph.facebook.com/{$album['cover_photo']}?fields=picture&access_token={$token['oauth_token']}",
                            null, null, false, false, 10, 3, 'txt', true
                        );
                        // @todo @leaskh
                        $cover_photo = ($cover_photo
                                     && $cover_photo['http_code'] === 200
                                     && isset($cover_photo['json']['picture'])
                                     && $cover_photo['json']['picture'])
                                     ? $cover_photo['json']['picture'] : '';
                        $albums[] = [
                            'external_id' => $album['id'],
                            'provider'    => 'facebook',
                            'caption'     => $album['name'],
                            'artwork'     => $cover_photo,
                            'count'       => $album['count'],
                            'size'        => -1,
                            'by_identity' => $identity,
                            'created_at'  => date('Y-m-d H:i:s', strtotime($album['created_time'])) . ' +0000',
                            'updated_at'  => date('Y-m-d H:i:s', strtotime($album['updated_time'])) . ' +0000',
                        ];
                    }
                    return ['albums' => $albums, 'photos' => []];
                }
            }
        }
        return null;
    }


    public function getPhotosFromFacebook($identity_id, $album_id) {
        $hlpIdentity = $this->getHelperByName('Identity');
        $identity    = $hlpIdentity->getIdentityById($identity_id);
        if ($identity
         && $identity->connected_user_id > 0
         && $identity->provider === 'facebook') {
            $token   = $hlpIdentity->getOAuthTokenById($identity_id);
            if ($token
             && $token['oauth_token']
             && $token['oauth_expires'] > time()) {
                $objCurl = curl_init(
                    "https://graph.facebook.com/{$album_id}/photos?access_token={$token['oauth_token']}"
                );
                curl_setopt($objCurl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($objCurl, CURLOPT_CONNECTTIMEOUT, 10);
                $data = curl_exec($objCurl);
                curl_close($objCurl);
                if ($data && ($data = json_decode($data, true)) && isset($data['data'])) {
                    $albums = [];
                    foreach ($data['data'] as $photo) {
                        $created_at = date('Y-m-d H:i:s', strtotime($photo['created_time']));
                        $updated_at = date('Y-m-d H:i:s', strtotime($photo['updated_time']));
                        $albums[]   = $this->packPhoto([
                            'id'                   => 0,
                            'cross_id'             => 0,
                            'caption'              => $photo['name'],
                            'by_identity_id'       => $identity_id,
                            'created_at'           => $created_at,
                            'updated_at'           => $updated_at,
                            'external_created_at'  => $created_at,
                            'external_updated_at'  => $updated_at,
                            'provider'             => 'facebook',
                            'external_album_id'    => $album_id,
                            'external_id'          => $photo['id'],
                            'location_title'       => '',
                            'location_description' => '',
                            'location_external_id' => '',
                            'location_lng'         => '',
                            'location_lat'         => '',
                            'fullsize_url'         => $photo['images'][0]['source'],
                            'fullsize_width'       => $photo['images'][0]['width'],
                            'fullsize_height'      => $photo['images'][0]['height'],
                            'preview_url'          => $photo['source'],
                            'preview_width'        => $photo['width'],
                            'preview_height'       => $photo['height'],
                        ]);
                    }
                    return $albums;
                }
            }
        }
        return null;
    }


    public function addFacebookAlbumToCross($album_id, $cross_id, $identity_id) {
        if ($album_id && $cross_id && $identity_id) {
            $photos = $this->getPhotosFromFacebook($identity_id, $album_id);
            if ($photos !== null) {
                return $this->addPhotosToCross($cross_id, $photos, $identity_id);
            }
        }
        return null;
    }

    // }


    // dropbox {

    public function getAlbumsFromDropbox($identity_id, $album_id = '') {
        $hlpIdentity = $this->getHelperByName('Identity');
        $identity = $hlpIdentity->getIdentityById($identity_id);
        if ($identity
         && $identity->connected_user_id > 0
         && $identity->provider === 'dropbox') {
            $token = $hlpIdentity->getOAuthTokenById($identity_id);
            if ($token
             && $token['oauth_token']
             && $token['oauth_token_secret']) {
                $objCurl = curl_init(
                    "https://api.dropbox.com/1/metadata/dropbox{$album_id}"
                );
                curl_setopt($objCurl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($objCurl, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($objCurl, CURLOPT_HTTPHEADER, [
                    'Authorization: '
                  . 'OAuth oauth_version="1.0", '
                  . 'oauth_signature_method="PLAINTEXT", '
                  . 'oauth_consumer_key="' . DROPBOX_APP_KEY              . '", '
                  . 'oauth_token="'        . $token['oauth_token']        . '", '
                  . 'oauth_signature="'    . DROPBOX_APP_SECRET           . '&'
                                           . $token['oauth_token_secret'] . '"'
                ]);
                $data = curl_exec($objCurl);
                curl_close($objCurl);
                if ($data && ($data = json_decode($data, true)) && isset($data['contents'])) {
                    $albums = [];
                    $photos = [];
                    $rawPto = [];
                    foreach ($data['contents'] as $album) {
                        $external_id  = implode('/', array_map('rawurlencode', explode('/', $album['path'])));
                        if ($album['is_dir']) {
                            $path = explode('/', $album['path']);
                            $albums[] = [
                                'external_id' => $external_id,
                                'provider'    => 'dropbox',
                                'caption'     => array_pop($path),
                                'artwork'     => '',
                                'count'       => -1,
                                'size'        => $album['size'],
                                'by_identity' => $identity,
                                'created_at'  => date('Y-m-d H:i:s', strtotime($album['modified'])) . ' +0000',
                                'updated_at'  => date('Y-m-d H:i:s', strtotime($album['modified'])) . ' +0000',
                            ];
                        } else if ($album['mime_type'] === 'image/jpeg') {
                            $rawPto[] = $external_id;
                        }
                    }
                    if ($rawPto && strlen($album_id) > 0) { // @todo @leaskh ignore photos in root folder now
                        $idsGet = [];
                        foreach ($rawPto as $i => $rItem) {
                            if ($i > 9) {       // get 10 photos a time
                                $photos[] = $this->packPhoto([
                                    'id'                   => 0,
                                    'cross_id'             => 0,
                                    'caption'              => '',
                                    'by_identity_id'       => $identity_id,
                                    'created_at'           => '',
                                    'updated_at'           => '',
                                    'external_created_at'  => '',
                                    'external_updated_at'  => '',
                                    'provider'             => 'dropbox',
                                    'external_album_id'    => $album_id,
                                    'external_id'          => $external_id,
                                    'location_title'       => '',
                                    'location_description' => '',
                                    'location_external_id' => '',
                                    'location_lng'         => '',
                                    'location_lat'         => '',
                                    'fullsize_url'         => '',
                                    'fullsize_width'       => 0,
                                    'fullsize_height'      => 0,
                                    'preview_url'          => '',
                                    'preview_width'        => 0,
                                    'preview_height'       => 0,
                                ]);
                            } else {
                                $idsGet[] = $rItem;
                            }
                        }
                        if ($idsGet) {
                            $rawGet = $this->getPhotosFromDropbox($identity_id, $album_id, $idsGet);
                            if ($rawGet) {
                                $photos = array_merge($photos, $rawGet);
                            }
                        }
                    }
                    return ['albums' => $albums, 'photos' => $photos];
                }
            }
        }
        return null;
    }


    public function getPhotosFromDropbox($identity_id, $album_id, $photo_ids) {
        require_once(dirname(dirname(__FILE__)) . '/lib/httpkit.php');
        $hlpIdentity = $this->getHelperByName('Identity');
        $identity = $hlpIdentity->getIdentityById($identity_id);
        if ($identity
         && $identity->connected_user_id > 0
         && $identity->provider === 'dropbox') {
            $token = $hlpIdentity->getOAuthTokenById($identity_id);
            if ($token
             && $token['oauth_token']
             && $token['oauth_token_secret']) {
                $photos = httpKit::request(
                    EXFE_AUTH_SERVER . '/thirdpart/photographers/photos?picture_id=' . implode(',', $photo_ids),
                    null, new Recipient(
                        $identity->id,
                        $identity->connected_user_id,
                        $identity->name,
                        json_encode($token),
                        '',
                        '',
                        '',
                        $identity->provider,
                        $identity->external_id,
                        $identity->external_username
                    ), false, false, 10, 3, 'json', true
                );
                if ($photos
                 && $photos['http_code'] === 200
                 && isset($photos['json'])
                 && $photos['json']) {
                    $rtPhotos = [];
                    foreach ($photos['json'] as $i => $rawPhoto) {
                        $rtPhotos[] = $this->packPhoto([
                            'id'                   => 0,
                            'cross_id'             => 0,
                            'caption'              => '',
                            'by_identity_id'       => $identity_id,
                            'created_at'           => '',
                            'updated_at'           => '',
                            'external_created_at'  => '',
                            'external_updated_at'  => '',
                            'provider'             => 'dropbox',
                            'external_album_id'    => $album_id,
                            'external_id'          => $photo_ids[$i],
                            'location_title'       => '',
                            'location_description' => '',
                            'location_external_id' => '',
                            'location_lng'         => '',
                            'location_lat'         => '',
                            'fullsize_url'         => $rawPhoto,
                            'fullsize_width'       => 0,
                            'fullsize_height'      => 0,
                            'preview_url'          => $rawPhoto,
                            'preview_width'        => 0,
                            'preview_height'       => 0,
                        ]);
                    }
                    return $rtPhotos;
                }
            }
        }
    }


    public function addDropboxAlbumToCross($album_id, $photox_id, $identity_id) {
        if ($identity_id && $album_id && $photox_id) {
            $hlpIdentity = $this->getHelperByName('Identity');
            $identity = $hlpIdentity->getIdentityById($identity_id);
            if ($identity
             && $identity->connected_user_id > 0
             && $identity->provider === 'dropbox') {
                $token = $hlpIdentity->getOAuthTokenById($identity_id);
                if ($token
                 && $token['oauth_token']
                 && $token['oauth_token_secret']) {
                    $recipient = new Recipient(
                        $identity->id,
                        $identity->connected_user_id,
                        $identity->name,
                        json_encode($token),
                        '',
                        '',
                        '',
                        $identity->provider,
                        $identity->external_id,
                        $identity->external_username
                    );
                    return $this->addPhotosByGobus($photox_id, $album_id, $recipient);
                }
            }
        }
        return null;
    }

    // }


    // instagram {

    public function getPhotosFromInstagram($identity_id) {
        $hlpIdentity = $this->getHelperByName('Identity');
        $identity = $hlpIdentity->getIdentityById($identity_id);
        if ($identity
         && $identity->connected_user_id > 0
         && $identity->provider === 'instagram') {
            $token = $hlpIdentity->getOAuthTokenById($identity_id);
            if ($token
             && $token['oauth_token']) {
                $rawPhotos = $this->getInstagramUsersMediaRecent(
                    $identity->external_id, $token
                );
                if ($rawPhotos
                 && isset($rawPhotos->meta)
                 && $rawPhotos->meta->code === 200
                 && isset($rawPhotos->data)
                 && is_array($rawPhotos->data)) {
                    $photos = [];
                    foreach ($rawPhotos->data as $photo) {
                        $updated_at = $created_at = date(
                            'Y-m-d H:i:s', $photo->created_time
                        );
                        $photos[]   = $this->packPhoto([
                            'id'                   => 0,
                            'cross_id'             => 0,
                            'caption'              => $photo->caption->text,
                            'by_identity_id'       => $identity_id,
                            'created_at'           => $created_at,
                            'updated_at'           => $updated_at,
                            'external_created_at'  => $created_at,
                            'external_updated_at'  => $updated_at,
                            'provider'             => 'instagram',
                            'external_album_id'    => $identity_id,
                            'external_id'          => $photo->id,
                            'location_title'       => $photo->location->name,
                            'location_description' => '',
                            'location_external_id' => $photo->location->id,
                            'location_lng'         => $photo->location->longitude,
                            'location_lat'         => $photo->location->latitude,
                            'fullsize_url'         => $photo->images->standard_resolution->url,
                            'fullsize_width'       => $photo->images->standard_resolution->width,
                            'fullsize_height'      => $photo->images->standard_resolution->height,
                            'preview_url'          => $photo->images->standard_resolution->url,
                            'preview_width'        => $photo->images->standard_resolution->width,
                            'preview_height'       => $photo->images->standard_resolution->height,
                        ]);
                    }
                    return ['photos' => $photos, 'next_max_id' => $rawPhotos->pagination->next_max_id];
                }
            }
        }
        return null;
    }


    public function getInstagramUsersSelfFeed($oauth_token) {
        $instagram = new Instagram(
            INSTAGRAM_CLIENT_ID, INSTAGRAM_CLIENT_SECRET, $oauth_token['oauth_token']
        );
        return $instagram->get('users/self/feed');
    }


    public function getInstagramUsersMediaRecent($external_id, $oauth_token) {
        $instagram = new Instagram(
            INSTAGRAM_CLIENT_ID, INSTAGRAM_CLIENT_SECRET, $oauth_token['oauth_token']
        );
        return $instagram->get("users/{$external_id}/media/recent");
    }

    // }


    // Flickr {

    public function getAlbumsFromFlickr($identity_id) {
        $hlpIdentity = $this->getHelperByName('Identity');
        $identity    = $hlpIdentity->getIdentityById($identity_id);
        if ($identity
         && $identity->connected_user_id > 0
         && $identity->provider === 'flickr') {
            $token   = $hlpIdentity->getOAuthTokenById($identity_id);
            if ($token
             && $token['oauth_token']
             && $token['oauth_token_secret']) {
                $objCurl = curl_init(
                    'http://api.flickr.com/services/rest/?method=flickr.photosets.getList&api_key='
                  . FLICKR_KEY
                  . "&user_id={$identity->external_id}&format=json&nojsoncallback=1"
                );
                curl_setopt($objCurl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($objCurl, CURLOPT_CONNECTTIMEOUT, 10);
                $data = curl_exec($objCurl);
                curl_close($objCurl);
                if ($data && ($data = json_decode($data, true)) && isset($data['photosets']) && isset($data['photosets']['photoset'])) {
                    $albums = [];
                    foreach ($data['photosets']['photoset'] as $album) {
                        $albums[] = [
                            'external_id' => $album['id'],
                            'provider'    => 'flickr',
                            'caption'     => $album['title']['_content'],
                            'artwork'     => "http://farm{$album['farm']}.staticflickr.com/{$album['server']}/{$album['primary']}_{$album['secret']}_s.jpg",
                            'count'       => $album['photos'],
                            'size'        => -1,
                            'by_identity' => $identity,
                            'created_at'  => date('Y-m-d H:i:s', $album['date_create']) . ' +0000',
                            'updated_at'  => date('Y-m-d H:i:s', $album['date_update']) . ' +0000',
                        ];
                    }
                    return ['albums' => $albums, 'photos' => []];
                }
            }
        }
        return null;
    }


    public function getPhotosFromFlickr($identity_id, $album_id) {
        $hlpIdentity = $this->getHelperByName('Identity');
        $identity    = $hlpIdentity->getIdentityById($identity_id);
        if ($identity
         && $identity->connected_user_id > 0
         && $identity->provider === 'flickr') {
            $token   = $hlpIdentity->getOAuthTokenById($identity_id);
            if ($token
             && $token['oauth_token']
             && $token['oauth_token_secret']) {
                $objCurl = curl_init(
                    'http://api.flickr.com/services/rest/?method=flickr.photosets.getPhotos&api_key='
                  . FLICKR_KEY
                  . "&photoset_id={$album_id}&extras=date_taken%2Clast_update%2Cgeo%2Curl_m%2Curl_o&format=json&nojsoncallback=1"
                );
                curl_setopt($objCurl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($objCurl, CURLOPT_CONNECTTIMEOUT, 10);
                $data = curl_exec($objCurl);
                curl_close($objCurl);
                if ($data && ($data = json_decode($data, true)) && isset($data['photoset']) && isset($data['photoset']['photo'])) {
                    $albums = [];
                    foreach ($data['photoset']['photo'] as $photo) {
                        $created_at = date('Y-m-d H:i:s', strtotime($photo['datetaken']));
                        $updated_at = date('Y-m-d H:i:s', $photo['lastupdate']);
                        if ((int) $photo['longitude'] === 0
                         && (int) $photo['latitude']  === 0) {
                            $photo['longitude'] = '';
                            $photo['latitude']  = '';
                        }
                        $albums[]   = $this->packPhoto([
                            'id'                   => 0,
                            'cross_id'             => 0,
                            'caption'              => $photo['title'],
                            'by_identity_id'       => $identity_id,
                            'created_at'           => $created_at,
                            'updated_at'           => $updated_at,
                            'external_created_at'  => $created_at,
                            'external_updated_at'  => $updated_at,
                            'provider'             => 'flickr',
                            'external_album_id'    => $album_id,
                            'external_id'          => $photo['id'],
                            'location_title'       => '',
                            'location_description' => '',
                            'location_external_id' => $photo['place_id'],
                            'location_lng'         => "{$photo['longitude']}",
                            'location_lat'         => "{$photo['latitude']}",
                            'fullsize_url'         => isset($photo['url_o'])    ?       $photo['url_o']    : '',
                            'fullsize_width'       => isset($photo['width_o'])  ? (int) $photo['width_o']  : 0,
                            'fullsize_height'      => isset($photo['height_o']) ? (int) $photo['height_o'] : 0,
                            'preview_url'          => $photo['url_m'],
                            'preview_width'        => (int) $photo['width_m'],
                            'preview_height'       => (int) $photo['height_m'],
                        ]);
                    }
                    return $albums;
                }
            }
        }
        return null;
    }


    public function addFlickrAlbumToCross($album_id, $cross_id, $identity_id) {
        if ($album_id && $cross_id && $identity_id) {
            $photos = $this->getPhotosFromFlickr($identity_id, $album_id);
            if ($photos !== null) {
                return $this->addPhotosToCross($cross_id, $photos, $identity_id);
            }
        }
        return null;
    }


    public function getPhotoFromFlickr($photo) {
        $objCurl = curl_init(
            'http://api.flickr.com/services/rest/?method=flickr.photos.getSizes&api_key='
          . FLICKR_KEY . "&photo_id={$photo->external_id}&format=json&nojsoncallback=1"
        );
        curl_setopt($objCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($objCurl, CURLOPT_CONNECTTIMEOUT, 10);
        $data = curl_exec($objCurl);
        curl_close($objCurl);
        if ($data && ($data = json_decode($data, true)) && isset($data['sizes']) && isset($data['sizes']['size'])) {
            foreach ($data['sizes']['size'] as $size) {
                if ($size['label'] === 'Large') {
                    $photo->images['fullsize']['url']    =       $size['url'];
                    $photo->images['fullsize']['width']  = (int) $size['width'];
                    $photo->images['fullsize']['height'] = (int) $size['height'];
                    return $photo;
                }
            }
        }
        return null;
    }

    // }


    // Photo Stream {

    public function getPhotosFromPhotoStream($strId, $identity_id) {
        if ($strId && $identity_id) {
            $objCurl = curl_init(
                "https://p01-sharedstreams.icloud.com/{$strId}/sharedstreams/webstream"
            );
            curl_setopt($objCurl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($objCurl, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($objCurl, CURLOPT_POST, 1);
            curl_setopt($objCurl, CURLOPT_POSTFIELDS, json_encode(['streamCtag' => null]));
            $data = curl_exec($objCurl);
            curl_close($objCurl);
            if ($data && ($data = json_decode($data, true)) && isset($data['photos'])) {
                $photos = [];
                $photoGuids = [];
                foreach ($data['photos'] as $photo) {
                    $updated_at = $created_at = date(
                        'Y-m-d H:i:s', $photo['dateCreated']
                    );
                    $catched = [];
                    foreach ($photo['derivatives'] as $di => $derivative) {
                        if (!isset($catched['preview'])) {
                            $catched['preview']  = $di;
                        } else if (!isset($catched['fullsize'])) {
                            $catched['fullsize'] = $di;
                        }
                    }
                    $photos[] = $this->packPhoto([
                        'id'                   => 0,
                        'cross_id'             => 0,
                        'caption'              => $photo['caption'],
                        'by_identity_id'       => $identity_id,
                        'created_at'           => $created_at,
                        'updated_at'           => $updated_at,
                        'external_created_at'  => $created_at,
                        'external_updated_at'  => $updated_at,
                        'provider'             => 'photostream',
                        'external_album_id'    => '',
                        'external_id'          => $photo['photoGuid'],
                        'location_title'       => '',
                        'location_description' => '',
                        'location_external_id' => '',
                        'location_lng'         => '',
                        'location_lat'         => '',
                        'fullsize_url'         => $photo['derivatives'][$catched['fullsize']]['checksum'],
                        'fullsize_width'       => $photo['derivatives'][$catched['fullsize']]['width'],
                        'fullsize_height'      => $photo['derivatives'][$catched['fullsize']]['height'],
                        'preview_url'          => $photo['derivatives'][$catched['preview']]['checksum'],
                        'preview_width'        => $photo['derivatives'][$catched['preview']]['width'],
                        'preview_height'       => $photo['derivatives'][$catched['preview']]['height'],
                    ]);
                    $photoGuids[] = $photo['photoGuid'];
                }
                $objCurl = curl_init(
                    "https://p01-sharedstreams.icloud.com/{$strId}/sharedstreams/webasseturls"
                );
                curl_setopt($objCurl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($objCurl, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($objCurl, CURLOPT_POST, 1);
                curl_setopt($objCurl, CURLOPT_POSTFIELDS, json_encode(['photoGuids' => $photoGuids]));
                $data = curl_exec($objCurl);
                curl_close($objCurl);
                if ($data && ($data = json_decode($data, true)) && isset($data['items'])) {
                    $hosts = [
                        "https://{$data['locations']['ms_ap_sin']['hosts'][0]}",
                        "https://{$data['locations']['ms_ap_sin']['hosts'][1]}"
                    ];
                    foreach ($photos as $pI => $photo) {
                        $objFullsize = $data['items'][$photos[$pI]->images['fullsize']['url']];
                        $objPreview  = $data['items'][$photos[$pI]->images['preview']['url']];
                        $photos[$pI]->images['fullsize']['url']        = "{$hosts[0]}{$objFullsize['url_path']}";
                        $photos[$pI]->images['preview']['url']         = "{$hosts[1]}{$objPreview['url_path']}";
                        $photos[$pI]->images['fullsize']['expired_at'] = date('Y-m-d H:i:s', strtotime($objFullsize['url_expiry']))  . ' +0000';
                        $photos[$pI]->images['preview']['expired_at']  = date('Y-m-d H:i:s', strtotime($objPreview['url_expiry'])) . ' +0000';
                    }
                }
                return $photos;
            }
        }
        return null;
    }


    public function getPhotoFromPhotoStream($photo) {
        if ($photo) {
            $url     = $photo->images['fullsize']['url'];
            $arr_url = explode('/', $url);
            $hash    = array_pop($arr_url);
            $objCurl = curl_init(
                "https://p01-sharedstreams.icloud.com/{$photo->external_album_id}/sharedstreams/webasseturls"
            );
            curl_setopt($objCurl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($objCurl, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($objCurl, CURLOPT_POST, 1);
            curl_setopt($objCurl, CURLOPT_POSTFIELDS, json_encode(['photoGuids' => $photo->external_id]));
            $data = curl_exec($objCurl);
            curl_close($objCurl);
            if ($data && ($data = json_decode($data, true)) && isset($data['items'])) {
                $hosts = [
                    "https://{$data['locations']['ms_ap_sin']['hosts'][0]}",
                    "https://{$data['locations']['ms_ap_sin']['hosts'][1]}"
                ];
                $objFullsize = @ $data['items'][$hash];
                if ($objFullsize) {
                    $photo->images['fullsize']['url']        = "{$hosts[0]}{$objFullsize['url_path']}";
                    $photo->images['fullsize']['expired_at'] = date('Y-m-d H:i:s', strtotime($objFullsize['url_expiry']))  . ' +0000';
                    return $photo;
                }
            }
        }
        return null;
    }


    public function addPhotoStreamToCross($stream_id, $photox_id, $identity_id) {
        if ($identity_id && $stream_id && $photox_id) {
            $hlpIdentity = $this->getHelperByName('Identity');
            $identity = $hlpIdentity->getIdentityById($identity_id);
            if ($identity
             && $identity->connected_user_id > 0) {
                $token = $hlpIdentity->getOAuthTokenById($identity_id);
                if ($token
                 && $token['oauth_token']
                 && $token['oauth_token_secret']) {
                    $recipient = new Recipient(
                        $identity->id,
                        $identity->connected_user_id,
                        $identity->name,
                        json_encode($token),
                        '',
                        '',
                        '',
                        'photostream',
                        $identity->external_id,
                        $identity->external_username
                    );
                    return $this->addPhotosByGobus($photox_id, $stream_id, $recipient);
                }
            }
        }
        return null;
    }

    // }

}
