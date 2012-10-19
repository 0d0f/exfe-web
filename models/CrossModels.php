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
        $exfee_ids = implode($exfee_id_list,",");

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
        $cross_time = $cross->time ?: new CrossTime('', '', '', '', '', '', '');
        $widgets    = $cross->widget;
        $background = '';
        if ($widgets) {
            foreach($widgets as $widget) {
                if ($widget->type === 'Background') {
                    $background = $widget->image;
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
        $cross->title                    = mysql_real_escape_string($cross->title);
        $cross->description              = mysql_real_escape_string($cross->description);
        $cross_time->origin              = mysql_real_escape_string($cross_time->origin);
        $cross_time->begin_at->timezone  = mysql_real_escape_string($cross_time->begin_at->timezone);
        $cross_time->begin_at->date_word = mysql_real_escape_string($cross_time->begin_at->date_word);
        $cross_time->begin_at->time_word = mysql_real_escape_string($cross_time->begin_at->time_word);
        $cross_time->begin_at->date      = mysql_real_escape_string($cross_time->begin_at->date);
        $cross_time->begin_at->time      = mysql_real_escape_string($cross_time->begin_at->time);
        $background                      = mysql_real_escape_string($background);

        $begin_at_time_in_old_format     = $cross_time->begin_at->date . (
            $cross_time->begin_at->date ? " {$cross_time->begin_at->time}" : ''
        );

        if (intval($cross->id) === 0) {
            $sql = "insert into crosses (`created_at`, `updated_at`, `state`,
                    `title`, `description`, `exfee_id`, `begin_at`, `place_id`,
                    `timezone`, `origin_begin_at`, `background`, `date_word`,
                    `time_word`, `date`, `time`, `outputformat`,
                    `by_identity_id`) values( NOW(), NOW(), '1',
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
             && ($old_cross->place->title       !== $cross->place->title
              || $old_cross->place->description !== $cross->place->description
              || $old_cross->place->lng         !== $cross->place->lng
              || $old_cross->place->lat         !== $cross->place->lat
              || $old_cross->place->provider    !== $cross->place->provider
              || $old_cross->place->external_id !== $cross->place->external_id
              || $old_cross->place->id          !=  $place_id)) {
                array_push($updatefields, "`place_id`        =  {$place_id}");
                $cross_updated['place']       = $updated;
            }

            if ($cross->title && $old_cross && $old_cross->title !== $cross->title) {
                array_push($updatefields, "`title`           = '{$cross->title}'");
                $cross_updated['title']       = $updated;
            }

            if ($cross->description && $old_cross && $old_cross->description !== $cross->description) {
                array_push($updatefields, "`description`     = '{$cross->description}'");
                $cross_updated['description'] = $updated;
            }

            if ($cross_time
             && $old_cross
             && ($old_cross->time->origin         !== $cross->time->origin
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
            }

            if ($background !== $old_background) {
                array_push($updatefields, "background = '{$background}'");
                $cross_updated['background']  = $updated;
            }

            $updatesql=implode($updatefields, ',');
            if ($updatesql) {
                $sql    = "UPDATE `crosses` SET `updated_at` = NOW(), {$updatesql} WHERE `id` = {$cross->id}";
                $result = $this->query($sql);
                if ($result <= 0) {
                    return 0;
                }
                saveUpdate($cross->id, $cross_updated);
            }

            return $cross->id;
        }

        return 0;
    }


    public function generateCrossAccessToken($cross_id, $identity_id) {
        $hlpExfeAuth = $this->getHelperByName('ExfeAuth');
        $resource    = ['token_type'   => 'cross_access_token',
                        'cross_id'     => (int) $cross_id,
                        'identity_id'  => (int) $identity_id];
        $data        = $resource
                     + ['created_time' => time(),
                        'updated_time' => time()];
        return $cross_id && $identity_id
             ? $hlpExfeAuth->generateToken($resource, $data, 60 * 60 * 24 * 7) // for 1 week
             : null;
    }


    public function getCrossAccessToken($token) {
        $hlpExfeAuth = $this->getHelperByName('ExfeAuth');
        return $hlpExfeAuth->getToken($token);
    }


    public function getExfeeByCrossId($cross_id) {
        $sql = "select exfee_id from crosses where `id` = $cross_id;";
        $result = $this->getRow($sql);
        return $result["exfee_id"];
    }


    public function validateCross($cross) {
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
        } else {
            $result['error'][] = 'no_cross_title';
        }
        // check description
        if (isset($result['cross']->description)) {
            $result['cross']->description = formatDescription($result['cross']->description, 0);
        } else {
            $result['error'][] = 'no_cross_description';
        }
        // check time
        if (isset($result['cross']->time)) {
            $result['cross']->origin = formatTitle($result['cross']->origin);
            // @todo by @leask ///////
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

 }
