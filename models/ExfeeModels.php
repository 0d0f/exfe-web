<?php

class ExfeeModels extends DataModel {

    protected $rsvp_status = array('NORESPONSE', 'ACCEPTED', 'INTERESTED', 'DECLINED', 'REMOVED', 'NOTIFICATION', 'IGNORED');


    protected function makeExfeeToken() {
        return md5(base64_encode(pack('N6', mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand(), uniqid())));
    }


    protected function getIndexOfRsvpStatus($rsvp_status) {
        return intval(array_search(strtoupper($rsvp_status), $this->rsvp_status));
    }


    public function getRawExfeeById($id, $withRemoved = false) {
        $strSql = "SELECT * FROM `invitations` WHERE `exfee_id` = {$id}";
        if ($withRemoved) {
            $rawExfee = $this->getAll($strSql);
        } else {
            $key = "exfee:{$id}";
            if (!($rawExfee = getCache($key))) {
                $rawExfee = $this->getAll("{$strSql} AND `state` <> 4");
                setCache($key, $rawExfee);
            }
        }
        return $rawExfee;
    }


    public function getExfeeById($id, $withRemoved = false, $withToken = false) {
        // init
        $hlpIdentity = $this->getHelperByName('identity');
        $weights     = [
            'ACCEPTED'     => 0,
            'INTERESTED'   => 1,
            'DECLINED'     => 2,
            'IGNORED'      => 3,
            'NORESPONSE'   => 4,
            'NOTIFICATION' => 5,
            'REMOVED'      => 6
        ];
        // get raw exfee
        $rawExfee = $this->getRawExfeeById($id, $withRemoved);
        if (!$rawExfee) {
            return null;
        }
        $objExfee = new Exfee($id);
        $objExfee->hosts      = [];
        $objExfee->updated_at = "{$rawExfee[0]['exfee_updated_at']} +0000";
        // sort invitations with connected user
        $users    = [];
        $chkedIdt = [];
        foreach ($rawExfee as $ei => $eItem) {
            $rawExfee[$ei]['identity']     = $hlpIdentity->getIdentityById($eItem['identity_id']);
            $rawExfee[$ei]['identity_inv'] = $hlpIdentity->getIdentityById($eItem['invited_by']);
            $rawExfee[$ei]['identity_upd'] = $hlpIdentity->getIdentityById($eItem['by_identity_id']);
            $rawExfee[$ei]['rsvp']         = $this->rsvp_status[$eItem['state']];
            $rawExfee[$ei]['rsvp_weight']  = $weights[$rawExfee[$ei]['rsvp']];
            if (isset($chkedIdt[$eItem['identity_id']])
             || !$rawExfee[$ei]['identity']
             || !$rawExfee[$ei]['identity_upd']
             || (!$withRemoved && in_array((int) $eItem['identity_id'], explode(',', SMITH_BOT)))) {
                unset($rawExfee[$ei]);
                continue;
            }
            if ($eItem['host']) {
                $objExfee->hosts[] = $rawExfee[$ei]['identity']->connected_user_id;
            }
            $chkedIdt[$eItem['identity_id']] = true;
            if (($user_id = $rawExfee[$ei]['identity']->connected_user_id) > 0) {
                if (!isset($users[$user_id])) {
                    $users[$user_id] = [
                        'grouping'    => [],
                        'invitations' => [],
                        'top_rsvp_id' => 0,
                        'rsvp_weight' => sizeof($weights),
                    ];
                }
                if ($eItem['grouping']) {
                    $users[$user_id]['grouping'][] = $eItem['grouping'];
                }
                $users[$user_id]['invitations'][] = $ei;
                if ($rawExfee[$ei]['rsvp_weight'] < $users[$user_id]['rsvp_weight']) {
                    $users[$user_id]['top_rsvp_id'] = $ei;
                    $users[$user_id]['rsvp_weight'] = $rawExfee[$ei]['rsvp_weight'];
                }
            }
        }
        // sort grouped invitations without connected user
        $groups = [];
        foreach ($rawExfee as $ei => $eItem) {
            if ($eItem['identity']->connected_user_id > 0 || !$eItem['grouping']) {
                continue;
            }
            $found = false;
            foreach ($users as $uI => $uItem) {
                if (in_array($eItem['grouping'], $uItem['grouping'])) {
                    $users[$uI]['invitations'][] = $ei;
                    if ($eItem['rsvp_weight'] < $users[$uI]['rsvp_weight']) {
                        $users[$uI]['top_rsvp_id'] = $ei;
                        $users[$uI]['rsvp_weight'] = $eItem['rsvp_weight'];
                    }
                    $found = true;
                    continue;
                }
            }
            if (!$found) {
                if (!isset($groups[$eItem['grouping']])) {
                    $groups[$eItem['grouping']] = [
                        'invitations' => [],
                        'top_rsvp_id' => 0,
                        'rsvp_weight' => sizeof($weights),
                    ];
                }
                $groups[$eItem['grouping']]['invitations'][] = $ei;
                if ($eItem['rsvp_weight'] < $groups[$eItem['grouping']]['rsvp_weight']) {
                    $groups[$eItem['grouping']]['top_rsvp_id'] = $ei;
                    $groups[$eItem['grouping']]['rsvp_weight'] = $eItem['rsvp_weight'];
                }
            }
        }
        // add invitations with connetced user or grouped into exfee
        foreach (array_merge($users, $groups) as $uItem) {
            $rsvp = $rawExfee[$uItem['top_rsvp_id']]['rsvp'] !== 'NOTIFICATION'
                  ? $rawExfee[$uItem['top_rsvp_id']]['rsvp']  :  'NORESPONSE';
            $notificationIds = [];
            foreach ($uItem['invitations'] as $invItem) {
                if ($invItem !== $uItem['top_rsvp_id']
                 && $rawExfee[$invItem]->rsvp !== 'REMOVED') {
                    $identity = $rawExfee[$invItem]['identity'];
                    $notificationIds[] = "{$identity->external_username}@{$identity->provider}";
                }
            }
            $objInvitation = new Invitation(
                $rawExfee[$uItem['top_rsvp_id']]['id'],
                $rawExfee[$uItem['top_rsvp_id']]['identity'],
                $rawExfee[$uItem['top_rsvp_id']]['identity_inv'],
                $rawExfee[$uItem['top_rsvp_id']]['identity_upd'],
                $rsvp,
                $rawExfee[$uItem['top_rsvp_id']]['via'],
                $withToken ? $rawExfee[$uItem['top_rsvp_id']]['token'] : '',
                $rawExfee[$uItem['top_rsvp_id']]['created_at'],
                $rawExfee[$uItem['top_rsvp_id']]['updated_at'],
                $rawExfee[$uItem['top_rsvp_id']]['host'],
                $rawExfee[$uItem['top_rsvp_id']]['mates'],
                $rawExfee[$uItem['top_rsvp_id']]['remark']
              ? explode(';', $rawExfee[$uItem['top_rsvp_id']]['remark']) : [],
                $notificationIds
            );
            if ($withToken) {
                $objInvitation->token_used_at = $rawExfee[$uItem['top_rsvp_id']]['token_used_at'];
            }
            $objExfee->invitations[] = $objInvitation;
        }
        // add other invitations into exfee
        foreach ($rawExfee as $eItem) {
            if ($eItem['identity']->connected_user_id > 0 || $eItem['grouping']) {
                continue;
            }
            $objInvitation = new Invitation(
                $eItem['id'],
                $eItem['identity'],
                $eItem['identity_inv'],
                $eItem['identity_upd'],
                $eItem['rsvp'] === 'NOTIFICATION' ? 'NORESPONSE' : $eItem['rsvp'],
                $eItem['via'],
                $withToken ? $eItem['token'] : '',
                $eItem['created_at'],
                $eItem['updated_at'],
                $eItem['host'],
                $eItem['mates'],
                $eItem['remark'] ? explode(';', $eItem['remark']) : []
            );
            if ($withToken) {
                $objInvitation->token_used_at = $eItem['token_used_at'];
            }
            $objExfee->invitations[] = $objInvitation;
        }
        // get exfee name
        $ifoExfee = $this->getRow("SELECT * FROM `exfees` WHERE `id` = {$id}");
        $objExfee->name = $ifoExfee && $ifoExfee['name'] ? $ifoExfee['name'] : '';
        if (!$objExfee->name && ($cross_id = $this->getCrossIdByExfeeId($id))) {
            $hlpCross = $this->getHelperByName('Cross');
            $rawCross = $hlpCross->getRawCrossById($cross_id);
            $objExfee->name = "{$rawCross['title']}";
        }
        // return
        $objExfee->summary();
        return $objExfee;
    }


    public function checkInvitationTokenTime($rawInvitation) {
        return in_array($rawInvitation['identity_id'], explode(',', SMITH_BOT))
            || (($rawInvitation['token_used_at'] === '0000-00-00 00:00:00'
              || time() - strtotime($rawInvitation['token_used_at']) < 233)
             && (time() - strtotime($rawInvitation['created_at']))   < (60 * 60 * 24 * 7)); // 7 days
    }


    public function getRawInvitationByToken($token) {
        $token = dbescape($token);
        if ($token) {
            $rawInvitation = $this->getRow(
                "SELECT * FROM `invitations` WHERE `token` = '{$token}'"
            );
            if ($rawInvitation) {
                $rawInvitation['id']             = (int) $rawInvitation['id'];
                $rawInvitation['identity_id']    = (int) $rawInvitation['identity_id'];
                $rawInvitation['state']          = (int) $rawInvitation['state'];
                $rawInvitation['by_identity_id'] = (int) $rawInvitation['by_identity_id'];
                $rawInvitation['host']           = (int) $rawInvitation['host'];
                $rawInvitation['mates']          = (int) $rawInvitation['mates'];
                $rawInvitation['exfee_id']       = (int) $rawInvitation['exfee_id'];
                $rawInvitation['cross_id']       = $this->getCrossIdByExfeeId($rawInvitation['exfee_id']);
                $rawInvitation['valid']          = $rawInvitation['state'] !== 4
                                                && $this->checkInvitationTokenTime($rawInvitation);
                return $rawInvitation;
            }
        }
        return null;
    }


    public function getRawInvitationByExfeeIdAndIdentityId($exfee_id, $identity_id, $cross_id = 0) {
        if ($exfee_id && $identity_id) {
            $rawInvitation = $this->getRow(
                "SELECT * FROM `invitations` WHERE `exfee_id` = {$exfee_id}
                 AND `identity_id` = {$identity_id} AND `state` <> 4"
            );
            if ($rawInvitation) {
                $rawInvitation['id']             = (int) $rawInvitation['id'];
                $rawInvitation['identity_id']    = (int) $rawInvitation['identity_id'];
                $rawInvitation['state']          = (int) $rawInvitation['state'];
                $rawInvitation['by_identity_id'] = (int) $rawInvitation['by_identity_id'];
                $rawInvitation['host']           = (int) $rawInvitation['host'];
                $rawInvitation['mates']          = (int) $rawInvitation['mates'];
                $rawInvitation['exfee_id']       = (int) $rawInvitation['exfee_id'];
                $rawInvitation['cross_id']       = (int) $cross_id ?: $this->getCrossIdByExfeeId($rawInvitation['exfee_id']);
                $rawInvitation['valid']          = true;
                $rawInvitation['raw_valid']      = $this->checkInvitationTokenTime($rawInvitation);
                return $rawInvitation;
            }
        }
        return null;
    }


    public function getRawInvitationByCrossIdAndIdentityId($cross_id, $identity_id) {
        $exfee_id = $this->getExfeeIdByCrossId($cross_id);
        return $this->getRawInvitationByExfeeIdAndIdentityId($exfee_id, $identity_id, $cross_id);
    }


    public function getRawInvitationByExfeeIdAndToken($exfee_id, $token) {
        if ($exfee_id && $token) {
            switch (strlen($token)) {
                case 3:
                    $token = "_{$token}____________________________";
                    break;
                case 4:
                    $token = "_{$token}___________________________";
                    break;
                default:
                    return null;
            }
            $rawInvitation = $this->getRow(
                "SELECT * FROM `invitations`
                 WHERE `exfee_id` =  {$exfee_id}
                 AND   `token` LIKE '{$token}'"
            );
            if ($rawInvitation) {
                $rawInvitation['id']             = (int) $rawInvitation['id'];
                $rawInvitation['identity_id']    = (int) $rawInvitation['identity_id'];
                $rawInvitation['state']          = (int) $rawInvitation['state'];
                $rawInvitation['invited_by']     = (int) $rawInvitation['invited_by'];
                $rawInvitation['by_identity_id'] = (int) $rawInvitation['by_identity_id'];
                $rawInvitation['host']           = (int) $rawInvitation['host'];
                $rawInvitation['mates']          = (int) $rawInvitation['mates'];
                $rawInvitation['exfee_id']       = (int) $rawInvitation['exfee_id'];
                $rawInvitation['cross_id']       = $this->getCrossIdByExfeeId($rawInvitation['exfee_id']);
                $rawInvitation['valid']          = $rawInvitation['state'] !== 4
                                                && ($rawInvitation['token_used_at'] === '0000-00-00 00:00:00'
                                                 || time() - strtotime($rawInvitation['token_used_at']) < 233);
                return $rawInvitation;
            }
        }
        return null;
    }


    public function getInvitationByExfeeIdAndToken($exfee_id, $token) {
        $rawInvitation = $this->getRawInvitationByExfeeIdAndToken($exfee_id, $token);
        if ($rawInvitation) {
            $hlpIdentity = $this->getHelperByName('identity');
            $objIdentity = $hlpIdentity->getIdentityById($rawInvitation['identity_id']);
            $oivIdentity = $hlpIdentity->getIdentityById($rawInvitation['invited_by']);
            $oByIdentity = $hlpIdentity->getIdentityById($rawInvitation['by_identity_id']);
            return new Invitation(
                $rawInvitation['id'],
                $objIdentity,
                $oivIdentity,
                $oByIdentity,
                $this->rsvp_status[$rawInvitation['state']],
                $rawInvitation['via'],
                '',
                $rawInvitation['created_at'],
                $rawInvitation['updated_at'],
                $rawInvitation['host'],
                $rawInvitation['mates'],
                $rawInvitation['remark'] ? explode(';', $rawInvitation['remark']) : []
            );
        }
        return null;
    }


    public function usedToken($token) {
        return $token && $this->query(
            "UPDATE `invitations`
             SET    `token_used_at` = NOW()
             WHERE  `token_used_at` = 0
             AND    `token`         = '{$token}'"
        );
    }


    public function getIdentityIdsByExfeeId($exfee_id) {
        $identity_ids = [];
        $sql          = "SELECT `identity_id` FROM `invitations`
                         WHERE  `exfee_id` = {$exfee_id} AND `state` <> 4";
        $rawExfee     = $this->getAll($sql);
        foreach ($rawExfee ?: [] as $eItem) {
            $identity_ids[] = (int) $eItem['identity_id'];
        }
        return $identity_ids;
    }


    public function getUserIdsByExfeeId($exfee_id, $notConnected = false) {
        $hlpUser = $this->getHelperByName('User');
        return $hlpUser->getUserIdsByIdentityIds(
            $this->getIdentityIdsByExfeeId($exfee_id), $notConnected
        );
    }


    public function addInvitationIntoExfee($invitation, $exfee_id, $by_identity_id, $user_id = 0, $locale = '', $timezone = '') {
        // init
        $hlpIdentity = $this->getHelperByName('identity');
        // adding new identity
        if (!$hlpIdentity->checkIdentityById($invitation->identity->id)) {
            $invitation->identity->id = $hlpIdentity->addIdentity([
                'provider'          => $invitation->identity->provider,
                'external_id'       => $invitation->identity->external_id,
                'name'              => $invitation->identity->name,
                'external_username' => $invitation->identity->external_username,
                'avatar'            => $invitation->identity->avatar,
                'avatar_filename'   => $invitation->identity->avatar_filename,
                'locale'            => $locale,
                'timezone'          => $timezone,
            ]);
        }
        if (!$invitation->identity->id) {
            return null;
        }
        // make invitation token
        $invToken    = $this->makeExfeeToken();
        // translate rsvp status
        $rsvp_status = $this->getIndexOfRsvpStatus($invitation->response);
        // get host boolean
        $host        = intval($invitation->host);
        // get mates tinyint
        $mates       = intval($invitation->mates);
        // insert invitation into database
        $sql = "INSERT INTO `invitations` SET
                `identity_id`      =  {$invitation->identity->id},
                `exfee_id`         =  {$exfee_id},
                `state`            = '{$rsvp_status}',
                `created_at`       = NOW(),
                `updated_at`       = NOW(),
                `exfee_updated_at` = NOW(),
                `token`            = '{$invToken}',
                `invited_by`       =  {$by_identity_id},
                `by_identity_id`   =  {$by_identity_id},
                `host`             =  {$host},
                `mates`            =  {$mates}" . (@$invitation->grouping ? ",
                `grouping`         =  {$invitation->grouping}" : '');
        $dbResult = $this->query($sql);
        // save relations and update request access
        $hlpRequest = $this->getHelperByName('Request');
        $hlpRequest->changeStatus(
            0, $invitation->identity->id, $exfee_id, 1, $by_identity_id
        );
        if ($user_id) {
            $hlpRelation = $this->getHelperByName('Relation');
            $hlpRelation->saveRelations($user_id, $invitation->identity->id);
            $hlpUser     = $this->getHelperByName('User');
            $rv_user_id  = $hlpUser->getUserIdByIdentityId(
                $invitation->identity->id
            );
            if ($rv_user_id) {
                $identityIds = $hlpUser->getIdentityIdsByUserId($rv_user_id);
                foreach ($identityIds as $id) {
                    $hlpRequest->changeStatus(
                        0, $id, $exfee_id, 1, $by_identity_id
                    );
                }
                $hlpRelation->saveRelations($rv_user_id, $by_identity_id);
            }
        }
        // return
        return intval($dbResult['insert_id']);
    }


    public function updateInvitation($invitation, $by_identity_id, $updateToken = false) {
        // base check
        if (!$invitation->id || !$invitation->identity->id || !$by_identity_id) {
            return null;
        }
        // make invitation token
        $sqlToken = $updateToken ? (
            ", `token`         = '" . $this->makeExfeeToken() . "'"
          . ', `created_at`    = NOW()'
          . ', `token_used_at` = 0'
        ) : '';
        // translate rsvp status
        $rsvp_status = $this->getIndexOfRsvpStatus($invitation->response);
        // get host boolean
        $host        = intval($invitation->host);
        // get mates tinyint
        $mates       = intval($invitation->mates);
        // update database
        return $this->query(
            "UPDATE `invitations` SET
             `state`            = {$rsvp_status},
             `updated_at`       = NOW(),
             `exfee_updated_at` = NOW(),
             `by_identity_id`   = {$by_identity_id},
             `host`             = {$host},
             `mates`            = {$mates}{$sqlToken}
             WHERE `id`         = {$invitation->id}"
        );
    }


    public function updateInvitationRemarkById($id, $remark) {
        $remark = strtoupper(implode(';', $remark));
        return $this->query(
            "UPDATE `invitations` SET `remark` = '{$remark}' WHERE `id` = $id"
        );
    }


    public function updateRsvpByExfeeId($exfee_id, $rsvp) {
        // base check
        $identity_id    = (int)$rsvp->identity_id;
        $rsvp_status    = $this->getIndexOfRsvpStatus($rsvp->response);
        $by_identity_id = (int)$rsvp->by_identity_id;
        // update database
        if ($identity_id && $rsvp_status && $by_identity_id) {
            if (intval($this->query(
                "UPDATE `invitations` SET
                 `state`            = {$rsvp_status},
                 `updated_at`       = NOW(),
                 `exfee_updated_at` = NOW(),
                 `by_identity_id`   = {$by_identity_id}
                 WHERE `exfee_id`   = {$exfee_id}
                 AND `identity_id`  = {$identity_id}"
            ))) {
                return array(
                    'identity_id'    => $identity_id,
                    'rsvp_status'    => $this->rsvp_status[$rsvp_status],
                    'by_identity_id' => $by_identity_id,
                    'type'           => 'rsvp',
                );
            }
        }
        return false;
    }


    public function removeNotificationIdentity($exfee_id, $identity_id, $by_identity_id) {
        $rsvp = new stdClass;
        $rsvp->identity_id    = $identity_id;
        $rsvp->by_identity_id = $by_identity_id;
        $rsvp->response       = 'REMOVED';
        $result = !!$this->updateRsvpByExfeeId($exfee_id, $rsvp);
        $this->updateExfeeTime($exfee_id);
        return $result;
    }


    public function getNewExfeeId() {
        $dbResult = $this->query("INSERT INTO `exfees` SET `id` = 0");
        $exfee_id = intval($dbResult['insert_id']);
        return $exfee_id;
    }


    public function addExfee($exfee_id, $invitations, $by_identity_id, $user_id = 0, $draft = false, $locale = '', $timezone = '') {
        // basic check
        if (!is_array($invitations) || !$by_identity_id) {
            return null;
        }
        // init
        $items  = 0;
        $added  = [];
        $hQuota = false;
        if (!$locale || !$timezone) {
            foreach ($invitations as $iItem) {
                if (!$locale   && @$iItem->identity->locale) {
                    $locale   = $iItem->identity->locale;
                }
                if (!$timezone && @$iItem->identity->timezone) {
                    $timezone = $iItem->identity->timezone;
                }
            }
        }
        // add invitations
        foreach ($invitations as $iI => $iItem) {
            $rawId = @$iItem->identity->external_username ?: "_{$iItem->identity->id}_";
            $id    = @strtolower("{$rawId}@{$iItem->identity->provider}");
            if (isset($iItem->rsvp_status)) { // @todo for v2 to v3 only
                $iItem->response = $iItem->rsvp_status;
            }
            if (!$id || isset($added[$id])
            || ($iItem->response && !in_array($iItem->response, [
                'NORESPONSE', 'ACCEPTED', 'INTERESTED', 'DECLINED'
            ]))) {
                unset($invitations[$iI]);
                continue;
            }
            $added[$id] = true;
            if (++$items > EXFEE_QUOTA_HARD_LIMIT) {
                $hQuota = true;
                unset($invitations[$iI]);
                continue;
            }
            $iItem->grouping = $iI;
            $this->addInvitationIntoExfee($iItem, $exfee_id, $by_identity_id, $user_id, $locale, $timezone);
        }
        // add notifications
        $strRegExp = '/(.*)@([^@]+)/';
        foreach ($invitations as $iI => $iItem) {
            foreach (@$iItem->notification_identities ?: [] as $tarId) {
                $tarId    = strtolower($tarId);
                $identity = new stdClass;
                $identity->external_username = preg_replace($strRegExp, '$1', $tarId);
                $identity->provider          = preg_replace($strRegExp, '$2', $tarId);
                if (isset($added[$tarId])
                 || !$identity->external_username
                 || !$identity->provider) {
                    continue;
                }
                $iItem = deepClone($iItem);
                $iItem->grouping = $iI;
                $iItem->identity = $identity;
                $iItem->response = 'NOTIFICATION';
                $this->addInvitationIntoExfee($iItem, $exfee_id, $by_identity_id, $user_id, $locale, $timezone);
            }
        }
        // updated exfee time
        $this->updateExfeeTime($exfee_id);
        // call Gobus {
        $hlpCross = $this->getHelperByName('cross');
        $hlpQueue = $this->getHelperByName('Queue');
        $cross_id = $this->getCrossIdByExfeeId($exfee_id);
        $cross    = $hlpCross->getCross($cross_id, true, true);
        $hlpQueue->despatchInvitation($cross, $cross->exfee, $user_id ?: -$by_identity_id, $by_identity_id, $draft);
        // }
        // return
        return [
            'exfee_id'   => $exfee_id,
            'soft_quota' => sizeof($invitations) > EXFEE_QUOTA_SOFT_LIMIT,
            'hard_quota' => $hQuota,
        ];
    }


    public function updateExfee($exfee, $by_identity_id, $user_id = 0, $rsvp_only = false, $draft = false, $keepRsvp = false, $timezone = '') {
        // load helpers
        $hlpCross    = $this->getHelperByName('cross');
        $hlpIdentity = $this->getHelperByName('identity');
        $hlpRequest  = $this->getHelperByName('Request');
        $hlpUser     = $this->getHelperByName('User');
        // basic check
        if (!$exfee || !$exfee->id || !$by_identity_id) {
            return null;
        }
        // update name
        $changed     = false;
        if (isset($exfee->name)) {
            $exfee->name = formatTitle($exfee->name, 233);
            $this->query(
                "UPDATE `exfees`
                 SET    `name` = '{$exfee->name}'
                 WHERE  `id`   =  {$exfee->id}"
            );
        }
        // init
        $cross_id   = $this->getCrossIdByExfeeId($exfee->id);
        $old_cross  = $hlpCross->getCross($cross_id, true, true);
        $soft_quota = false;
        $hard_quota = false;
        $newInvId   = [];
        $addExfee   = [];
        $delExfee   = [];
        $oldExfee   = $this->getRawExfeeById($exfee->id, true);
        $added      = [];
        $items      = $old_cross->exfee->items;
        $strRegExp  = '/(.*)@([^@]+)/';
        $locale     = '';
        //
        if (isset($exfee->invitations) && is_array($exfee->invitations) && $oldExfee) {
            // get current exfee infos
            $oldUserIds  = [];
            $oldNotifIds = [];
            foreach ($old_cross->exfee->invitations as $fmI => $fmItem) {
                if ($fmItem->response !== 'REMOVED') {
                    $oldUserIds[] = $fmItem->identity->connected_user_id;
                    $oldNotifIds[$fmItem->identity->id] = $fmItem->notification_identities;
                }
                if (!$timezone && $fmItem->identity->timezone) {
                    $timezone = $fmItem->identity->timezone;
                }
                if (!$locale   && $fmItem->identity->locale) {
                    $locale   = $fmItem->identity->locale;
                }
            }
            // complete invitations datas
            foreach ($exfee->invitations as $toI => $toItem) {
                // adding new identity
                if (!$hlpIdentity->checkIdentityById($toItem->identity->id)) {
                    $exfee->invitations[$toI]->identity->id = $hlpIdentity->addIdentity([
                        'provider'          => $toItem->identity->provider,
                        'external_id'       => $toItem->identity->external_id,
                        'name'              => $toItem->identity->name,
                        'external_username' => $toItem->identity->external_username,
                        'avatar'            => $toItem->identity->avatar,
                        'avatar_filename'   => $toItem->identity->avatar_filename,
                        'timezone'          => $timezone,
                        'locale'            => $locale,
                    ]);
                }
                // if no identity id or duplicate, skip it
                if (!$toItem->identity->id
                 || isset($added[$exfee->invitations[$toI]->identity->id])) {
                    unset($exfee->invitations[$toI]);
                    continue;
                }
                $added[$exfee->invitations[$toI]->identity->id] = true;
                // upgraded invitation object // @todo for v2 to v3 only
                if (isset($toItem->rsvp_status)) {
                    $exfee->invitations[$toI]->response = $toItem->rsvp_status;
                }
                // expending notification identities
                if (!$toItem->notification_identities
                 && isset($oldNotifIds[$toItem->identity->id])
                 && $toItem->response === 'REMOVED') {
                    $toItem->notification_identities = $oldNotifIds[$toItem->identity->id];
                }
                foreach ($toItem->notification_identities ?: [] as $ti => $tarId) {
                    $tarId = strtolower($tarId);
                    $external_username = preg_replace($strRegExp, '$1', $tarId);
                    $provider          = preg_replace($strRegExp, '$2', $tarId);
                    $idtId = $hlpIdentity->addIdentity([
                        'provider'          => $provider,
                        'external_username' => $external_username,
                        'external_id'       => '',
                        'name'              => '',
                        'avatar'            => null,
                        'avatar_filename'   => '',
                        'timezone'          => $timezone,
                        'locale'            => $locale,
                    ]);
                    if ($idtId && !isset($added[$idtId])) {
                        $added[$idtId]        = true;
                        $ntItem = deepClone($toItem);
                        $ntItem->identity->id = $idtId;
                        $ntItem->response     = $ntItem->response !== 'REMOVED'
                                              ? 'NOTIFICATION'     :  'REMOVED';
                        $ntItem->grouping_ref = $toI;
                        $exfee->invitations[] = $ntItem;
                    }
                }
            }
            // get current rsvp status
            $maxGrouping = 0;
            foreach ($oldExfee as $fmI => $fmItem) {
                $oldExfee[$fmI]['response'] = $this->rsvp_status[$fmItem['state']];
                $maxGrouping = $fmItem['grouping'] > $maxGrouping
                             ? $fmItem['grouping'] : $maxGrouping;
            }
            // update invitations
            foreach ($exfee->invitations as $toI => $toItem) {
                // find out the existing invitation
                $exists = false;
                foreach ($oldExfee as $fmItem) {
                    // update existing invitaion
                    if ((int) $toItem->identity->id === (int) $fmItem['identity_id']) {
                        $exists = true;
                        $toItem->id = $fmItem['id'];
                        $exfee->invitations[$toI]->grouping
                      = $toItem->grouping = $fmItem['grouping'] ?: ++$maxGrouping;
                        // 邮件编辑 cross 不删除人
                        if ($keepRsvp && !isset($toItem->response)) {
                            $toItem->response
                          = $fmItem['response'] !== 'REMOVED'
                          ? $fmItem['response']  :  'NORESPONSE';
                        }
                        // delete exfee
                        if ($fmItem['response'] !== 'REMOVED'
                         && $toItem->response   === 'REMOVED') {
                            if ($fmItem['host']) { // @todo: 将来需要检查是否是最后一个 host
                                $toItem->response = $fmItem['response'];
                            } else {
                                $delExfee[]       = $fmItem['id'];
                            }
                        }
                        // update exfee token
                        if ($fmItem['response'] === 'REMOVED'
                         && $toItem->response   !== 'REMOVED') {
                            $hlpRequest->changeStatus(
                                0, $toItem->identity->id, $exfee->id,
                                1, $by_identity_id
                            );
                            $rv_user_id = $hlpUser->getUserIdByIdentityId(
                                $toItem->identity->id
                            );
                            if ($rv_user_id) {
                                $identityIds = $hlpUser->getIdentityIdsByUserId($rv_user_id);
                                foreach ($identityIds as $id) {
                                    $hlpRequest->changeStatus(
                                        0, $id, $exfee_id, 1, $by_identity_id
                                    );
                                }
                            }
                            $newInvId[]  = $fmItem['id'];
                            $updateToken = true;
                        } else {
                            $updateToken = false;
                        }
                        if ($rsvp_only) {
                            if ($fmItem['response'] !== $toItem->response) {
                                $toItem->host  = (bool) $fmItem['host'];
                                $toItem->mates = (int)  $fmItem['mates'];
                                $this->updateInvitation($toItem, $by_identity_id, $updateToken);
                                $changed = true;
                            }
                        } else {
                            if ($fmItem['response'] !== $toItem->response
                             || (bool) $fmItem['host']  !== (bool) $toItem->host
                             || (int)  $fmItem['mates'] !== (int)  $toItem->mates) {
                                $this->updateInvitation($toItem, $by_identity_id, $updateToken);
                                $changed = true;
                            }
                        }
                    }
                }
                // add new invitation if it's a new invitation
                if (!$exists) {
                    if ($toItem->response && !in_array($toItem->response, [
                        'NORESPONSE', 'ACCEPTED', 'INTERESTED', 'DECLINED', 'NOTIFICATION'
                    ])) {
                        continue;
                    }
                    if ($toItem->response !== 'NOTIFICATION') {
                        $items++;
                        if ($items > EXFEE_QUOTA_SOFT_LIMIT) {
                            $soft_quota = true;
                        }
                        if ($items > EXFEE_QUOTA_HARD_LIMIT) {
                            $hard_quota = true;
                            unset($exfee->invitations[$toI]);
                            continue;
                        }
                    }
                    if (isset($toItem->grouping_ref)
                     && isset($exfee->invitations[$toItem->grouping_ref]->grouping)) {
                        $toItem->grouping = $exfee->invitations[$toItem->grouping_ref]->grouping;
                    }
                    $exfee->invitations[$toI]->grouping
                  = $toItem->grouping = @$toItem->grouping ?: ++$maxGrouping;
                    $newInvId[] = $this->addInvitationIntoExfee(
                        $toItem, $exfee->id, $by_identity_id, $user_id, $locale, $timezone
                    );
                    $changed = true;
                }
            }
        }
        $this->updateExfeeTime($exfee->id);
        // call Gobus {
        if (!$draft) {
            $hlpQueue = $this->getHelperByName('Queue');
            $cross    = $hlpCross->getCross($cross_id, true, true);
            foreach ($cross->exfee->invitations as $eI => $eItem) {
                if (in_array($eItem->id, $newInvId)
                 && !in_array($eItem->identity->connected_user_id, $oldUserIds)) {
                    $addExfee[] = $eItem;
                }
            }
            $inxExfee  = [];
            foreach ($delExfee as $deI => $deItem) {
                $found = false;
                foreach ($old_cross->exfee->invitations as $odItem) {
                    if ((int) $deItem === $odItem->id) {
                        $delExfee[$deI] = $odItem;
                        $found = true;
                    }
                    if (!$found) {
                        unset($delExfee[$deI]);
                    }
                }
            }
            if ($changed) {
                $hlpQueue->despatchUpdate(
                    $cross, $old_cross, $delExfee, $addExfee, $user_id ?: -$by_identity_id, $by_identity_id
                );
            }
            if ($addExfee) {
                $to_exfee = new stdClass;
                $to_exfee->id = $cross->exfee->id;
                $to_exfee->invitations = $addExfee;
                $hlpQueue->despatchInvitation(
                    $cross, $to_exfee, $user_id ?: -$by_identity_id, $by_identity_id
                );
            }
        }
        // }
        // return
        return [
            'exfee_id'   => $exfee->id,
            'soft_quota' => $soft_quota,
            'hard_quota' => $hard_quota,
            'changed'    => $changed,
        ];
    }


    public function updateExfeeRsvpById($exfee_id, $rsvps, $by_identity_id, $by_user_id, $draft = false) {
        // base check
        if (!$exfee_id || !is_array($rsvps) || !$by_identity_id) {
            return null;
        }
        // get old cross
        $hlpCross  = $this->getHelperByName('cross');
        $cross_id  = $this->getCrossIdByExfeeId($exfee_id);
        $old_cross = $hlpCross->getCross($cross_id, true, true);
        // raw actions
        $arrResult = [];
        $actResult = true;
        foreach ($rsvps as $rsvp) {
            // @todo v2 to v3 only {
            if (isset($rsvp->rsvp_status)) {
                $rsvp->response = $rsvp->rsvp_status;
            }
            // }
            $itm = $this->updateRsvpByExfeeId($exfee_id, $rsvp);
            if ($itm) {
                $arrResult[] = $itm;
            } else {
                $actResult = false;
            }
        }
        $this->updateExfeeTime($exfee_id);
        // call Gobus {
        if (!$draft) {
            $hlpQueue = $this->getHelperByName('Queue');
            $cross    = $hlpCross->getCross($cross_id, true, true);
            $hlpQueue->despatchUpdate(
                $cross, $old_cross, [], [], $by_user_id ?: -$by_identity_id, $by_identity_id
            );
        }
        // }
        // return
        return $actResult ? $arrResult : null;
    }


    public function getExfeeIdByUserid($userid, $updated_at = '') {
        $sql = "SELECT `identityid` FROM `user_identity` WHERE `userid` = {$userid} AND ( `status` = 3 OR `status` = 4 )";
        $identities = $this->getColumn($sql);
        if ($updated_at !== '') {
            $updated_sql="and exfee_updated_at>'$updated_at'";
        }
        $identities_list = implode($identities, ',');
        $sql = "SELECT DISTINCT `exfee_id` FROM `invitations` WHERE `identity_id` IN ($identities_list) AND `state` <> 4 {$updated_sql} ORDER BY `updated_at` DESC LIMIT 100";
        $exfee_id_list = $this->getColumn($sql);
        return $exfee_id_list;
    }


    public function updateExfeeTime($exfee_id, $quick = false) {
        $sql = "UPDATE `invitations` SET `exfee_updated_at` = NOW() WHERE `exfee_id` = {$exfee_id}";
        $this->query($sql);
        if (!$quick) {
            delCache("exfee:{$exfee_id}");
        }
    }


    public function getUpdatedExfeeByIdentityIds($identityids, $updated_at) {
        $join_identity_ids = implode($identityids, ',');
        $sql = "SELECT `exfee_id` FROM `invitations` WHERE `identity_id` IN ({$join_identity_ids}) AND `exfee_updated_at` > '$updated_at'";
        $cross_ids = $this->getColumn($sql);
        return $cross_ids;
    }


    public function getCrossIdByExfeeId($exfee_id) {
        $result = $this->getRow(
            "SELECT `id` FROM `crosses` WHERE `exfee_id` = {$exfee_id}"
        );
        return intval($result['id']);
    }


    public function getExfeeIdByCrossId($cross_id) {
        $result = $this->getRow(
            "SELECT `exfee_id` FROM `crosses` WHERE `id` = $cross_id"
        );
        return intval($result['exfee_id']);
    }


    public function getHostIdentityIdsByExfeeId($exfee_id) {
        $hosts   = [];
        $rawInvs = $this->getRawExfeeById($exfee_id);
        foreach (($rawInvs && is_array($rawInvs)) ? $rawInvs : [] as $rawInv) {
            if ($rawInv['state'] !== 4 && $rawInv['host']) {
                $hosts[] = $rawInv['identity_id'];
            }
        }
        return $hosts ?: null;
    }

}
