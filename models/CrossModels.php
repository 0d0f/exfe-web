<?php

class CrossModels extends DataModel {

    public function getCrossesByExfeeids($exfee_id_list, $time_type = null, $time_split = null) {
        switch ($time_type) {
            case 'future':
                $filter = "AND c.`date` <> '' AND c.`begin_at` >= FROM_UNIXTIME({$time_split}) ORDER BY c.`begin_at` DESC";
                break;
            case 'past':
                $filter = "AND c.`date` <> '' AND c.`begin_at` <  FROM_UNIXTIME({$time_split}) ORDER BY c.`begin_at` DESC";
                break;
            case 'sometime':
                $filter = "AND c.`date` =  '' ORDER BY c.`created_at` DESC";
                break;
            default:
                $filter = ' ORDER BY c.`begin_at` DESC';
        }
        $exfee_ids = implode($exfee_id_list ?: [], ',');

        $sql = "select c.*,p.place_line1,p.place_line2,p.provider,p.external_id,p.lng,p.lat,p.created_at as place_created_at,p.updated_at as place_updated_at from crosses c left join places p on p.id=c.place_id where c.exfee_id in ({$exfee_ids}) {$filter}";
        $result = $this->getAll($sql);
        return $result;
    }


    public function getCross($crossid)
    {
        $sql="select * from crosses where id=$crossid;";
        $result=$this->getRow($sql);
        return $result;
    }


    public function addCross($cross, $place_id = 0, $exfee_id = 0, $by_identity_id = 0, $old_cross = null) {
        $setTime    = isset($cross->time);
        $cross_time = $cross->time ?: new CrossTime('', '', '', '', '', '', '');
        $widgets    = $cross->widget;
        $bgUpdate   = false;
        $background = '';
        if ($widgets) {
            foreach($widgets as $widget) {
                if ($widget->type === 'Background') {
                    $background = $widget->image;
                    $bgUpdate   = true;
                }
            }
        }
        $old_background = '';
        if ($old_cross && $old_cross->widget) {
            foreach ($old_cross->widget as $widget) {
                if ($widget->type === 'Background') {
                    $old_background = $widget->image;
                }
            }
        }

        $cross_time->outputformat        = (int) $cross_time->outputformat;
        $cross->title                    = dbescape(isset($cross->title)       ? $cross->title       : '');
        $cross->description              = dbescape(isset($cross->description) ? $cross->description : '');
        $cross_time->origin              = dbescape($cross_time->origin);
        $cross_time->begin_at->timezone  = dbescape($cross_time->begin_at->timezone);
        $cross_time->begin_at->date_word = dbescape($cross_time->begin_at->date_word);
        $cross_time->begin_at->time_word = dbescape($cross_time->begin_at->time_word);
        $cross_time->begin_at->date      = dbescape($cross_time->begin_at->date);
        $cross_time->begin_at->time      = dbescape($cross_time->begin_at->time);
        $background                      = dbescape($background);

        $begin_at_time_in_old_format     = $cross_time->begin_at->date . (
            $cross_time->begin_at->date ? " {$cross_time->begin_at->time}" : ''
        );

        $status = ['draft' => 0, 'published' => 1, 'deleted' => 2];


        if (intval($cross->id) === 0) {
            $state = 1;
            if (isset($cross->attribute)) {
                $cross->attribute = (array) $cross->attribute;
                if (isset($cross->attribute['state'])
                 && isset($status[$cross->attribute['state']])
                 && $status[$cross->attribute['state']] === 0) {
                    $state = $status[$cross->attribute['state']];
                }
            }
            $sql = "insert into crosses (`created_at`, `updated_at`, `state`,
                    `title`, `description`, `exfee_id`, `begin_at`, `place_id`,
                    `timezone`, `origin_begin_at`, `background`, `date_word`,
                    `time_word`, `date`, `time`, `outputformat`,
                    `by_identity_id`) values( NOW(), NOW(), '{$state}',
                    '{$cross->title}', '{$cross->description}', {$exfee_id},
                    '{$begin_at_time_in_old_format}', {$place_id},
                    '{$cross_time->begin_at->timezone}',
                    '{$cross_time->origin}', '{$background}',
                    '{$cross_time->begin_at->date_word}',
                    '{$cross_time->begin_at->time_word}',
                    '{$cross_time->begin_at->date}',
                    '{$cross_time->begin_at->time}',
                    '{$cross_time->outputformat}', {$by_identity_id})";
            $result        = $this->query($sql);
            $cross_id      = intval($result['insert_id']);
            return $cross_id;
        } else {
            $updatefields  = [];
            $cross_updated = [];
            $updated       = ['updated_at'  => date('Y-m-d H:i:s', time()),
                              'identity_id' => $by_identity_id];
            if ($place_id > 0 && $old_cross
             && (dbescape($old_cross->place->title)       !== $cross->place->title
              || dbescape($old_cross->place->description) !== $cross->place->description
              || (float) $old_cross->place->lng !== (float) $cross->place->lng
              || (float) $old_cross->place->lat !== (float) $cross->place->lat
              || $old_cross->place->provider    !== $cross->place->provider
              || $old_cross->place->external_id !== $cross->place->external_id
              || $old_cross->place->id          !=  $place_id)) {
                array_push($updatefields, "`place_id`        =  {$place_id}");
                $cross_updated['place']       = $updated;
            }
            if (isset($cross->title) && $cross->title && $old_cross && dbescape($old_cross->title) !== $cross->title) {
                array_push($updatefields, "`title`           = '{$cross->title}'");
                $cross_updated['title']       = $updated;
            }

            if (isset($cross->description) && $old_cross && dbescape($old_cross->description) !== $cross->description) {
                array_push($updatefields, "`description`     = '{$cross->description}'");
                $cross_updated['description'] = $updated;
            }

            if ($setTime
             && $cross_time
             && $old_cross
             && (dbescape($old_cross->time->origin) !== $cross->time->origin
              || $old_cross->time->begin_at->date !== $cross->time->begin_at->date
              || $old_cross->time->begin_at->time !== $cross->time->begin_at->time)) {
                array_push($updatefields, "`begin_at`        = '{$begin_at_time_in_old_format}'");
                array_push($updatefields, "`date_word`       = '{$cross_time->begin_at->date_word}'");
                array_push($updatefields, "`time_word`       = '{$cross_time->begin_at->time_word}'");
                array_push($updatefields, "`date`            = '{$cross_time->begin_at->date}'");
                array_push($updatefields, "`time`            = '{$cross_time->begin_at->time}'");
                array_push($updatefields, "`outputformat`    = '{$cross_time->outputformat}'");
                array_push($updatefields, "`timezone`        = '{$cross_time->begin_at->timezone}'");
                array_push($updatefields, "`origin_begin_at` = '{$cross_time->origin}'");
                $cross_updated['time']        = $updated;
                // despatch remind {
                $hlpQueue = $this->getHelperByName('Queue');
                $hlpExfee = $this->getHelperByName('Exfee');
                $cross->exfee = $hlpExfee->getExfeeById($cross->exfee_id, false, true);
                $hlpQueue->despatchRemind($cross, $cross->exfee, -$by_identity_id, $by_identity_id);
                // }
            }

            if ($bgUpdate && $background !== $old_background) {
                array_push($updatefields, "background = '{$background}'");
                $cross_updated['background']  = $updated;
            }

            $helpExfee = $this->getHelperByName('Exfee');
            $host_ids  = $helpExfee->getHostIdentityIdsByExfeeId($exfee_id);
            if ($host_ids && is_array($host_ids) && in_array($by_identity_id, $host_ids)) {
                if ($cross->attribute && is_array($cross->attribute)) {
                    if (isset($cross->attribute['state'])
                     && isset($status[$cross->attribute['state']])) {
                        array_push($updatefields, '`state`  = ' . $status[$cross->attribute['state']]);
                    }
                    if (isset($cross->attribute['closed'])) {
                        array_push($updatefields, '`closed` = ' . !!$cross->attribute['closed']);
                    }
                }
            } else if ($old_cross->attribute['state'] !== 'published' || $old_cross->attribute['closed']) {
                return 0;
            }

            $updatesql = implode($updatefields, ',');
            if ($updatesql) {
                $sql    = "UPDATE `crosses` SET `updated_at` = NOW(), {$updatesql} WHERE `id` = {$cross->id}";
                $result = $this->query($sql);
                if ($result <= 0) {
                    return 0;
                }
                saveUpdate($cross->id, $cross_updated);
            }

            return [
                'cross_id'     => $cross->id,
                'notification' => !(sizeof($cross_updated)       === 1
                                && array_keys($cross_updated)[0] === 'background')
            ];
        }

        return 0;
    }


    public function generateCrossAccessToken(
        $cross_id, $identity_id, $user_id = 0
    ) {
        $hlpExfeAuth = $this->getHelperByName('ExfeAuth');
        $resource    = ['token_type'   => 'cross_access_token',
                        'cross_id'     => (int) $cross_id,
                        'identity_id'  => (int) $identity_id,
                        'user_id'      => (int) $user_id];
        $data        = $resource
                     + ['created_time' => time(),
                        'updated_time' => time()];
        return $cross_id && $identity_id
             ? $hlpExfeAuth->create($resource, $data, 60 * 60 * 24 * 7) // for 1 week
             : null;
    }


    public function getCrossAccessToken($token) {
        $hlpExfeAuth = $this->getHelperByName('ExfeAuth');
        return $hlpExfeAuth->keyGet($token);
    }


    public function getExfeeByCrossId($cross_id) {
        $sql = "select exfee_id from crosses where `id` = $cross_id;";
        $result = $this->getRow($sql);
        return $result["exfee_id"];
    }


    public function archiveCrossByCrossIdAndUserId($cross_id, $user_id, $archive = true) {
        if (!$cross_id || !$user_id) {
            return null;
        }
        $hlpCross = $this->getHelperByName('Cross');
        $hlpExfee = $this->getHelperByName('Exfee');
        $cross    = $hlpCross->getCross($cross_id);
        if ($cross) {
            foreach ($cross->exfee->invitations as $invitation) {
                if ($invitation->identity->connected_user_id === $user_id
                 && $invitation->rsvp_status                 !== 'REMOVED'
                 && $invitation->rsvp_status                 !== 'NOTIFICATION') {
                    $key = array_search('ARCHIVED', $invitation->remark);
                    if ($key === false && $archive) {
                        $invitation->remark[] = 'ARCHIVED';
                        $hlpExfee->updateInvitationRemarkById(
                            $invitation->id, $invitation->remark
                        );
                    } else if ($key !== false && !$archive) {
                        unset($invitation->remark[$key]);
                        $invitation->remark = array_values($invitation->remark);
                        $hlpExfee->updateInvitationRemarkById(
                            $invitation->id, $invitation->remark
                        );
                    }
                }
            }
            delCache("exfee:{$cross->exfee->id}");
            return true;
        }
        return null;
    }


    public function deleteCrossByCrossIdAndUserId($cross_id, $user_id, $delete = true) {
        if (!$cross_id || !$user_id) {
            return null;
        }
        $hlpCross = $this->getHelperByName('Cross');
        $hlpExfee = $this->getHelperByName('Exfee');
        $cross    = $hlpCross->getCross($cross_id);
        if ($cross) {
            foreach ($cross->exfee->invitations as $invitation) {
                if ($invitation->identity->connected_user_id === $user_id
                 && $invitation->rsvp_status                 !== 'REMOVED'
                 && $invitation->host) {
                    if ($cross->attribute['state'] !== 'deleted' && $delete) {
                        $sql = "UPDATE `crosses` SET `updated_at` = NOW(), `state` = 2 WHERE `id` = {$cross->id}";
                        if ($this->query($sql) > 0) {
                            return true;
                        }
                    } else if ($cross->attribute['state'] === 'deleted' && !$delete) {
                        $sql = "UPDATE `crosses` SET `updated_at` = NOW(), `state` = 1 WHERE `id` = {$cross->id}";
                        if ($this->query($sql) > 0) {
                            return true;
                        }
                    }
                    return false;
                }
            }
        }
        return null;
    }


    public function getDraftStatusBy($cross_id) {
        if ($cross_id) {
            $state = $this->getRow(
                "SELECT `state` FROM `crosses` WHERE `id` = {$cross_id}"
            );
            if ($state) {
                return (int) $state['state'] === 0 ? true : false;
            }
        }
        return null;
    }


    public function publishCrossBy($cross_id) {
        if ($cross_id) {
            return $this->query(
                "UPDATE `crosses` SET `state` = 1 WHERE `id` = {$cross_id}"
            );
        }
        return null;
    }


    public function validateCross($cross, $old_cross = null) {
        // init
        $result = ['cross' => $cross, 'error' => []];
        // check structure
        if (!$cross || !is_object($cross)) {
            $result['error'][] = 'invalid_cross_structure';
        }
        // check title
        if (isset($result['cross']->title)) {
            $result['cross']->title = formatTitle($result['cross']->title, 233);
            if (strlen($result['cross']->title) === 0) {
                $result['error'][] = 'empty_cross_title';
            }
        }
        // check description
        if (isset($result['cross']->description)) {
            $result['cross']->description = formatDescription($result['cross']->description, 0);
        }
        // check time
        if (isset($result['cross']->time)) {
            $hlpTime = $this->getHelperByName('Time');
            if ($old_cross
             && $old_cross->time->origin === $result['cross']->time->origin) {
                $cross_time = $old_cross->time;
            } else {
                $cross_time = $hlpTime->parseTimeString(
                    $result['cross']->time->origin,
                    $result['cross']->time->begin_at->timezone
                );
            }
            switch ($cross_time) {
                case 'timezone_error':
                    $result['error'][] = 'timezone_error';
                    break;
                default:
                    $cross_time->origin = formatTitle($cross_time->origin);
                    $result['cross']->time = $cross_time;
            }
        }
        // check place
        if (isset($result['cross']->place)) {
            $hlpPlace = $this->getHelperByName('Place');
            $chkPlace = $hlpPlace->validatePlace($result['cross']->place);
            $result['cross']->place = $chkPlace['place'];
            $result['error'] = array_merge($result['error'], $chkPlace['error']);
        }
        // check exfee
        if (isset($result['cross']->exfee)) {
            if (isset(  $result['cross']->exfee->invitations)
            && is_array($result['cross']->exfee->invitations)
            &&          $result['cross']->exfee->invitations) {
                // @todo by @leask ///////
            } else {
                $result['error'][] = 'no_exfee_invitations';
            }
        }
        return $result;
    }


    public function doTutorial($identity, $background = '') {
        // @todo enabled this!

        return false;

        // init libs
        require_once dirname(dirname(__FILE__)) . '/lib/httpkit.php';
        // init models
        $hlpCross    = $this->getHelperByName('Cross');
        $hlpIdentity = $this->getHelperByName('Identity');
        $hlpTime     = $this->getHelperByName('Time');
        // init functions
        function nextStep2($step_id, $cross_id, $exfee_id, $identity_id, $delay = 5, $created_at = 0) {
            httpKit::request(
                EXFE_GOBUS_SERVER . '/v3/queue/-/POST/'
              . base64_url_encode(
                    SITE_URL . '/v3/bus/tutorials/' . ($step_id + 1)
                  . "?cross_id={$cross_id}"
                  . "&exfee_id={$exfee_id}"
                  . "&identity_id={$identity_id}" . ($created_at
                  ? "&created_at={$created_at}"   : '')
                ),
                ['update' => 'once', 'ontime' => time() + $delay], [],
                false, false, 3, 3, 'txt'
            );
        };
        // init robots
        if (!($bot233      = $hlpIdentity->getIdentityById(TUTORIAL_BOT_A))
         || !($botFrontier = $hlpIdentity->getIdentityById(TUTORIAL_BOT_B))
         || !($botCashbox  = $hlpIdentity->getIdentityById(TUTORIAL_BOT_C))
         || !($botClarus   = $hlpIdentity->getIdentityById(TUTORIAL_BOT_D))
         || !($botSmith    = $hlpIdentity->getIdentityById(explode(',', SMITH_BOT)[0]))) {
            return false;
        }
        // init cross
        $objCross = new stdClass;
        $objCross->title       = 'Explore EXFE';
        $objCross->description = 'Hey, this is 233 the EXFE cat. My friends Cashbox, Frontier and I will guide you through EXFE basics, come on.';
        $objCross->by_identity = $bot233;
        $objCross->time        = $hlpTime->parseTimeString(
            'Today',
            $hlpTime->getDigitalTimezoneBy($identity->timezone) ?: '+00:00'
        );
        $objCross->place       = new Place(
            0, 'Online', 'exfe.com', '', '', '', '', $now, $now
        );
        $objCross->attribute   = new stdClass;
        $objCross->attribute->state = 'published';
        $objBackground         = new stdClass;
        if (!$background) {
            $hlpBkg = $this->getHelperByName('Background');
            $allBgs = $hlpBkg->getAllBackground();
            $background = $allBgs[rand(0, sizeof($allBgs) - 1)];
        }
        $objCross->widget      = [new Background($background)];
        $objCross->type        = 'Cross';
        $objCross->exfee       = new Exfee;
        $now                   = time();
        $objCross->exfee->invitations = [
            new Invitation(
                0, $bot233,      $bot233, $bot233,
                'ACCEPTED',   'EXFE', '', $now, $now, true,  0, []
            ),
            new Invitation(
                0, $identity,    $bot233, $bot233,
                'NORESPONSE', 'EXFE', '', $now, $now, false, 0, []
            ),
            new Invitation(
                0, $botFrontier, $bot233, $bot233,
                'NORESPONSE', 'EXFE', '', $now, $now, false, 0, []
            ),
            new Invitation(
                0, $botCashbox,  $bot233, $bot233,
                'NORESPONSE', 'EXFE', '', $now, $now, false, 0, []
            ),
            new Invitation(
                0, $botSmith,    $bot233, $bot233,
                'NORESPONSE', 'EXFE', '', $now, $now, false, 0, []
            ),
        ];
        $gtResult = $hlpCross->gatherCross(
            $objCross, $bot233->id,
            $bot233->connected_user_id > 0 ? $bot233->connected_user_id : 0
        );
        $cross_id = @ (int) $gtResult['cross_id'];
        if ($cross_id > 0) {
            $objCross = $hlpCross->getCross($cross_id);
            $exfee_id = $objCross->exfee->id;
            nextStep2(2, $cross_id, $exfee_id, $identity->id, 60);
            $this->query(
                "UPDATE `identities` SET `tutorial_x_id` = {$cross_id} WHERE `id` = {$identity->id}"
            );
            if ($identity->connected_user_id > 0) {
                $this->query(
                    "UPDATE  `users` SET `tutorial_x_id` = {$cross_id} WHERE `id` = {$identity->connected_user_id}"
                );
            }
            return $objCross;
        }
        return false;
    }


    public function log() {
        $hlpHistory = $this->getHelperByName('History');
    }

 }
