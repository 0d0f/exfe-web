<?php

require_once dirname(dirname(__FILE__)) . '/lib/httpkit.php';


class QueueModels extends DataModel {

    public $hlpGobus = null;


    public function fireBus(
        $recipients, $merge_key, $method, $service,
        $update, $ontime, $data, $cstRequest = ''
    ) {
        return httpKit::request(
            EXFE_AUTH_SERVER . '/v3/splitter',
            null, [
                'recipients' => $recipients,
                'merge_key'  => $merge_key,
                'method'     => $method,
                'service'    => base64_url_encode($service),
                'update'     => $update,
                'ontime'     => $ontime,
                'data'       => $data ?: new stdClass,
            ], false, false, 3, 3, 'json', false, true, [], $cstRequest
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
            if (isset($data['cross']->updated)
             && is_array($data['cross']->updated)) {
                $data['cross']->updated     = (object) $data['cross']->updated;
            }
        }
        if (isset($data['old_cross'])) {
            $data['old_cross']->exfee->invitations = $this->cleanInvitations(
                $data['old_cross']->exfee->invitations
            );
            if (isset($data['old_cross']->updated)
             && is_array($data['old_cross']->updated)) {
                $data['old_cross']->updated = (object) $data['old_cross']->updated;
            }
        }
        $strSrv = "{$service}/{$method}";
        switch ($strSrv) {
            case 'cross/invitation':
                $urlSrv = "/v3/notifier/{$strSrv}";
                $mergeK = '-';
                $dataAr = ['cross_id' => $data['cross']->id, 'by' => $data['by']];
                break;
            case 'cross/update':
                $urlSrv = "/v3/notifier/{$strSrv}";
                $mergeK = "cross{$data['cross']->id}";
                $dataAr = $data;
                break;
            case 'cross/remind':
                $urlSrv = "/v3/notifier/{$strSrv}";
                $mergeK = "cross{$data['cross']->id}";
                $dataAr = ['cross_id' => (int) $data['cross']->id];
                break;
            case 'cross/conversation':
                $urlSrv = "/v3/notifier/{$strSrv}";
                $mergeK = "cross{$data['cross']->id}";
                $dataAr = ['cross_id' => $data['cross']->id, 'post' => $data['post']];
                break;
            case 'Thirdpart/UpdateIdentity':
            case 'Thirdpart/UpdateFriends':
                $urlSrv = "/{$strSrv}";
                $mergeK = '-';
                $dataAr = $data;
                break;
            default:
                return true;
        }
        switch ($queue) {
            case 'Head2':
                $type   = 'once';
                $ontime = time() + 60 * 2;
                break;
            case 'Head10':
                $type   = 'once';
                $ontime = time() + 60 * 10;
                break;
            case 'Tail10':
                $type   = 'always';
                $ontime = time() + 60 * 10;
                break;
            case 'Instant':
                $type   = 'once';
                $ontime = time();
                break;
            case 'Remind':
                $type   = 'once';
                $this->fireBus(
                    $tos, $mergeK, 'POST', EXFE_AUTH_SERVER . $urlSrv,
                    $type, 0, $dataAr, 'DELETE'
                );
                $ontime = $this->getRemindTimeBy($data['cross']->time);
                if (!$ontime) {
                    return true;
                }
                break;
            case 'Digest':
                $type   = 'always';
                $rmTime = $this->getRemindTimeBy($data['cross']->time);
                $ontime = $this->getDigestTimeBy($data['cross']->time);
                // 明天发生的活动，撤回 remind 通知 {
                if ($rmTime === $ontime) {
                    $this->fireBus(
                        $tos, $mergeK, 'POST', EXFE_AUTH_SERVER . $urlSrv,
                        'once', 0, ['cross_id' => (int) $data['cross']->id],
                        'DELETE'
                    );
                }
                // }
                $strSrv = 'cross/digest';
                $urlSrv = "/v3/notifier/{$strSrv}";
                $dataAr = [
                    'cross_id'   => $data['cross']->id,
                    'updated_at' => $data['cross']->updated_at,
                ];
        }
        return $this->fireBus(
            $tos, $mergeK, 'POST', EXFE_AUTH_SERVER . $urlSrv,
            $type, $ontime, $dataAr
        );
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
        $remind          = [];
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
                if ($event === 'cross/conversation') {
                    $hlpConversation->addConversationCounter(
                        $cross->exfee->id,
                        $invitation->identity->connected_user_id
                    );
                }
                // marked
                $chkUser[$invitation->identity->connected_user_id] = true;
            }
        }
        // match rules {
        foreach ($gotInvitation as $gI => $gItem) {
            if ($gItem->identity->connected_user_id > 0) {
                $provider = ($gItem->identity->provider === 'iOS' || $gItem->identity->provider === 'Android')
                          ? 'device' : $gItem->identity->provider;
                $userPerProvider[$provider][$gItem->identity->connected_user_id] = $gI;
            }
        }
        foreach ($chkUser as $cuI => $cuItem) {
            switch ($event) {
                case 'cross/update':
                    if ((isset($userPerProvider['device'][$cuI]) && isset($userPerProvider['email'][$cuI]))
                     || (isset($userPerProvider['phone'][$cuI])  && isset($userPerProvider['email'][$cuI])
                      && $gotInvitation[$userPerProvider['email'][$cuI]]->rsvp_status === 'NOTIFICATION')) {
                        $digest[] = $gotInvitation[$userPerProvider['email'][$cuI]];
                        $imsgInv  = deepClone($gotInvitation[$userPerProvider['email'][$cuI]]);
                        $imsgInv->identity->provider = 'imessage';
                        $digest[] = $imsgInv;
                        unset($gotInvitation[$userPerProvider['email'][$cuI]]);
                    }
                    break;
                case 'cross/conversation':
                    if (isset($userPerProvider['device'][$cuI]) && isset($userPerProvider['email'][$cuI])) {
                        unset($gotInvitation[$userPerProvider['email'][$cuI]]);
                    }
            }
        }
        // }
        switch ($event) {
            case 'cross/conversation':
                foreach ($gotInvitation as $item) {
                    switch ($item->identity->provider) {
                        case 'email':
                        case 'google':
                        case 'facebook':
                            $head10[]  = $item;
                            break;
                        case 'phone':
                            $item->identity->provider = 'imessage|phone';
                        case 'twitter':
                        case 'iOS':
                        case 'Android':
                            $instant[] = $item;
                    }
                }
                break;
            case 'cross/invitation':
                foreach ($gotInvitation as $item) {
                    switch ($item->identity->provider) {
                        case 'email':
                        case 'google':
                        case 'phone':
                            $imsgInv = deepClone($item);
                            $imsgInv->identity->provider = 'imessage';
                            $instant[] = $imsgInv;
                        case 'twitter':
                        case 'facebook':
                        case 'iOS':
                        case 'Android':
                            $instant[] = $item;
                    }
                }
                break;
            case 'cross/remind':
                foreach ($gotInvitation as $item) {
                    switch ($item->identity->provider) {
                        case 'email':
                        case 'google':
                        case 'phone':
                            $imsgInv = deepClone($item);
                            $imsgInv->identity->provider = 'imessage';
                            $remind[]  = $imsgInv;
                        case 'twitter':
                        case 'facebook':
                        case 'iOS':
                        case 'Android':
                            $remind[]  = $item;
                    }
                }
                break;
            case 'cross/update':
                foreach ($gotInvitation as $item) {
                    switch ($item->identity->provider) {
                        case 'email':
                        case 'google':
                        case 'twitter':
                        case 'facebook':
                            $tail10[]  = $item;
                            break;
                        case 'phone':
                            $item->identity->provider = 'imessage';
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
            'Remind'  => $remind,
        ];
    }


    public function getRemindTimeBy($crossTime) {
        if ($crossTime
         && $crossTime->begin_at
         && $crossTime->begin_at->date
         && $crossTime->begin_at->timezone) {
            $time = strtotime(
                "{$crossTime->begin_at->date} {$crossTime->begin_at->timezone}"
            ) + 60 * 60 * 6; // at 6pm
            if ($time >= time()) {
                return $time;
            }
        }
        return null;
    }


    public function getDigestTimeBy($crossTime) {
        if ($crossTime
         && $crossTime->begin_at
         && $crossTime->begin_at->timezone) {
            $hlpTime = $this->getHelperByName('Time');
            $timezoneName = $hlpTime->getTimezoneNameByRaw(
                $crossTime->begin_at->timezone
            );
            @date_default_timezone_set($timezoneName);
        }
        $time = strtotime('tomorrow') + 60 * 60 * 6; // at 6pm;
        @date_default_timezone_set('UTC');
        return $time;
    }


    public function despatchConversation(
        $cross,
        $post,
        $by_user_id,
        $by_identity_id,
        $exclude_identities = []
    ) {
        $service     = 'cross';
        $method      = 'conversation';
        $hlpIdentity = $this->getHelperByName('Identity');
        $objIdentity = $hlpIdentity->getIdentityById($by_identity_id);
        $invitations = $this->getToInvitationsByExfee(
            $cross, $by_user_id, "{$service}/{$method}", [], $exclude_identities
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
        $service     = 'cross';
        $method      = 'invitation';
        $hlpIdentity = $this->getHelperByName('Identity');
        $objIdentity = $hlpIdentity->getIdentityById($by_identity_id);
        $dpCross     = new stdClass;
        $dpCross->id = $cross->id;
        $dpCross->exfee = $to_exfee;
        $invitations = $this->getToInvitationsByExfee(
            $dpCross, $by_user_id, "{$service}/{$method}"
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
        $this->despatchRemind($cross, $to_exfee, $by_user_id, $by_identity_id);
        return $result;
    }


    public function despatchRemind($cross, $to_exfee, $by_user_id, $by_identity_id) {
        $service     = 'cross';
        $method      = 'remind';
        $hlpIdentity = $this->getHelperByName('Identity');
        $objIdentity = $hlpIdentity->getIdentityById($by_identity_id);
        $dpCross     = new stdClass;
        $dpCross->id = $cross->id;
        $dpCross->exfee = $to_exfee;
        $invitations = $this->getToInvitationsByExfee(
            $dpCross, $by_user_id, "{$service}/{$method}"
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


    public function despatchUpdate($cross, $old_cross, $inc_exfee, $exc_exfee, $by_user_id, $by_identity_id) {
        $service     = 'cross';
        $method      = 'update';
        $hlpIdentity = $this->getHelperByName('Identity');
        $objIdentity = $hlpIdentity->getIdentityById($by_identity_id);
        $invitations = $this->getToInvitationsByExfee(
            $cross, $by_user_id, "{$service}/{$method}", $inc_exfee, $exc_exfee
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
