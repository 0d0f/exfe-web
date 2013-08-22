<?php

set_time_limit(5);


class wechatActions extends ActionController {

    public function doIndex() {

    }


    public function doCallback() {
        $modWechat = $this->getModelByName('wechat');
        $params    = $this->params;
        if ($params['echostr']) {
            if ($modWechat->valid(
                $params['signature'],
                $params['timestamp'],
                $params['nonce']
            )) {
                echo $params['echostr'];
                return;
            }
        } else {
            $modUser     = $this->getModelByName('User');
            $modIdentity = $this->getModelByName('Identity');
            $crossHelper = $this->getHelperByName('cross');
            $rawInput    = file_get_contents('php://input');
            $objMsg      = $modWechat->unpackMessage($rawInput);
            $now         = time();
            $rtnType     = 'text';
            $rtnMessage  = '';
            if (!($external_id = @$objMsg->FromUserName && @$objMsg->ToUserName
                               ? "{$objMsg->FromUserName}@{$objMsg->ToUserName}" : '')) {
                error_log('Empty FromUserName');
                return;
            }
            $identity = $modIdentity->getIdentityByProviderAndExternalUsername(
                'wechat', $external_id
            );
            if ($identity) {
                $identity_id = $identity->id;
            } else {
                if (!($rawIdentity = $modWechat->getIdentityBy($external_id))) {
                    header('HTTP/1.1 500 Internal Server Error');
                    return;
                }
                $identity_id = $modIdentity->addIdentity([
                    'provider'          => $rawIdentity->provider,
                    'external_id'       => $rawIdentity->external_id,
                    'name'              => $rawIdentity->name,
                    'external_username' => $rawIdentity->external_username,
                    'avatar'            => $rawIdentity->avatar,
                    'avatar_filename'   => $rawIdentity->avatar_filename,
                    'timezone'          => $rawIdentity->timezone,
                ]);
                $identity    = $modIdentity->getIdentityById($identity_id);
            }
            if (!$identity) {
                header('HTTP/1.1 500 Internal Server Error');
                return;
            }
            switch (@$objMsg->MsgType) {
                case 'event':
                    $event = @strtolower($objMsg->Event);
                    switch ($event) {
                        case 'subscribe':
                        case 'click':
                        case 'location':
                            // check user
                            $user_infos = $modUser->getUserIdentityInfoByIdentityId($identity_id);
                            $user_id    = 0;
                            $cross_id   = 0;
                            if (isset($user_infos['CONNECTED'])) {
                                $user_id  = $user_infos['CONNECTED'][0]['user_id'];
                            } else if (isset($user_infos['REVOKED'])) {
                                $user_id  = $user_infos['REVOKED'][0]['user_id'];
                                $modUser->setUserIdentityStatus($user_id, $identity_id, 3);
                            } else {
                                $user_id  = $modUser->addUser();
                                $modUser->setUserIdentityStatus($user_id, $identity_id, 3);
                                $identity = $modIdentity->getIdentityById($identity_id);
                            }
                            if (!$user_id) {
                                header('HTTP/1.1 500 Internal Server Error');
                                return;
                            }
                            $modIdentity = $this->getModelByName('Identity');
                            $exfeeHelper = $this->getHelperByName('exfee');
                            $idBot = explode(',', SMITH_BOT)[0];
                            switch ($event) {
                                case 'subscribe':
                                    $numIdentities = $modUser->getConnectedIdentityCount($user_id);
                                    $tutorial_x_id = $modUser->getTutorialXId($user_id, $identity_id);
                                    if (1 || $numIdentities === 1 && !$tutorial_x_id) {
                                        $cross = $crossHelper->doTutorial($identity);
                                        if ($cross) {
                                            $invitation = $exfeeHelper->getRawInvitationByCrossIdAndIdentityId(
                                                $cross->id, $idBot
                                            );
                                            if ($invitation) {
                                                $cross_id   = $cross->id;
                                                $rtnType    = 'news';
                                                $rtnMessage = [[
                                                    'Title'       => '欢迎使用“活点地图”',
                                                    'Description' => '',
                                                    'PicUrl'      => SITE_URL . '/static/img/routex_welcome@2x.jpg',
                                                    'Url'         => SITE_URL . "/!{$cross->id}/routex?xcode={$invitation['token']}&via={$identity->external_username}@{$identity->provider}",
                                                ]];
                                            }
                                        }
                                    }
                                    if (!$rtnMessage) {
                                        $rtnMessage = "嗨，{$identity->name}！欢迎再次开启“活点地图”。";
                                    }
                                    break;
                                case 'click':
                                    $bot = $modIdentity->getIdentityById($idBot);
                                    switch ($objMsg->EventKey) {
                                        case 'LIST_MAPS':
                                            $exfee_id_list = $exfeeHelper->getExfeeIdByUserid($user_id);
                                            $cross_list    = $crossHelper->getCrossesByExfeeIdList(
                                                $exfee_id_list, null, null, false, $user_id
                                            );
                                            $crosses       = [];
                                            foreach ($cross_list as $i => $cross) {
                                                if ($cross->attribute['state'] === 'deleted'
                                                || ($cross->attribute['state'] === 'draft'
                                                 && !in_array($user_id, $cross->exfee->hosts))) {
                                                } else {
                                                    $crosses[$cross->id] = $cross;
                                                }
                                                unset($cross_list[$i]);
                                            }
                                            $cross_list = null;
                                            $maps       = [];
                                            if ($crosses) {
                                                $rawMaps = httpKit::request(
                                                    EXFE_AUTH_SERVER . '/v3/routex/_inner/search/crosses',
                                                    null, array_keys($crosses),
                                                    false, false, 3, 3, 'json', true
                                                );
                                                $rawMaps = (
                                                    $rawMaps
                                                 && $rawMaps['http_code'] === 200
                                                 && $rawMaps['json']
                                                ) ? $rawMaps['json'] : [];
                                                foreach ($rawMaps as $rI => $rItem) {
                                                    if ($rItem['enable'] && sizeof($maps) < 10) {
                                                        $maps[] = $rItem['cross_id'];
                                                    }
                                                }
                                            }
                                            if ($maps) {
                                                $rtnMessage = [];
                                                $rtnType    = 'news';
                                                foreach ($maps as $map) {
                                                    $invitation = $exfeeHelper->getRawInvitationByCrossIdAndIdentityId(
                                                        $map, $idBot
                                                    );
                                                    if (!$invitation) {
                                                        $exfee = new Exfee;
                                                        $exfee->id = $crosses[$map]->exfee->id;
                                                        $exfee->invitations = [new Invitation(
                                                            0, $bot, $identity, $identity,
                                                            'ACCEPTED', 'EXFE', '', $now, $now, false, 0, []
                                                        )];
                                                        $udeResult = $exfeeHelper->updateExfee(
                                                            $exfee, $identity->id, $user_id, false, true
                                                        );
                                                        if ($udeResult) {
                                                            $invitation = $exfeeHelper->getRawInvitationByCrossIdAndIdentityId(
                                                                $map, $idBot
                                                            );
                                                        }
                                                    }
                                                    if (!$invitation) {
                                                        header('HTTP/1.1 500 Internal Server Error');
                                                        return;
                                                    }
                                                    $picUrl = API_URL . "/v3/crosses/{$map}/image?xcode={$invitation['token']}";
                                                    if ($rtnMessage) {
                                                        foreach ($crosses[$map]->exfee->invitations as $invItem) {
                                                            if ($invItem->identity->connected_user_id === $user_id
                                                             || $invItem->identity->id                === $identity_id) {
                                                                $picUrl = $invItem->invited_by->avatar['320_320'];
                                                                break;
                                                            }
                                                        }
                                                    }
                                                    $rtnMessage[] = [
                                                        'Title'       => $crosses[$map]->title,
                                                        'Description' => $crosses[$map]->description,
                                                        'PicUrl'      => $picUrl,
                                                        'Url'         => SITE_URL . "/!{$map}/routex?xcode={$invitation['token']}&via={$identity->external_username}@{$identity->provider}",
                                                    ];
                                                }
                                            } else {
                                                $rtnMessage = '您现在没有“活点地图”，创建一张并邀请朋友们吧。';
                                            }
                                            break;
                                        case 'CREATE_MAP':
                                            if (!$modIdentity->isLabRat($identity->id)) {
                                                $rtnMessage = "【封闭测试中  非常抱歉】\n若您知道测试口令请回复。";
                                                break;
                                            }
                                            // gather
                                            $modTime  = $this->getModelByName('Time');
                                            $objCross = new stdClass;
                                            $objCross->time        = $modTime->parseTimeString(
                                                'Today',
                                                $modTime->getDigitalTimezoneBy($identity->timezone) ?: '+08:00 GMT'
                                            );
                                            $timeArray = explode('-', $objCross->time->begin_at->date);
                                            $objCross->title       = "{$identity->name}的活点地图 " . (int)$timeArray[1] . '月' . (int)$timeArray[2] . '日';
                                            $objCross->description = '';
                                            $objCross->by_identity = $identity;
                                            $objCross->place       = new Place();
                                            $objCross->attribute   = new stdClass;
                                            $objCross->attribute->state = 'published';
                                            $objBackground         = new stdClass;
                                            $objCross->widget      = [new Background('wechat.jpg')];
                                            $objCross->type        = 'Cross';
                                            $objCross->exfee       = new Exfee;
                                            $objCross->exfee->invitations = [
                                                new Invitation(
                                                    0, $identity, $identity, $identity,
                                                    'ACCEPTED', 'EXFE', '', $now, $now, true,  0, []
                                                ),
                                                new Invitation(
                                                    0, $bot,      $identity, $identity,
                                                    'ACCEPTED', 'EXFE', '', $now, $now, false, 0, []
                                                )
                                            ];
                                            $gtResult = $crossHelper->gatherCross(
                                                $objCross, $identity->id, $user_id
                                            );
                                            $cross_id = @ (int) $gtResult['cross_id'];
                                            if ($cross_id <= 0) {
                                                header('HTTP/1.1 500 Internal Server Error');
                                                return;
                                            }
                                            // get invitation
                                            $invitation = $exfeeHelper->getRawInvitationByCrossIdAndIdentityId(
                                                $cross_id, $idBot
                                            );
                                            if (!$invitation) {
                                                header('HTTP/1.1 500 Internal Server Error');
                                                return;
                                            }
                                            // returns
                                            touchCross($cross_id, $identity->connected_user_id);
                                            $rtnType    = 'news';
                                            $rtnMessage = [[
                                                'Title'       => $objCross->title,
                                                'Description' => '开启这张“活点地图” 就能互相看到位置和轨迹。或长按转发邀请更多朋友们。',
                                                'PicUrl'      => '',
                                                'Url'         => SITE_URL . "/!{$cross_id}/routex?xcode={$invitation['token']}&via={$identity->external_username}@{$identity->provider}",
                                            ]];
                                            break;
                                        case 'MORE':
                                            break;
                                        default:
                                            header('HTTP/1.1 404 Not Found');
                                            return;
                                    }
                                    break;
                                case 'location':
                                    httpKit::request(
                                        EXFE_AUTH_SERVER . "/v3/routex/_inner/breadcrumbs/users/{$user_id}",
                                        ['coordinate' => 'mars'], [[
                                            't'   => $now,
                                            'gps' => [
                                                (float) $objMsg->Latitude,
                                                (float) $objMsg->Longitude,
                                                (float) $objMsg->Precision,
                                            ],
                                        ]], false, false, 3, 3, 'json'
                                    );
                                    return;
                            }
                            $strReturn = $modWechat->packMessage(
                                $identity->external_username,
                                $rtnMessage, $rtnType
                            );
                            if (!$strReturn) {
                                header('HTTP/1.1 500 Internal Server Error');
                                return;
                            }
                            echo $strReturn;
                            ob_end_flush(); // Strange behaviour, will not work
                            flush();        // Unless both are called!
                            // request x title
                            if ("{$event}" === 'click' && "{$objMsg->EventKey}" === 'CREATE_MAP') {
                                setCache("wechat_user_{$identity->external_id}_current_x_id", $cross_id, 60);
                                httpKit::request(
                                    EXFE_GOBUS_SERVER . '/v3/queue/-/POST/'
                                  . base64_url_encode(
                                        SITE_URL . '/v3/bus/requestxtitle/'
                                      . "?cross_id={$cross_id}"
                                      . "&cross_title=" . urlencode($objCross->title)
                                      . "&external_id={$identity->external_id}"
                                    ),
                                    ['update' => 'once', 'ontime' => time() + 7], [],
                                    false, false, 3, 3, 'txt'
                                );
                            }
                            // enable routex
                            if ($cross_id) {
                                httpKit::request(
                                    EXFE_AUTH_SERVER . "/v3/routex/_inner/users/{$user_id}/crosses/{$cross_id}",
                                    null,  [
                                        'save_breadcrumbs' => true,
                                        'after_in_seconds' => 7200,
                                    ], false, false, 3, 3, 'json'
                                );
                            }
                            break;
                        case 'unsubscribe':
                            // check user
                            $user_infos = $modUser->getUserIdentityInfoByIdentityId($identity_id);
                            if (!isset($user_infos['CONNECTED'])) {
                                header('HTTP/1.1 404 Not Found');
                                return;
                            }
                            $modIdentity->revokeIdentity($identity_id);
                            break;
                        default:
                            error_log('Unknow Event');
                    }
                    break;
                case 'text':
                    $strContent = dbescape(trim($objMsg->Content));
                    if (($current_cross_id = $modWechat->getCurrentX($identity->external_id))) {
                        $cross = new stdClass;
                        $cross->id    = $current_cross_id;
                        $cross->title = $strContent;
                        $cross_rs = $crossHelper->editCross($cross, $identity->id);
                        if ($cross_rs) {
                            setCache("wechat_user_{$identity->external_id}_current_x_id", $cross->id, 60);
                            touchCross($cross->id, $identity->connected_user_id);
                            $rtnMessage = "1分钟内回复新名字可更改这张活点地图当前的名字：{$cross->title}";
                        }
                    }
                    if (!$rtnMessage) {
                        switch (strtolower($strContent)) {
                            case 'threshold of the odyssey':
                            case '233':
                                $modIdentity->setLabRat($identity->id);
                                $rtnMessage = "感谢您参与测试。去创建活点地图并邀请朋友们吧！\n产品仍在不断改进，欢迎您的想法反馈。您可以在此发送以“反馈：”开头的消息。";
                        }
                    }
                    if (!$rtnMessage) {
                        $rtnMessage = "【封闭测试中敬请期待…若你有兴趣参与公开测试，请留言。】\n\n" . shell_exec('/usr/local/bin/fortune');
                    }
                    $strReturn = $modWechat->packMessage(
                        $identity->external_username, $rtnMessage
                    );
                    if (!$strReturn) {
                        header('HTTP/1.1 500 Internal Server Error');
                        return;
                    }
                    echo $strReturn;
                    ob_end_flush(); // Strange behaviour, will not work
                    flush();        // Unless both are called!
                    $modWechat->logMessage($identity->id, $strContent);
                    break;
                default:
                    error_log('Unknow MsgType');
            }
        }
    }

}
