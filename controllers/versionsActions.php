<?php

class VersionsActions extends ActionController {

    public function doIndex() {
        header('Content-Type: application/json; charset=UTF-8');
        apiResponse([
            'ios' => [
                'version'     => CLIENT_IOS_VERSION,
                'description' => CLIENT_IOS_DESCRIPTION,
                'url'         => CLIENT_IOS_URL,
            ],
            'andriod' => [
                'version'     => CLIENT_ANDROID_VERSION,
                'description' => CLIENT_ANDROID_DESCRIPTION,
                'url'         => CLIENT_ANDROID_URL,
            ]
        ]);
    }

}
