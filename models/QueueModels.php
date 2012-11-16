<?php

class QueueModels extends DataModel {

    public $hlpGobus = null;


    public function __construct() {
        $this->hlpGobus = $this->getHelperByName('Gobus');
    }


    public function pushToQueue($queue, $method, $data) {
        return $this->hlpGobus->useGobusApi(
            EXFE_GOBUS_SERVER, $queue, $method, $data
        );
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


    public function pushJobToQueue($queue, $service, $method, $invitations, $data = []) {
        $tos  = [];
        foreach ($invitations as $invitation) {
            $tos[] = $this->makeRecipientByInvitation($invitation);
        }
        $jobData = [
            'service'   => $service,
            'method'    => $method,
            'merge_key' => (string) $data['cross']->id,
            'tos'       => $tos,
            'data'      => $data,
        ];
        if (DEBUG) {
            error_log('job: ' . json_encode($jobData));
        }
        return $this->pushToQueue($queue, 'Push', $jobData);
    }


    public function getToInvitationsByExfee($exfee, $by_user_id, $event, $incExfee = [], $excExfee = []) {
        $hlpDevice       = $this->getHelperByName('Device');
        $hlpConversation = $this->getHelperByName('Conversation');
        $head10  = [];
        $instant = [];
        $chkUser = [];
        foreach ($incExfee as $ieI => $ieItem) {
            $incExfee[$ieI]->inc = true;
        }
        foreach (array_merge($exfee->invitations, $incExfee) as $invitation) {
            if ($invitation->identity->connected_user_id === $by_user_id
            ||  $invitation->rsvp_status                 === 'DECLINED'
            || (!isset($invitation->inc)
             && $invitation->rsvp_status                 === 'REMOVED')) {
                continue;
            }
            // exclude {
            $bolContinue = false;
            foreach ($excExfee as $eI => $eItem) {
                if ($invitation->id === $eItem->id) {
                    $bolContinue = true;
                    break;
                }
            }
            // }
            if ($bolContinue) {
                continue;
            }
            $gotInvitation = [(object) (array) $invitation];
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
                $hlpConversation->addConversationCounter(
                    $exfee->id,
                    $invitation->identity->connected_user_id
                );
                // marked
                $chkUser[$invitation->identity->connected_user_id] = true;
            }
            switch ($event) {
                case 'Conversation_Update':
                    foreach ($gotInvitation as $item) {
                        switch ($item->identity->provider) {
                            case 'email':
                            case 'facebook':
                                $head10[]  = $item;
                                break;
                            case 'twitter':
                                break;
                            case 'iOS':
                            case 'Android':
                                $instant[] = $item;
                        }
                    }
                    break;
                case 'Cross_Invite':
                    foreach ($gotInvitation as $item) {
                        switch ($item->identity->provider) {
                            case 'email':
                            case 'twitter':
                            case 'facebook':
                            case 'iOS':
                            case 'Android':
                                $instant[] = $item;
                        }
                    }
                    break;
                case 'Cross_Summary':
                    foreach ($gotInvitation as $item) {
                        switch ($item->identity->provider) {
                            case 'email':
                            case 'facebook':
                                $head10[]  = $item;
                                break;
                            case 'twitter':
                                break;
                            case 'iOS':
                            case 'Android':
                                $instant[] = $item;
                        }
                    }
            }
        }
        return ['Head10' => $head10, 'Instant' => $instant];
    }


    public function despatchConversation($cross, $post, $by_user_id) {
        $service = 'Conversation';
        $method  = 'Update';
        $invitations = $this->getToInvitationsByExfee(
            $cross->exfee, $by_user_id, "{$service}_{$method}"
        );
        $result = true;
        foreach ($invitations as $invI => $invItems) {
            if ($invItems) {
                if ($this->pushJobToQueue(
                    $invI, $service, $method, $invItems,
                    ['cross' => $cross, 'post' => $post]
                )) {
                    $result = false;
                }
            }
        }
        return $result;
    }


    public function despatchInvitation($cross, $to_exfee, $by_user_id) {
        $service = 'Cross';
        $method  = 'Invite';
        $invitations = $this->getToInvitationsByExfee(
            $to_exfee, $by_user_id, "{$service}_{$method}"
        );
        $result = true;
        foreach ($invitations as $invI => $invItems) {
            if ($invItems) {
                if ($this->pushJobToQueue(
                    $invI, $service, $method, $invItems,
                    ['cross' => $cross]
                )) {
                    $result = false;
                }
            }
        }
        return $result;
    }


    public function despatchSummary($cross, $old_cross, $inc_exfee, $exc_exfee, $by_user_id) {
        $service = 'Cross';
        $method  = 'Summary';
        $invitations = $this->getToInvitationsByExfee(
            $cross->exfee, $by_user_id, "{$service}_{$method}", $inc_exfee, $exc_exfee
        );
        $result = true;
        foreach ($invitations as $invI => $invItems) {
            if ($invItems) {
                if ($this->pushJobToQueue(
                    $invI, $service, $method, $invItems,
                    ['cross' => $cross, 'old_cross' => $old_cross]
                )) {
                    $result = false;
                }
            }
        }
        return $result;
    }


    public function updateFriends($identity, $oauth_info) {
        $service   = 'Thirdpart';
        $method    = 'Send';
        $recipient = new Recipient(
            $identity->id,
            $identity->connected_user_id,
            $identity->name,
            '',
            '',
            json_encode($oauth_info),
            '',
            $identity->provider,
            $identity->external_id,
            $identity->external_username
        );
        return $this->pushJobToQueue('Instant', $service, $method, [$recipient]);
    }

}
