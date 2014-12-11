<?php

class MuteActions extends ActionController {

    public function doCross() {
        // get token
        $token = dbescape(trim($_GET['token']));
        $modExfee = $this->getModelByName('Exfee');
        $objToken = $modExfee->getRawInvitationByToken($token);
        if (!$token) {
            header('HTTP/1.1 404 Not Found');
            return;
        }
        // get user id
        $modUser = $this->getModelByName('User');
        $user_id = $modUser->getUserIdByIdentityId($objToken['identity_id']);
        $user_id = $user_id ?: -$objToken['identity_id'];
        // mute
        $modMute = $this->getModelByName('Mute');
        $modMute->setMute($objToken['cross_id'], $user_id);
        header("location: /#!token={$token}/mute");
    }

}
