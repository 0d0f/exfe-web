<?php

class PhotoModels extends DataModel {

    public function packPhoto() {

    }


    public function getPhotosByCrossId($cross_id) {

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
                if ($data && ($data = (array) json_decode($data)) && isset($data['data'])) {
                    $albums = [];
                    foreach ($data['data'] as $album) {
                        $albums[] = [
                            'external_id' => $album->id,
                            'provider'    => 'facebook',
                            'caption'     => $album->name,
                            'count'       => $album->count,
                            'by_identity' => $identity,
                            'created_at'  => date('Y-m-d H:i:s', strtotime($album->created_time)),
                            'updated_at'  => date('Y-m-d H:i:s', strtotime($album->updated_time)),
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
        $identity = $hlpIdentity->getIdentityById($identity_id);
        if ($identity
         && $identity->connected_user_id > 0
         && $identity->provider === 'facebook') {
            $token = $hlpIdentity->getOAuthTokenById($identity_id);
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
                if ($data && ($data = (array) json_decode($data)) && isset($data['data'])) {
                    return $data['data'];
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
