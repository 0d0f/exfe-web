<?php

class VersionsActions extends ActionController {

    public function doIndex() {
        header('Content-Type: application/json; charset=UTF-8');
        apiResponse([
            'ios' => [
                'version'     => '2.1',
                'description' => 'ageafe',
                'url'         => 'https://itunes.apple.com/us/app/exfe/id514026604',
            ],
            'andriod' => [
                'version'     => '',
                'description' => '',
                'url'         => '',
            ]
        ]);
    }

}