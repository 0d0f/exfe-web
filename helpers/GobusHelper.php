<?php

class GobusHelper extends ActionController {

    public $modGobus = null;


    public function __construct() {
        $this->modGobus = $this->getModelByName('Gobus');
    }


    public function useGobusApi($server, $api, $method, $postArgs = [], $encode_fields = false, $getArgs = [], $id = '') {
        return $this->modGobus->useGobusApi(
            $server, $api, $method, $postArgs, $encode_fields, $getArgs, $id
        );
    }

}
