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


    public function makeRecipientByInvitation($invitation) {
        return new Recipient(
            $invitation->identity->id,
            $invitation->identity->connected_user_id,
            $invitation->identity->name,
            '',
            '',
            $invitation->token,
            '',
            $invitation->identity->provider,
            $invitation->identity->external_id,
            $invitation->identity->external_username
        );
    }


    public function pushConversationToQueue($queue, $invitations, $cross, $post) {
        $tos  = [];
        foreach ($invitations as $invitation) {
            $tos[] = $this->makeRecipientByInvitation($invitation);
        }
        $data = [
            'service' => 'Conversation',
            'method'  => 'Update',
            'key'     => (string) $cross->id,
            'tos'     => $tos,
            'data'    => ['cross' => $cross, 'post' => $post],
        ];
        if (DEBUG) {
            error_log('tos: '  . json_encode($tos));
            error_log('data: ' . json_encode($data));
        }
        return $this->pushToQueue($queue, $data);
    }


    public function despatchConversation($cross, $post, $by_user_id) {
        $hlpDevice       = $this->getHelperByName('Device');
        $hlpConversation = $this->getHelperByName('Conversation');
        $head10  = [];
        $instant = [];
        $chkUser = [];
        foreach ($cross->exfee->invitations as $invitation) {
            if ($invitation->rsvp_status === 'DECLINED') {
                continue;
            }
            $gotInvitation = [$invitation];
            if ($invitation->identity->connected_user_id > 0
            && !$chkUser[$invitation->identity->connected_user_id]) {
                // get mobile identities
                $mobIdentities = $hlpDevice->getDevicesByUserid(
                    $invitation->identity->connected_user_id,
                    $invitation->identity
                );
                foreach ($mobIdentities as $mI => $mItem) {
                    $tmpInvitation = $invitation;
                    $tmpInvitation->identity = $mItem;
                    $gotInvitation[] = $tmpInvitation;
                }
                // set conversation counter
                if ($invitation->identity->connected_user_id !== $by_user_id) {
                    $hlpConversation->addConversationCounter(
                        $cross->exfee->id,
                        $invitation->identity->connected_user_id
                    );
                }
                // marked
                $chkUser[$invitation->identity->connected_user_id] = true;
            }
            foreach ($gotInvitation as $invitation) {
                switch ($invitation->identity->provider) {
                    case 'email':
                        $head10[]  = $invitation;
                        break;
                    case 'iOS':
                    case 'Android':
                        $instant[] = $invitation;
                }
            }
        }
        $h10Result = $head10  ? $this->pushConversationToQueue(
            'Head10',  $head10, $cross, $post
        ) : true;
        $insResult = $instant ? $this->pushConversationToQueue(
            'Instant', $head10, $cross, $post
        ) : true;
        return $h10Result && $insResult;
    }

}
