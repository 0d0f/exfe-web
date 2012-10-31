<?php

class QueueModels extends DataModel {

    public $hlpGobus = null;


    public function __construct() {
        $this->hlpGobus = $this->getHelperByName('Gobus');
    }


    public function rawPushToQueue($queue, $method, $data) {
        return $this->hlpGobus->useGobusApi(
            EXFE_GOBUS_SERVER, $queue, $method, $data
        );
    }


    public function pushToQueue($queue, $data) {
        return $this->rawPushToQueue($queue, 'Push', $data);
    }

}
