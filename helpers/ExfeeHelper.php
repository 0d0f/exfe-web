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


    public function getRawInvitationByCrossIdAndIdentityId($cross_id, $identity_id) {
        return $this->modExfee->getRawInvitationByCrossIdAndIdentityId($cross_id, $identity_id);
    }


    public function getRawInvitationByExfeeIdAndIdentityId($exfee_id, $identity_id, $cross_id = 0) {
        return $this->modExfee->getRawInvitationByExfeeIdAndIdentityId($exfee_id, $identity_id, $cross_id);
    }


    public function updateExfee($exfee, $by_identity_id, $user_id = 0, $rsvp_only = false, $draft = false, $keepRsvp = false, $timezone = '', $asJoin = false) {
        return $this->modExfee->updateExfee($exfee, $by_identity_id, $user_id, $rsvp_only, $draft, $keepRsvp, $timezone, $asJoin);
    }


    public function updateExfeeTime($exfee_id, $quick = false) {
        return $this->modExfee->updateExfeeTime($exfee_id, $quick);
    }

}
