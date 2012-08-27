<?php

class LogHelper extends ActionController {

    public function getXUpdate($userid, $crossId = 'all', $updated_since = '', $limit = 1000) {
        // init models
        $modIdentity = $this->getModelByName('identity');
        $modUser     = $this->getModelByName('user');
        $modCross    = $this->getModelByName('x');
        $modPlace    = $this->getModelByName('place');
        $modLog      = $this->getModelByName('log');

        // Get all cross
        if ($crossId === 'all') {
            $rawCross = $modCross->fetchCross($userid, 0, null, null, null);
        } else {
            $rawCross = $modCross->getCrossesByIds(array($crossId));
        }
        $allCross = array();
        foreach ($rawCross as $crossI => $crossItem) {
            $crossItem['place'] = array('line1'       => $crossItem['place_line1'],
                                        'line2'       => $crossItem['place_line2'],
                                        'provider'    => $crossItem['place_provider'],
                                        'external_id' => $crossItem['place_external_id'],
                                        'lng'         => $crossItem['place_lng'],
                                        'lat'         => $crossItem['place_lat']);
            unset($crossItem['place_id']);
            unset($crossItem['place_line1']);
            unset($crossItem['place_line2']);
            unset($crossItem['place_provider']);
            unset($crossItem['place_external_id']);
            unset($crossItem['place_lng']);
            unset($crossItem['place_lat']);
            $allCross[$crossItem['id']] = $crossItem;
        }
        $allCrossIds = array_keys($allCross);

        // Get recently logs
        $rawLogs = $modLog->getRecentlyLogsByCrossIds($allCrossIds, $updated_since, $limit);

        // clean logs
        $loged              = array();
        $relatedIdentityIds = array();
        foreach ($rawLogs as $logI => $logItem) {
            $rawLogs[$logI]['x_id'] = intval($logItem['to_id']);
            $doSkip         = false;
            switch ($logItem['action']) {
                case 'gather':
                    $rawLogs[$logI]['change_dna'] = "{$rawLogs[$logI]['x_id']}_title";
                    if (isset($loged[$rawLogs[$logI]['change_dna']])) {
                        $rawLogs[$loged[$rawLogs[$logI]['change_dna']]]['old_value'] = $logItem['change_summy'];
                    }
                    $doSkip  = true;
                    break;
                case 'change':
                    $rawLogs[$logI]['change_dna'] = "{$rawLogs[$logI]['x_id']}_{$logItem['to_field']}";
                    $rawLogs[$logI]['action']     = $logItem['to_field'];
                    $rawLogs[$logI]['new_value']  = $logItem['change_summy'];
                    switch ($logItem['to_field']) {
                        case 'title':
                            $rawLogs[$logI]['old_value'] = $logItem['meta'];
                            if (isset($loged[$rawLogs[$logI]['change_dna']])) {
                                $rawLogs[$loged[$rawLogs[$logI]['change_dna']]]['old_value'] = $logItem['change_summy'];
                            }
                            $loged[$rawLogs[$logI]['change_dna']] = $logI;
                            break;
                        case 'place':
                        case 'place_line1':
                        case 'place_line2':
                            $rawLogs[$logI]['action'] = 'place';
                            $objPlace =  json_decode(preg_replace('/\r\n|\r|\n/u', ' ', $logItem['meta']), true);
                            $rawLogs[$logI]['new_value']
                          = $logItem['change_summy'] !== '' || !isset($objPlace['line1'])
                          ? array('line1' => $logItem['change_summy'], 'line2' => '')
                          : $objPlace;
                            unset($rawLogs[$logI]['meta']);
                            break;
                        case 'begin_at':
                            if ($logItem['meta'] && !$logItem['change_summy']) {
                                $rawLogs[$logI]['new_value'] = json_decode($logItem['meta'], true);
                            } else {
                                $logItem['change_summy'] = explode(',', $logItem['change_summy']);
                                if (!isset($rawLogs[$logI]['new_value'][1])
                                 || $rawLogs[$logI]['new_value'][1] === '0') {
                                    $rawLogs[$logI]['new_value'][1] = '';
                                }
                                $rawLogs[$logI]['new_value'] = array(
                                    'begin_at'        => $logItem['change_summy'][0],
                                    'time_type'       => $logItem['change_summy'][1],
                                    'timezone'        => $allCross[$rawLogs[$logI]['x_id']]['timezone'],
                                    'origin_begin_at' => $logItem['meta'] ? $logItem['meta'] : '',
                                );
                            }
                        case 'description':
                            break;
                        default:
                            $doSkip = true;
                    }
                    break;
                case 'conversation':
                    $rawLogs[$logI]['change_dna'] = "{$rawLogs[$logI]['x_id']}_conversation";
                    $rawLogs[$logI]['message']    = $logItem['change_summy'];
                    break;
                case 'rsvp':
                case 'exfee':
                    switch ($logItem['to_field']) {
                        case '':
                        case 'rsvp':
                            $logItem['change_summy']
                          = strpos($logItem['change_summy'], ':') === false
                          ? array($logItem['from_id'], intval($logItem['change_summy']))
                          : array_map('intval', explode(':',  $logItem['change_summy']));
                            switch ($logItem['change_summy'][1]) {
                                case 1:
                                    $rawLogs[$logI]['action'] = 'confirmed';
                                    break;
                                case 2:
                                    $rawLogs[$logI]['action'] = 'declined';
                                    break;
                                case 3:
                                    $rawLogs[$logI]['action'] = 'interested';
                                    break;
                                default:
                                    $doSkip = true;
                            }
                            array_push($relatedIdentityIds, $rawLogs[$logI]['to_identity_id'] = $logItem['change_summy'][0]);
                            $rawLogs[$logI]['change_dna'] = "{$rawLogs[$logI]['x_id']}_exfee_{$rawLogs[$logI]['to_id']}";
                            $rawLogs[$logI]['soft_rsvp']  = $logItem['change_summy'][1] === 0
                                                         || $logItem['change_summy'][1] === 3;
                            break;
                        case 'addexfee':
                        case 'delexfee':
                            $rawLogs[$logI]['change_dna'] = "{$rawLogs[$logI]['x_id']}_exfee_{$logItem['change_summy']}";
                            $rawLogs[$logI]['action']     = $logItem['to_field'];
                            array_push($relatedIdentityIds, $rawLogs[$logI]['to_identity_id'] = intval($logItem['change_summy']));
                            break;
                        default:
                            $doSkip = true;
                    }
                    break;
                default:
                    $doSkip = true;
            }
            if ($doSkip || !($logItem['from_id'] = intval($logItem['from_id']))) {
                unset($rawLogs[$logI]); // 容错处理
                continue;
            }
            $rawLogs[$logI]['x_title']       = $allCross[$rawLogs[$logI]['x_id']]['title'];
            $rawLogs[$logI]['x_description'] = $allCross[$rawLogs[$logI]['x_id']]['description'];
            $rawLogs[$logI]['x_background']  = $allCross[$rawLogs[$logI]['x_id']]['background'];
            $rawLogs[$logI]['x_begin_at']    = $allCross[$rawLogs[$logI]['x_id']]['begin_at'];
            $rawLogs[$logI]['x_time_type']   = $allCross[$rawLogs[$logI]['x_id']]['time_type'];
            $rawLogs[$logI]['x_place']       = $allCross[$rawLogs[$logI]['x_id']]['place'];
            $rawLogs[$logI]['log_id']        = intval($rawLogs[$logI]['id']);
            array_push($relatedIdentityIds, $rawLogs[$logI]['x_host_id']      = intval($allCross[$rawLogs[$logI]['x_id']]['host_id']));
            array_push($relatedIdentityIds, $rawLogs[$logI]['by_identity_id'] = $logItem['from_id']);
            unset($rawLogs[$logI]['id']);
            unset($rawLogs[$logI]['change_summy']);
            unset($rawLogs[$logI]['from_id']);
            unset($rawLogs[$logI]['from_obj']);
            unset($rawLogs[$logI]['to_obj']);
            unset($rawLogs[$logI]['to_id']);
            unset($rawLogs[$logI]['to_field']);
        }
        $rawLogs = array_merge($rawLogs);

        // get human identities
        $humanIdentities   = array();
        $userIds           = array();
        $relatedIdentities = $modIdentity->getIdentitiesByIdentityIds(
            array_flip(array_flip($relatedIdentityIds))
        );
        foreach ($relatedIdentities as $ridI => $ridItem) {
            $user = $modUser->getUserByIdentityId($ridItem['id']);
            $humanIdentities[$ridItem['id']] = humanIdentity($ridItem, $user);
            $humanIdentities[$ridItem['id']]['user_id'] = intval($user['id']);
        }

        // render human identities
        foreach ($rawLogs as $logI => $logItem) {
            $rawLogs[$logI]['by_identity']     = $humanIdentities[$rawLogs[$logI]['by_identity_id']];
            unset($rawLogs[$logI]['by_identity_id']);
            $rawLogs[$logI]['x_host_identity'] = $humanIdentities[$rawLogs[$logI]['x_host_id']];
            unset($rawLogs[$logI]['x_host_id']);
            if (!isset($rawLogs[$logI]['to_identity_id'])) {
                continue;
            }
            $rawLogs[$logI]['to_identity']     = $humanIdentities[$rawLogs[$logI]['to_identity_id']];
            unset($rawLogs[$logI]['to_identity_id']);
        }

        return $rawLogs;
    }

    public function getMergedXUpdate($userid, $crossId = 'all', $updated_since = '', $limit = 1000) {
        $rawLogs = $this->getXUpdate($userid, $crossId, $updated_since, $limit);

        $preItemLogs = array();
        $preItemDna  = '';
        $preItemTime = 0;
        $magicTime   = 153; // 2:33
        foreach ($rawLogs as $logI => $logItem) {
            $curDna  = ($logItem['by_identity']['user_id']
                     ? "u{$logItem['by_identity']['user_id']}"
                     : "i{$logItem['by_identity']['id']}")
                     . "_c{$logItem['x_id']}";
            $difTime = abs($preItemTime - ($curTime = strtotime($logItem['time'])));
            if ($curDna === $preItemDna && $difTime <= $magicTime && isset($preItemLogs[$logItem['action']])) {
                switch ($logItem['action']) {
                    case 'title':
                        if (isset($logItem['old_value'])
                         && $logItem['old_value'] !== null
                         && $logItem['old_value'] !== '') {
                            $rawLogs[$preItemLogs[$logItem['action']]]['old_value'] = $logItem['old_value'];
                        }
                        break;
                    case 'addexfee':
                    case 'delexfee':
                    case 'confirmed':
                    case 'declined':
                    case 'interested':
                        $loged = false;
                        foreach ($rawLogs[$preItemLogs[$logItem['action']]]['to_identity'] as $subItem) {
                            if ($subItem['id'] === $logItem['to_identity']['id']) {
                                $loged = true;
                                break;
                            }
                        }
                        if (!$loged) {
                            array_push(
                                $rawLogs[$preItemLogs[$logItem['action']]]['to_identity'],
                                $logItem['to_identity']
                            );
                        }
                }
                if ($logItem['action'] !== 'conversation') {
                    unset($rawLogs[$logI]);
                }
            } else {
                switch ($logItem['action']) {
                    case 'addexfee':
                    case 'delexfee':
                    case 'confirmed':
                    case 'declined':
                    case 'interested':
                        $rawLogs[$logI]['to_identity'] = array($logItem['to_identity']);
                }
                if ($curDna !== $preItemDna || $difTime > $magicTime) {
                    $preItemLogs = array();
                }
                $preItemLogs[$logItem['action']] = $logI;
            }
            $preItemDna  = $curDna;
            $preItemTime = $curTime;
        }
        return array_merge($rawLogs);
    }

}
