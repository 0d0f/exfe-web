<?php

class GobusHelper extends ActionController {

    public $modGobus = null;


    public function __construct() {
        $this->modGobus = $this->getModelByName('Gobus');
    }


    public function useGobusApi($server, $api, $method, $args, $encode_fields = false, $get = false, $id = '') {
        return $this->modGobus->useGobusApi(
            $server, $api, $method, $args, $encode_fields, $get, $id
        );
    }

}
