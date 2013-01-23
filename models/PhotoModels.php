<?php

class PhotoModels extends DataModel {

    public function packPhoto($rawPhoto) {
        $hlpIdentity = $this->getHelperByName('Identity');
        $identity = $hlpIdentity->getIdentityById($rawPhoto['by_identity_id']);
        $location = $rawPhoto['location_lng'] &&  $rawPhoto['location_lat'] ? new Place(
            0, '', '', $rawPhoto['location_lng'], $rawPhoto['location_lat'],
            $rawPhoto['provider'],   $rawPhoto['external_id'],
            $rawPhoto['created_at'], $rawPhoto['updated_at']
        ) : null;
        $images   = [
            'fullsize'  => [
                'height' => $rawPhoto['fullsize_height'],
                'width'  => $rawPhoto['fullsize_width'],
                'url'    => $rawPhoto['fullsize_url'],
            ],
            'thumbnail' => [
                'height' => $rawPhoto['thumbnail_height'],
                'width'  => $rawPhoto['thumbnail_width'],
                'url'    => $rawPhoto['thumbnail_url'],
            ],
        ];
        return new Photo(
            $rawPhoto['id'], $rawPhoto['caption'], $identity,
            $rawPhoto['created_at'], $rawPhoto['updated_at'],
            $rawPhoto['provider'],   $rawPhoto['external_id'],
            $location, $images
        );
    }


    public function getPhotosByCrossId($cross_id) {
        $photos = [];
        $rawPhotos = $this->getAll(
            "SELECT * FROM `photos` WHERE `cross_id` = {$cross_id}"
        );
        foreach ($rawPhotos ?: [] as $rawPhoto) {
            $photos[] = $this->packPhoto($rawPhoto);
        }
        return $photos;
    }


    public function addPhotosToCross($cross_id, $photos, $identity_id) {
        if ($cross_id && is_array($photos) && $identity_id) {
            foreach ($photos as $photo) {
                $strSql = "
                    `caption`             = '{$photo->caption}',
                    `by_identity_id`      =  {$identity_id},
                    `updated_at`          =  NOW(),
                    `external_created_at` = '" . date('Y-m-d H:i:s', strtotime($photo->created_at)) . "',
                    `external_updated_at` = '" . date('Y-m-d H:i:s', strtotime($photo->updated_at)) . "',
                    `location_lng`        = '" . ($photo->location ? $photo->location->lng  :  '')  . "',
                    `location_lat`        = '" . ($photo->location ? $photo->location->lat  :  '')  . "',
                    `fullsize_url`        = '{$photo->images['fullsize']['url']}',
                    `fullsize_width`      =  {$photo->images['fullsize']['width']},
                    `fullsize_height`     =  {$photo->images['fullsize']['height']},
                    `thumbnail_url`       = '{$photo->images['thumbnail']['url']}',
                    `thumbnail_width`     =  {$photo->images['thumbnail']['width']},
                    `thumbnail_height`    =  {$photo->images['thumbnail']['height']}
                ";
                $curImg = $this->getRow(
                    "SELECT * FROM `photos`
                     WHERE `cross_id`     =  {$cross_id}
                     AND   `provider`     = 'facebook'
                     AND   `external_id`  = '{$photo->external_id}'"
                );
                if ($curImg) {
                    $this->query(
                        "UPDATE `photos` SET     {$strSql}
                         WHERE  `cross_id`    =  {$cross_id}
                         AND    `provider`    = 'facebook'
                         AND    `external_id` = '{$photo->external_id}'"
                    );
                } else {
                    $this->query(
                        "INSERT INTO `photos` SET
                         `cross_id`           =  {$cross_id},
                         `provider`           = 'facebook',
                         `external_id`        = '{$photo->external_id}',
                         `created_at`         =  NOW(), {$strSql}"
                    );
                }
            }
            return true;
        }
        return false;
    }


    // facebook {

    public function getAlbumsFromFacebook($identity_id) {
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
                curl_setopt($objCurl, CURLOPT_CONNECTTIMEOUT, 23);
                $data = curl_exec($objCurl);
                curl_close($objCurl);
                if ($data && ($data = json_decode($data, true)) && isset($data['data'])) {
                    $albums = [];
                    foreach ($data['data'] as $album) {
                        $albums[] = [
                            'external_id' => $album['id'],
                            'provider'    => 'facebook',
                            'caption'     => $album['name'],
                            'count'       => $album['count'],
                            'size'        => -1,
                            'by_identity' => $identity,
                            'created_at'  => date('Y-m-d H:i:s', strtotime($album['created_time'])),
                            'updated_at'  => date('Y-m-d H:i:s', strtotime($album['updated_time'])),
                        ];
                    }
                    return $albums;
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
                curl_setopt($objCurl, CURLOPT_CONNECTTIMEOUT, 23);
                $data = curl_exec($objCurl);
                curl_close($objCurl);
                if ($data && ($data = json_decode($data, true)) && isset($data['data'])) {
                    $albums = [];
                    foreach ($data['data'] as $photo) {
                        $created_at = date('Y-m-d H:i:s', strtotime($photo['created_time']));
                        $updated_at = date('Y-m-d H:i:s', strtotime($photo['updated_time']));
                        $albums[]   = $this->packPhoto([
                            'id'                  => 0,
                            'cross_id'            => 0,
                            'caption'             => $photo['name'],
                            'by_identity_id'      => $identity_id,
                            'created_at'          => $created_at,
                            'updated_at'          => $updated_at,
                            'external_created_at' => $created_at,
                            'external_updated_at' => $updated_at,
                            'provider'            => 'facebook',
                            'external_id'         => $photo['id'],
                            'location_lng'        => '',
                            'location_lat'        => '',
                            'fullsize_url'        => $photo['images'][0]['source'],
                            'fullsize_width'      => $photo['images'][0]['width'],
                            'fullsize_height'     => $photo['images'][0]['height'],
                            'thumbnail_url'       => $photo['source'],
                            'thumbnail_width'     => $photo['width'],
                            'thumbnail_height'    => $photo['height'],
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
                curl_setopt($objCurl, CURLOPT_CONNECTTIMEOUT, 23);
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
                    foreach ($data['contents'] as $album) {
                        if ($album['is_dir']) {
                            $path = explode('/', $album['path']);
                            $albums[] = [
                                'external_id' => implode('/', array_map('rawurlencode', explode('/', $album['path']))),
                                'provider'    => 'dropbox',
                                'caption'     => array_pop($path),
                                'count'       => -1,
                                'size'        => $album['size'],
                                'by_identity' => $identity,
                                'created_at'  => date('Y-m-d H:i:s', strtotime($album['modified'])),
                                'updated_at'  => date('Y-m-d H:i:s', strtotime($album['modified'])),
                            ];
                        }
                    }
                    return $albums;
                }
            }
        }
        return null;
    }

    // }


    // instagram {

    public function getPhotosFromInstagram() {

    }

    // }

}
