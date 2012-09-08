<?php

class ExfeeModels extends DataModel {

    protected $rsvp_status = array('NORESPONSE', 'ACCEPTED', 'INTERESTED', 'DECLINED', 'REMOVED', 'NOTIFICATION');


    protected function makeExfeeToken() {
        return md5(base64_encode(pack('N6', mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand(), uniqid())));
    }


    protected function getIndexOfRsvpStatus($rsvp_status) {
        return intval(array_search(strtoupper($rsvp_status), $this->rsvp_status));
    }


    public function getExfeeById($id, $withRemoved = false, $withToken = false) {
        // init
        $exfee_updated_at="";
        $hlpIdentity = $this->getHelperByName('identity');
        // get invitations
        $withRemoved = $withRemoved ? '' : 'AND `state` <> 4' ;
        $rawExfee = $this->getAll("SELECT * FROM `invitations` WHERE `cross_id` = {$id} {$withRemoved}");
        $objExfee = new Exfee($id);
        $exfee_updated_at = $rawExfee[0]['exfee_updated_at'];
        foreach ($rawExfee as $ei => $eItem) {
            $objIdentity   = $hlpIdentity->getIdentityById($eItem['identity_id']);
            $oByIdentity   = $hlpIdentity->getIdentityById($eItem['by_identity_id']);
            if (!$objIdentity || !$oByIdentity) {
                continue;
            }
            $objExfee->invitations[] = new Invitation(
                $eItem['id'],
                $objIdentity,
                $oByIdentity,
                $this->rsvp_status[$eItem['state']],
                $eItem['via'],
                $withToken ? $eItem['token'] : '',
                $eItem['created_at'],
                $eItem['updated_at'],
                $eItem['host'],
                $eItem['mates']
            );
        }
        $objExfee->updated_at = $exfee_updated_at;
        $objExfee->summary();
        // return
        return $objExfee;
    }


    public function getRawInvitationByToken($token) {
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
                $rawInvitation['exfee_id']       = (int) $rawInvitation['cross_id'];
                $rawInvitation['cross_id']       = $this->getCrossIdByExfeeId($rawInvitation['exfee_id']);
                $rawInvitation['valid']          = $rawInvitation['state'] !== 4
                                                && ($rawInvitation['token_used_at'] === '0000-00-00 00:00:00'
                                                 || time() - strtotime($rawInvitation['token_used_at']) < (60 * 23 + 30));
                return $rawInvitation;
            }
        }
        return null;
    }


    public function getInvitationByExfeeIdAndToken($exfee_id, $token) {
        if ($exfee_id && $token) {
            $rawInvitation = $this->getRow(
                "SELECT * FROM `invitations`
                 WHERE `cross_id` = $exfee_id
                 AND   `token` LIKE '_{$token}____________________________'"
            );
            if ($rawInvitation) {
                $hlpIdentity = $this->getHelperByName('identity');
                $objIdentity = $hlpIdentity->getIdentityById($rawInvitation['identity_id']);
                $oByIdentity = $hlpIdentity->getIdentityById($rawInvitation['by_identity_id']);
                return new Invitation(
                    $rawInvitation['id'],
                    $objIdentity,
                    $oByIdentity,
                    $this->rsvp_status[$rawInvitation['state']],
                    $rawInvitation['via'],
                    '',
                    $rawInvitation['created_at'],
                    $rawInvitation['updated_at'],
                    $rawInvitation['host'],
                    $rawInvitation['mates']
                );
            }
        }
        return null;
    }


    public function usedToken($token) {
        return $this->query(
            "UPDATE `invitations`
             SET    `token_used_at` = NOW()
             WHERE  `token` = '{$token}'"
        );
    }


    public function getIdentityIdsByExfeeId($exfee_id) {
        $identity_ids = [];
        $sql          = "SELECT `identity_id` FROM `invitations`
                         WHERE  `cross_id` = {$exfee_id} AND `state` <> 4";
        $rawExfee     = $this->getAll($sql);
        foreach ($rawExfee ?: [] as $eItem) {
            $identity_ids[] = $eItem['identity_id'];
        }
        return $identity_ids;
    }


    public function getUserIdsByExfeeId($exfee_id, $notConnected = false) {
        $hlpUser = $this->getHelperByName('User');
        return $hlpUser->getUserIdsByIdentityIds(
            $this->getIdentityIdsByExfeeId($exfee_id), $notConnected
        );
    }


    public function addInvitationIntoExfee($invitation, $exfee_id, $by_identity_id, $user_id = 0) {
        // init
        $hlpIdentity = $this->getHelperByName('identity');
        // adding new identity
        if (!$hlpIdentity->checkIdentityById($invitation->identity->id)) {
            $avatar_filename = $invitation->identity->avatar_filename;
            switch ($invitation->identity->provider) {
                case 'twitter':
                    $avatar_filename = preg_match('/\.twimg\.com\//', $avatar_filename)
                                     ? $avatar_filename : '';
            }
            $invitation->identity->id = $hlpIdentity->addIdentity(
                ['provider'          => $invitation->identity->provider,
                 'external_id'       => $invitation->identity->external_id,
                 'name'              => $invitation->identity->name,
                 'external_username' => $invitation->identity->external_username,
                 'avatar_filename'   => $avatar_filename]
            );
        }
        if (!$invitation->identity->id) {
            return null;
        }
        // make invitation token
        $invToken    = $this->makeExfeeToken();
        // translate rsvp status
        $rsvp_status = $this->getIndexOfRsvpStatus($invitation->rsvp_status);
        // get host boolean
        $host        = intval($invitation->host);
        // get mates tinyint
        $mates       = intval($invitation->mates);
        // insert invitation into database
        $sql = "INSERT INTO `invitations` SET
                `identity_id`      =  {$invitation->identity->id},
                `cross_id`         =  {$exfee_id},
                `state`            = '{$rsvp_status}',
                `created_at`       = NOW(),
                `updated_at`       = NOW(),
                `exfee_updated_at` = NOW(),
                `token`            = '{$invToken}',
                `by_identity_id`   =  {$by_identity_id},
                `host`             =  {$host},
                `mates`            =  {$mates}";
        $dbResult = $this->query($sql);
        // save relations
        if ($user_id) {
            $hlpRelation = $this->getHelperByName('Relation');
            $hlpRelation->saveRelations($user_id, $invitation->identity->id);
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
        $sqlToken = $updateToken
                  ? (", `token` = '" . $this->makeExfeeToken() . "', `token_used_at` = 0")
                  : '';
        // translate rsvp status
        $rsvp_status = $this->getIndexOfRsvpStatus($invitation->rsvp_status);
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


    public function updateRsvpByExfeeId($exfee_id, $rsvp) {
        // base check
        $identity_id    = (int)$rsvp->identity_id;
        $rsvp_status    = $this->getIndexOfRsvpStatus($rsvp->rsvp_status);
        $by_identity_id = (int)$rsvp->by_identity_id;
        // update database
        if ($identity_id && $rsvp_status && $by_identity_id) {
            if (intval($this->query(
                "UPDATE `invitations` SET
                 `state`            = {$rsvp_status},
                 `updated_at`       = NOW(),
                 `exfee_updated_at` = NOW(),
                 `by_identity_id`   = {$by_identity_id}
                 WHERE `cross_id`   = {$exfee_id}
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


    public function sendToGobus($exfee_id, $by_identity_id, $to_identities = null, $old_cross = null) {
        // get helpers
        $hlpCross  = $this->getHelperByName('cross');
        $hlpGobus  = $this->getHelperByName('gobus');
        $hlpDevice = $this->getHelperByName('device');
        // get cross
        $cross_id  = $this->getCrossIdByExfeeId($exfee_id);
        $cross     = $hlpCross->getCross($cross_id, true, true);
        $msgArg    = array('cross' => $cross, 'to_identities' => array());
        // get old cross
        if ($old_cross) {
            $msgArg['old_cross'] = $old_cross;
        }
        // raw action
        $chkMobUs  = array();
        foreach ($cross->exfee->invitations as $i => $invitation) {
            if ($invitation->identity->id === $by_identity_id) {
                $msgArg['by_identity'] = $invitation->identity;
            }
            if ($invitation->rsvp_status === 'REMOVED') {
                unset($cross->exfee->invitations[$i]);
            } else {
                $msgArg['to_identities'][] = $invitation->identity;
                // get mobile identities
                if ($invitation->identity->connected_user_id > 0
                && !$chkMobUs[$invitation->identity->connected_user_id]) {
                    $mobIdentities = $hlpDevice->getDevicesByUserid(
                        $invitation->identity->connected_user_id,
                        $invitation->identity
                    );
                    if ($mobIdentities) {
                        foreach ($mobIdentities as $mI => $mItem) {
                            $msgArg['to_identities'][] = $mItem;
                        }
                    }
                    $chkMobUs[$invitation->identity->connected_user_id] = true;
                }
            }
        }
        $cross->exfee->invitations = array_merge($cross->exfee->invitations);
        if ($to_identities) {
            foreach ($to_identities as $identity) {
                $msgArg['to_identities'][] = $identity;
                // get mobile identities
                if ($identity->connected_user_id
                && !$chkMobUs[$identity->connected_user_id]) {
                    $mobIdentities = $hlpDevice->getDevicesByUserid(
                        $identity->connected_user_id, $identity
                    );
                    foreach ($mobIdentities as $mI => $mItem) {
                        $msgArg['to_identities'][] = $mItem;
                    }
                    $chkMobUs[$identity->connected_user_id] = true;
                }
            }
        }
        if (DEBUG) {
            error_log(json_encode($msgArg));
        }
        $hlpGobus->send('cross', 'Update', $msgArg);
    }


    public function getNewExfeeId() {
        $dbResult = $this->query("INSERT INTO `exfees` SET `id` = 0");
        $exfee_id = intval($dbResult['insert_id']);
        return $exfee_id;
    }


    public function addExfee($exfee_id, $invitations, $by_identity_id, $user_id = 0) {
        // basic check
        if (!is_array($invitations) || !$by_identity_id) {
            return null;
        }
        // init
        $items      = 0;
        $over_quota = false;
        // add invitations
        foreach ($invitations as $iI => $iItem) {
            if ($iItem->rsvp_status !== 'REMOVED'
             && $iItem->rsvp_status !== 'NOTIFICATION') {
                if (++$items > EXFEE_QUOTA_SOFT_LIMIT) {
                    $over_quota = true;
                    continue;
                }
            }
            $this->addInvitationIntoExfee($iItem, $exfee_id, $by_identity_id, $user_id);
        }
        $this->updateExfeeTime($exfee_id);
        // call Gobus
        $this->sendToGobus($exfee_id, $by_identity_id);
        // return
        return ['exfee_id' => $exfee_id, 'over_quota' => $over_quota];
    }


    public function updateExfeeById($exfee_id, $invitations, $by_identity_id, $user_id = 0) {
        // get helper
        $hlpIdentity = $this->getHelperByName('identity');
        // base check
        if (!$exfee_id || !is_array($invitations) || !$by_identity_id) {
            return null;
        }
        // get old cross
        $hlpCross   = $this->getHelperByName('cross');
        $cross_id   = $this->getCrossIdByExfeeId($exfee_id);
        $old_cross  = $hlpCross->getCross($cross_id, true, true);
        $items      = $old_cross->exfee->items;
        $over_quota = false;
        $changed    = false;
        // raw actions
        $chkInvit = array();
        $delExfee = array();
        foreach ($invitations as $toI => $toItem) {
            // adding new identity
            if (!$hlpIdentity->checkIdentityById($toItem->identity->id)) {
                $avatar_filename = $toItem->identity->avatar_filename;
                switch ($toItem->identity->provider) {
                    case 'twitter':
                        $avatar_filename = preg_match('/\.twimg\.com\//', $avatar_filename)
                                         ? $avatar_filename : '';
                }
                $toItem->identity->id = $hlpIdentity->addIdentity(
                    ['provider'          => $toItem->identity->provider,
                     'external_id'       => $toItem->identity->external_id,
                     'name'              => $toItem->identity->name,
                     'external_username' => $toItem->identity->external_username,
                     'avatar_filename'   => $avatar_filename]
                );
            }
            // if no identity id, skip it
            if (!$toItem->identity->id) {
                continue;
            }
            // find out the existing invitation
            $exists = false;
            foreach ($old_cross->exfee->invitations as $fmI => $fmItem) {
                if (!$chkInvit[$fmI]) {
                    if ($toItem->identity->id === $fmItem->identity->id) {
                        $exists = true;
                        // update existing invitaion
                        $toItem->id = $fmItem->id;
                        // delete exfee
                        if ($this->getIndexOfRsvpStatus($fmItem->rsvp_status) !== 4
                         && $this->getIndexOfRsvpStatus($toItem->rsvp_status) === 4) {
                            $delExfee[]  = $fmItem->identity;
                        }
                        // update exfee token
                        if ($this->getIndexOfRsvpStatus($fmItem->rsvp_status) === 4
                         && $this->getIndexOfRsvpStatus($toItem->rsvp_status) !== 4) {
                            $updateToken = true;
                        } else {
                            $updateToken = false;
                        }
                        if ($fmItem->rsvp_status !== $toItem->rsvp_status
                         || $fmItem->host        !== $toItem->host
                         || $fmItem->mates       !== $toItem->mates) {
                            $this->updateInvitation($toItem, $by_identity_id, $updateToken);
                            $changed = true;
                        }
                        $chkInvit[$fmI] = true;
                    }
                }
            }
            // add new invitation if it's a new invitation
            if (!$exists) {
                if ($toItem->rsvp_status !== 'REMOVED'
                 && $toItem->rsvp_status !== 'NOTIFICATION') {
                    if (++$items > EXFEE_QUOTA_SOFT_LIMIT) {
                        $over_quota = true;
                        continue;
                    }
                }
                $this->addInvitationIntoExfee($toItem, $exfee_id, $by_identity_id, $user_id);
                $changed = true;
            }
        }
        $this->updateExfeeTime($exfee_id);
        // call Gobus
        $this->sendToGobus($exfee_id, $by_identity_id, $delExfee, $old_cross);
        // return
        return ['exfee_id' => $exfee_id, 'over_quota' => $over_quota, 'changed' => $changed];
    }


    public function updateExfeeRsvpById($exfee_id, $rsvps, $by_identity_id) {
        // base check
        if (!$exfee_id || !is_array($rsvps) || !$by_identity_id) {
            return null;
        }
        // get old cross
        $hlpCross  = $this->getHelperByName('cross');
        $cross_id  = $this->getCrossIdByExfeeId($exfee_id);
        $old_cross = $hlpCross->getCross($cross_id, true, true);
        // raw actions
        $arrResult = array();
        $actResult = true;
        foreach ($rsvps as $rsvp) {
            $itm = $this->updateRsvpByExfeeId($exfee_id, $rsvp);
            if ($itm) {
                $arrResult[] = $itm;
            } else {
                $actResult = false;
            }
        }
        $this->updateExfeeTime($exfee_id);
        // call Gobus
        $this->sendToGobus($exfee_id, $by_identity_id, $null, $old_cross);
        //
        return $actResult ? $arrResult : null;
    }


    public function getExfeeIdByUserid($userid,$updated_at="") {
        $sql="select identityid from user_identity where userid=$userid;";
        $identities=$this->getColumn($sql);
        if($updated_at!="")
            $updated_sql="and exfee_updated_at>'$updated_at'";

        $identities_list=implode($identities,",");
        $sql="select DISTINCT cross_id from invitations where identity_id in($identities_list) AND `state` <> 4 {$updated_sql} order by created_at limit 100;";
        //TODO: cross_id will be renamed to exfee_id
        $exfee_id_list=$this->getColumn($sql);
        return $exfee_id_list;
    }


    public function updateExfeeTime($exfee_id) {
        $sql="update invitations set exfee_updated_at=NOW() where `cross_id`=$exfee_id;";
        $this->query($sql);
    }


    public function getUpdatedExfeeByIdentityIds($identityids,$updated_at) {

        $join_identity_ids=implode($identityids,",");
        $sql="select cross_id from invitations where identity_id in ({$join_identity_ids}) and exfee_updated_at >'$updated_at'; ";
        $cross_ids=$this->getColumn($sql);
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

}
