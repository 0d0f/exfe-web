<?php

class HistoryHelper extends ActionController {

    protected $modHistory = null;


    public function __construct() {
        $this->modHistory = $this->getModelByName('History');
    }


    public function log($by_identity_id, $module, $module_id, $action, $data, $index_id = '') {
        $this->modHistory->log($by_identity_id, $module, $module_id, $action, $data, $index_id);
    }


    public function getLogs($index_id, $module = '', $module_id = '', $limit = 1000) {
        $this->modHistory->getLogs($index_id, $module, $module_id, $limit);
    }

}
