<?php

class SHelper extends ActionController
{

    public function GetAllUpdate($userid, $updated_since = '', $limit = 200, $complexobject = false)
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
        $rawLogs = $modLog->getRecentlyLogsByCrossIds($allCrossIds,$updated_since,$limit);

        // clean logs
        $loged   = array();
        foreach ($rawLogs as $logI => $logItem) {
            $xId = $logItem['to_id'];
            $rawLogs[$logI]['from_id']
          = $logItem['from_id'] = intval($logItem['from_id']);
            switch ($logItem['action']) {
                case 'gather':
                    $changeDna = "{$xId}_title";
                    if (isset($loged[$changeDna])
                    && !isset($rawLogs[$loged[$changeDna]]['oldtitle'])) {
                        $rawLogs[$loged[$changeDna]]['oldtitle']
                      = $logItem['change_summy'];
                    }
                    unset($rawLogs[$logI]);
                    break;
                case 'change':
                    $changeDna = "{$xId}_{$logItem['to_field']}";
                    if (isset($loged[$changeDna])) {
                        if ($logItem['to_field'] === 'title') {
                            if (!isset(
                                    $rawLogs[$loged[$changeDna]]['oldtitle']
                                )) {
                                $rawLogs[$loged[$changeDna]]['oldtitle']
                              = $logItem['change_summy'];
                            }
                        }
                        unset($rawLogs[$logI]);
                    } else {
                        $loged[$changeDna] = $logI;
                    }
                    break;
                case 'conversation':
                    // $changeDna = "{$xId}_conversation";
                    // $loged[$changeDna] = $logI;
                    break;
                case 'rsvp':
                case 'exfee':
                    $doSkip = false;
                    switch ($logItem['to_field']) {
                        case '':
                            $changeDna = "{$xId}_exfee_{$logItem['from_id']}";
                            $rawLogs[$logI]['change_summy']
                          = $logItem['change_summy']
                          = intval($logItem['change_summy']);
                            $dnaValue  = array(
                                'action'    => 'rsvp',
                                'offset'    => $logI,
                                'soft_rsvp' => $logItem['change_summy'] === 0
                                            || $logItem['change_summy'] === 3,
                            );
                            break;
                        case 'rsvp':
                            $rawLogs[$logI]['change_summy'] = explode(
                                ':',
                                $logItem['change_summy']
                            );
                            $rawLogs[$logI]['change_summy']
                          = array_map('intval',$rawLogs[$logI]['change_summy']);
                            $changeDna = "{$xId}_exfee_"
                                       . "{$rawLogs[$logI]['change_summy'][0]}";
                            $dnaValue  = array('action' => 'rsvp',
                                               'offset' => $logI);
                            break;
                        case 'addexfee':
                        case 'delexfee':
                            $changeDna = "{$xId}_exfee_"
                                       . "{$logItem['change_summy']}";
                            $dnaValue  = array('action' => $logItem['to_field'],
                                               'offset' => $logI);
                            break;
                        default:
                            $doSkip = true; // 容错处理
                    }
                    if ($doSkip) {
                        unset($rawLogs[$logI]);
                        break;
                    }
                    if (isset($loged[$changeDna])) {
                        if ($dnaValue['action'] === 'addexfee'
                         && $loged[$changeDna]['action'] === 'rsvp'
                         && $loged[$changeDna]['soft_rsvp']) {
                            $rawLogs[$loged[$changeDna]['offset']] = $logItem;
                            $loged[$changeDna] = $dnaValue;
                        } else if (($loged[$changeDna]['action'] === 'addexfee'
                          && $dnaValue['action'] === 'delexfee')
                         || ($loged[$changeDna]['action'] === 'delexfee'
                          && $dnaValue['action'] === 'addexfee')) {
                            $loged[$changeDna]['action'] = 'skipped';
                            unset($rawLogs[$loged[$changeDna]['offset']]);
                        }
                        unset($rawLogs[$logI]);
                    } else {
                        $loged[$changeDna] = $dnaValue;
                    }
            }
        }

        // merge logs
        $cleanLogs = array();
        $xlogsHash = array();
        $relatedIdentityIds = array();
        foreach ($rawLogs as $logI => $logItem) {
            $xId = $logItem['to_id'];
            if (!isset($xlogsHash[$xId])) {
                $xlogsHash[$xId]
              = array_push($cleanLogs, array('cross_id' => $xId)) - 1;
            }
            switch ($logItem['action']) {
                case 'change':
                    $cleanLogs[$xlogsHash[$xId]]['change'][$logItem['to_field']]
                  = array('time'      => $logItem['time'],
                          'by_id'     => $logItem['from_id'],
                          'new_value' => $logItem['change_summy'],
                          'old_value' => isset($logItem['oldtitle'])
                                       ? $logItem['oldtitle'] : null);
                    array_push($relatedIdentityIds, $logItem['from_id']);
                    break;
                case 'conversation':
                    if ($complexobject === true) {
                        $myidentity = $modIdentity->getIdentityById($logItem['from_id']);
                        $user = $modUser->getUserByIdentityId($logItem['from_id']);
                        $identity = humanIdentity($myidentity, $user);
                    }
                    if (!isset($cleanLogs[$xlogsHash[$xId]]['conversation'])) {
                        $cleanLogs[$xlogsHash[$xId]]['conversation'] = array();
                    }
                    array_push($cleanLogs[$xlogsHash[$xId]]['conversation'],
                               array('time'     => $logItem['time'],
                                     'by_id'    => $logItem['from_id'],
                                     'message'  => $logItem['change_summy'],
                                     'meta'     => $logItem['meta'],
                                     'num_msgs' => $logItem['num_conversation'],
                                     'identity' => $identity));
                    array_push($relatedIdentityIds, $logItem['from_id']);
                    break;
                case 'rsvp':
                case 'exfee':
                    switch ($logItem['to_field']) {
                        case '':
                        case 'rsvp':
                            if (is_array($logItem['change_summy'])) {
                                list($toExfee,$action)=$logItem['change_summy'];
                            } else {
                                $toExfee = $logItem['from_id'];
                                $action  = $logItem['change_summy'];
                            }
                            if ($action === 1) {
                                $action = 'confirmed';
                            } else if ($action === 2) {
                                $action = 'declined';
                            } else {
                                break;
                            }
                            if (!isset($cleanLogs[$xlogsHash[$xId]][$action])) {
                                $cleanLogs[$xlogsHash[$xId]][$action] = array();
                            }
                            array_push(
                                $cleanLogs[$xlogsHash[$xId]][$action],
                                array('time'  => $logItem['time'],
                                      'by_id' => $logItem['from_id'],
                                      'meta'  => $logItem['meta'],
                                      'to_id' => $toExfee)
                            );
                            array_push($relatedIdentityIds,$logItem['from_id']);
                            array_push($relatedIdentityIds,$toExfee);
                            break;
                        case 'addexfee':
                        case 'delexfee':
                            $action = $logItem['action'];
                            if (!isset($cleanLogs[$xlogsHash[$xId]][$action])) {
                                $cleanLogs[$xlogsHash[$xId]][$action] = array();
                            }
                            array_push(
                                $cleanLogs[$xlogsHash[$xId]][$action],
                                array('time'  => $logItem['time'],
                                      'by_id' => $logItem['from_id'],
                                      'to_id' => $logItem['change_summy'])
                            );
                            array_push($relatedIdentityIds,$logItem['from_id']);
                            array_push($relatedIdentityIds,
                                       $logItem['change_summy']);
                    }
            }
            if (count($cleanLogs[$xlogsHash[$xId]]) === 1) {
                array_pop($cleanLogs);
                unset($xlogsHash[$xId]);
            }
        }

        // get human identities
        $humanIdentities = array();
        $relatedIdentities = $modIdentity->getIdentitiesByIdentityIds(
            array_flip(array_flip($relatedIdentityIds))
        );
        foreach ($relatedIdentities as $ridI => $ridItem) {
            $user = $modUser->getUserByIdentityId($ridItem['identity_id']);
            $humanIdentities[$ridItem['id']] = humanIdentity($ridItem, $user);
        }

        // merge cross details and humanIdentities
        foreach ($cleanLogs as $logI => $logItem) {
            $cleanLogs[$logI]['base62id'] = int_to_base62($logItem['cross_id']);
            $cleanLogs[$logI]['title']
          = $allCross[$logItem['cross_id']]['title'];
            $cleanLogs[$logI]['begin_at']
          = $allCross[$logItem['cross_id']]['begin_at'];
            foreach (array('change', 'conversation', 'confirmed', 'declined',
                           'addexfee', 'delexfee') as $action) {
                if (isset($logItem[$action])) {
                    foreach ($logItem[$action] as $actionI => $actionItem) {
                        $cleanLogs[$logI][$action][$actionI]['by_name']
                      = $humanIdentities[$actionItem['by_id']]['name'];
                        if (!isset(
                                $cleanLogs[$logI][$action][$actionI]['to_id'])
                            ) {
                            continue;
                        }
                        $cleanLogs[$logI][$action][$actionI]['to_name']
                      = $humanIdentities[$actionItem['to_id']]['name'];
                    }
                }
            }
        }

        return $cleanLogs;
    }

}
