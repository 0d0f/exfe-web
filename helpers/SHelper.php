<?php

class SHelper extends ActionController
{

    public function GetAllUpdate($userid, $updated_since = '', $limit = 200)
    {
        // init models
        $modIdentity = $this->getModelByName('identity');
        $modUser     = $this->getModelByName('user');
        $modCross    = $this->getModelByName('x');
        $modLog      = $this->getModelByName('log');

        // Get all cross
        $rawCross = $modCross->fetchCross($userid, 0, null, null, null);
        $allCross = array();
        foreach ($rawCross as $crossI => $crossItem) {
            $allCross[$crossItem['id']] = $crossItem;
        }
        $allCrossIds = array_keys($allCross);

        // Get recently logs
        $rawLogs = $modLog->getRecentlyLogsByCrossIds($allCrossIds, $updated_since, $limit);

        // clean logs
        $loged               = array();
        $relatedIdentityIds  = array();
        foreach ($rawLogs as $logI => $logItem) {
            $rawLogs[$logI]['x_id'] = intval($logItem['to_id']);
            $doSkip          = false;
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
                            if (isset($loged[$rawLogs[$logI]['change_dna']])) {
                                $rawLogs[$loged[$rawLogs[$logI]['change_dna']]]['old_value'] = $logItem['change_summy'];
                            }
                            $loged[$rawLogs[$logI]['change_dna']] = $logI;
                            break;
                        case 'place':
                        case 'place_line1':
                        case 'place_line2':
                            $rawLogs[$logI]['action'] = 'place';
                            $rawLogs[$logI]['new_value']
                          = $logItem['change_summy'] !== '' ?: json_decode(
                                preg_replace('/\r\n|\r|\n/u', ' ', $logItem['meta']), true
                            );
                            unset($rawLogs[$logI]['meta']);
                            break;
                        case 'description':
                        case 'begin_at':
                            break;
                        default:
                            $doSkip = true;
                    }
                    break;
                case 'conversation':
                    $rawLogs[$logI]['change_dna'] = "{$rawLogs[$logI]['x_id']}_conversation";
                    $rawLogs[$logI]['message']   = $logItem['change_summy'];
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
            $rawLogs[$logI]['title']    = $allCross[$rawLogs[$logI]['x_id']]['title'];
            $rawLogs[$logI]['log_id']   = intval($rawLogs[$logI]['id']);
            $rawLogs[$logI]['base62id'] = int_to_base62($rawLogs[$logI]['x_id']);
            unset($rawLogs[$logI]['id']);
            unset($rawLogs[$logI]['change_summy']);
            unset($rawLogs[$logI]['from_id']);
            unset($rawLogs[$logI]['from_obj']);
            unset($rawLogs[$logI]['to_obj']);
            unset($rawLogs[$logI]['to_id']);
            unset($rawLogs[$logI]['to_field']);
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
            $humanIdentities[$ridItem['id']]['user_id'] = $user['id'];
        }

        // merge logs
        foreach ($rawLogs as $logI => $logItem) {
            $rawLogs[$logI]['by_identity'] = $humanIdentities[$rawLogs[$logI]['by_identity_id']];
            unset($rawLogs[$logI]['by_identity_id']);
            if (!isset($rawLogs[$logI]['to_identity_id'])) {
                continue;
            }
            $rawLogs[$logI]['to_identity'] = $humanIdentities[$rawLogs[$logI]['to_identity_id']];
            unset($rawLogs[$logI]['to_identity_id']);
        }

        return $rawLogs;
    }

}
