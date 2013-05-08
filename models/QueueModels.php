<?php

require_once dirname(dirname(__FILE__)) . '/lib/httpkit.php';


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
            $invitation->identity->auth_data ?: '',
            '',
            $invitation->token ?: '',
            '',
            $invitation->identity->provider,
            $invitation->identity->external_id,
            $invitation->identity->external_username
        );
    }


    public function cleanInvitations($invitations) {
        $clear_invitations = [];
        foreach ($invitations as $invitation) {
            if ($invitation->rsvp_status !== 'REMOVED') {
                $clear_invitations[] = $invitation;
            }
        }
        return $clear_invitations;
    }


    public function pushJobToQueue($queue, $service, $method, $invitations, $data = []) {
        $tos  = [];
        foreach ($invitations as $invitation) {
            $tos[] = $this->makeRecipientByInvitation($invitation);
        }
        if (isset($data['cross'])) {
            $data['cross']->exfee->invitations     = $this->cleanInvitations(
                $data['cross']->exfee->invitations
            );
        }
        if (isset($data['old_cross'])) {
            $data['old_cross']->exfee->invitations = $this->cleanInvitations(
                $data['old_cross']->exfee->invitations
            );
        }
        if ($queue === 'Digest') {
            return httpKit::request(
                EXFE_GOBUS_SERVER . "/v3/queue/-/POST/" . EXFE_BUS_SERVICES . '/v3/splitter',
                [
                    'update'     => 'always',
                    'ontime'     => time(),
                ],
                [
                    'recipients' => $tos,
                    'merge_key'  => "cross{$data['cross']->id}",
                    'method'     => 'POST',
                    'service'    => EXFE_AUTH_SERVER . '/v3/notifier/cross/digest',
                    'type'       => 'always',
                    'ontime'     => strtotime('tomorrow'),
                    'data'       => [
                        'cross_id'   => $data['cross']->id,
                        'updated_at' => $data['cross']->updated_at,
                    ],
                ], false, false, 3, 3, 'json'
            );
        } else {
            $jobData = [
                'service'   => $service,
                'method'    => $method,
                'merge_key' => "{$service}_{$method}" === 'Cross_Invite' ? '' : (string) $data['cross']->id,
                'tos'       => $tos,
                'data'      => $data ?: new stdClass,
            ];
            return $this->pushToQueue($queue, 'Push', $jobData);
        }
    }


    public function getToInvitationsByExfee($cross, $by_user_id, $event, $incExfee = [], $excExfee = []) {
        $hlpDevice       = $this->getHelperByName('Device');
        $hlpConversation = $this->getHelperByName('Conversation');
        $hlpMute         = $this->getHelperByName('Mute');
        $head2           = [];
        $head10          = [];
        $tail10          = [];
        $instant         = [];
        $digest          = [];
        $chkUser         = [];
        $userPerProvider = [];
        $gotInvitation   = [];
        foreach ($incExfee as $ieI => $ieItem) {
            $incExfee[$ieI]->inc = true;
        }
        foreach (array_merge($cross->exfee->invitations, $incExfee) as $invitation) {
            if ($invitation->rsvp_status === 'DECLINED'
            || ($invitation->rsvp_status === 'REMOVED' && !isset($invitation->inc))) {
                continue;
            }
            // exclude {
            $bolContinue = false;
            foreach ($excExfee as $eI => $eItem) {
                if (isset($eItem->id)) {
                    if ($invitation->id === $eItem->id) {
                        $bolContinue = true;
                        break;
                    }
                } else {
                    if ($invitation->identity->provider          === $eItem->identity->provider
                     && $invitation->identity->external_username === $eItem->identity->external_username) {
                        $bolContinue = true;
                        break;
                    }
                }
            }
            if ($bolContinue) {
                continue;
            }
            // }
            // mute {
            if ($hlpMute->getMute($cross->id, $invitation->identity->connected_user_id)) {
                continue;
            }
            // }
            $gotInvitation[] = deepClone($invitation);
            if ($invitation->identity->connected_user_id > 0
            && !$chkUser[$invitation->identity->connected_user_id]) {
                // get mobile identities
                $mobIdentities = $hlpDevice->getDevicesByUserid(
                    $invitation->identity->connected_user_id,
                    $invitation->identity
                );
                foreach ($mobIdentities as $mI => $mItem) {
                    $tmpInvitation = deepClone($invitation);
                    $tmpInvitation->identity = $mItem;
                    $gotInvitation[] = $tmpInvitation;
                }
                // set conversation counter
                if ($event === 'Conversation_Update') {
                    $hlpConversation->addConversationCounter(
                        $cross->exfee->id,
                        $invitation->identity->connected_user_id
                    );
                }
                // marked
                $chkUser[$invitation->identity->connected_user_id] = true;
            }
        }
        // get digest identities {
        if (in_array($event, ['Conversation_Update', 'Cross_Summary'])) {
            foreach ($gotInvitation as $gI => $gItem) {
                if ($gItem->identity->connected_user_id > 0) {
                    $provider = ($gItem->identity->provider === 'iOS' || $gItem->identity->provider === 'Android')
                              ? 'device' : $gItem->identity->provider;
                    $userPerProvider[$provider][$gItem->identity->connected_user_id] = $gI;
                }
            }
            foreach ($chkUser as $cuI => $cuItem) {
                if ((isset($userPerProvider['device'][$cuI]) && isset($userPerProvider['email'][$cuI]))
                 || (isset($userPerProvider['phone'][$cuI])  && isset($userPerProvider['email'][$cuI])
                  && $gotInvitation[$userPerProvider['email'][$cuI]]->rsvp_status === 'NOTIFICATION')) {
                    $digest[] = $gotInvitation[$userPerProvider['email'][$cuI]];
                    unset($gotInvitation[$userPerProvider['email'][$cuI]]);
                }
            }
        }
        // }
        switch ($event) {
            case 'Conversation_Update':
                foreach ($gotInvitation as $item) {
                    switch ($item->identity->provider) {
                        case 'email':
                        case 'facebook':
                            $head10[]  = $item;
                            break;
                        case 'phone':
                        case 'twitter':
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
                            $imsgInv = deepClone($item);
                            $imsgInv->identity->provider = 'phone';
                            $instant[] = $imsgInv;
                        case 'phone':
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
                        case 'twitter':
                        case 'facebook':
                            $tail10[]  = $item;
                            break;
                        case 'phone':
                        case 'iOS':
                        case 'Android':
                            $head2[]   = $item;
                    }
                }
        }
        return [
            'Head2'   => $head2,
            'Head10'  => $head10,
            'Tail10'  => $tail10,
            'Instant' => $instant,
            'Digest'  => $digest,
        ];
    }


    public function despatchConversation(
        $cross,
        $post,
        $by_user_id,
        $by_identity_id,
        $exclude_identities = []
    ) {
        $service     = 'Conversation';
        $method      = 'Update';
        $hlpIdentity = $this->getHelperByName('Identity');
        $objIdentity = $hlpIdentity->getIdentityById($by_identity_id);
        $invitations = $this->getToInvitationsByExfee(
            $cross, $by_user_id, "{$service}_{$method}", [], $exclude_identities
        );
        $result = true;
        foreach ($invitations as $invI => $invItems) {
            if ($invItems) {
                if ($this->pushJobToQueue(
                    $invI, $service, $method, $invItems,
                    ['cross' => $cross, 'post' => $post, 'by' => $objIdentity]
                )) {
                    $result = false;
                }
            }
        }
        return $result;
    }


    public function despatchInvitation($cross, $to_exfee, $by_user_id, $by_identity_id) {
        $service     = 'Cross';
        $method      = 'Invite';
        $hlpIdentity = $this->getHelperByName('Identity');
        $objIdentity = $hlpIdentity->getIdentityById($by_identity_id);
        $dpCross     = new stdClass;
        $dpCross->id = $cross->id;
        $dpCross->exfee = $to_exfee;
        $invitations = $this->getToInvitationsByExfee(
            $dpCross, $by_user_id, "{$service}_{$method}"
        );
        $result = true;
        foreach ($invitations as $invI => $invItems) {
            if ($invItems) {
                if ($this->pushJobToQueue(
                    $invI, $service, $method, $invItems,
                    ['cross' => $cross, 'by' => $objIdentity]
                )) {
                    $result = false;
                }
            }
        }
        return $result;
    }


    public function despatchSummary($cross, $old_cross, $inc_exfee, $exc_exfee, $by_user_id, $by_identity_id) {
        $service     = 'Cross';
        $method      = 'Summary';
        $hlpIdentity = $this->getHelperByName('Identity');
        $objIdentity = $hlpIdentity->getIdentityById($by_identity_id);
        $invitations = $this->getToInvitationsByExfee(
            $cross, $by_user_id, "{$service}_{$method}", $inc_exfee, $exc_exfee
        );
        $result = true;
        foreach ($invitations as $invI => $invItems) {
            if ($invItems) {
                if ($this->pushJobToQueue(
                    $invI, $service, $method, $invItems,
                    ['cross' => $cross, 'old_cross' => $old_cross, 'by' => $objIdentity]
                )) {
                    $result = false;
                }
            }
        }
        return $result;
    }


    public function updateIdentity($identity, $oauth_info) {
        $service     = 'Thirdpart';
        $method      = 'UpdateIdentity';
        $identity    = deepClone($identity);
        $identity->auth_data = json_encode($oauth_info);
        $invitations = [(object) ['identity' => $identity]];
        return $this->pushJobToQueue('Instant', $service, $method, $invitations);
    }


    public function updateFriends($identity, $oauth_info) {
        $service     = 'Thirdpart';
        $method      = 'UpdateFriends';
        $identity    = deepClone($identity);
        $identity->auth_data = json_encode($oauth_info);
        $invitations = [(object) ['identity' => $identity]];
        return $this->pushJobToQueue('Instant', $service, $method, $invitations);
    }

}
