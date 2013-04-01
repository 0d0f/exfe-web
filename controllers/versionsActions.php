<?php

class VersionsActions extends ActionController {

    public function doIndex() {
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