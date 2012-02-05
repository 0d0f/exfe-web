<?php
require_once dirname(dirname(__FILE__))."/config.php";
require_once dirname(dirname(__FILE__))."/common.php";
require_once dirname(dirname(__FILE__))."/DataModel.php";
require_once dirname(dirname(__FILE__))."/lib/tmhOAuth.php";

class Twitter_Job.php {

    public function perform() {
        print_r($this->args);
    }

}

?>
