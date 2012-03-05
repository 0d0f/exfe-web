<?php

class SHelper extends ActionController
{

    public function GetAllUpdate($userid, $updated_since = '', $limit = 200)
    {
        // init models
        $modIdentity = $this->getModelByName('identity');
        $modUser     = $this->getModelByName('user');
        $modCross    = $this->getModelByName('x');
        $modPlace    = $this->getModelByName('place');
        $modLog      = $this->getModelByName('log');

        // Get all cross
        $rawCross = $modCross->fetchCross($userid, 0, null, null, null);
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
                            $rawLogs[$logI]['new_value'] = explode(',', $logItem['change_summy']);
                            $rawLogs[$logI]['new_value'][0] = trim($rawLogs[$logI]['new_value'][0]);
                            if (!isset($rawLogs[$logI]['new_value'][1]) || $rawLogs[$logI]['new_value'][1] === '0') {
                                $rawLogs[$logI]['new_value'][1] = '';
                            }
                            $rawLogs[$logI]['new_value'] = implode(',', $rawLogs[$logI]['new_value']);
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
            if ($doSkip) {
                unset($rawLogs[$logI]); // 容错处理
                continue;
            }
            $rawLogs[$logI]['x_title']       = $allCross[$rawLogs[$logI]['x_id']]['title'];
            $rawLogs[$logI]['x_description'] = $allCross[$rawLogs[$logI]['x_id']]['description'];
            $rawLogs[$logI]['x_begin_at']    = $allCross[$rawLogs[$logI]['x_id']]['begin_at'];
            $rawLogs[$logI]['x_time_type']   = $allCross[$rawLogs[$logI]['x_id']]['time_type'];
            $rawLogs[$logI]['x_place']       = $allCross[$rawLogs[$logI]['x_id']]['place'];
            $rawLogs[$logI]['log_id']        = intval($rawLogs[$logI]['id']);
            $rawLogs[$logI]['x_base62id']    = int_to_base62($rawLogs[$logI]['x_id']);
            unset($rawLogs[$logI]['id']);
            unset($rawLogs[$logI]['change_summy']);
            unset($rawLogs[$logI]['from_id']);
            unset($rawLogs[$logI]['from_obj']);
            unset($rawLogs[$logI]['to_obj']);
            unset($rawLogs[$logI]['to_id']);
            unset($rawLogs[$logI]['to_field']);
            array_push($relatedIdentityIds, $rawLogs[$logI]['x_host_id']      = intval($allCross[$rawLogs[$logI]['x_id']]['host_id']));
            array_push($relatedIdentityIds, $rawLogs[$logI]['by_identity_id'] = intval($logItem['from_id']));
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

}
