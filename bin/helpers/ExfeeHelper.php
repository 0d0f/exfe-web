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


    public function getExfeeIdByCrossId($cross_id) {
        return $this->modExfee->getExfeeIdByCrossId($cross_id);
    }


    public function getHostIdentityIdsByExfeeId($exfee_id) {
        return $this->modExfee->getHostIdentityIdsByExfeeId($exfee_id);
    }


    public function updateInvitationRemarkById($id, $remark) {
        return $this->modExfee->updateInvitationRemarkById($id, $remark);
    }


    public function getExfeeById($id, $withRemoved = false, $withToken = false) {
        return $this->modExfee->getExfeeById($id, $withRemoved, $withToken);
    }

}
