<?php

class ExfeeHelper extends ActionController {

	protected $modExfee = null;


	public function __construct() {
		$this->modExfee = $this->getModelByName('exfee');
	}


    public function getExfeeIdByUserid($userid, $updated_at = '') {
        return $this->modExfee->getExfeeIdByUserid($userid, $updated_at);
    }


    public function getCrossIdByExfeeId($exfee_id) {
        return $this->modExfee->getCrossIdByExfeeId($exfee_id);
    }

}
