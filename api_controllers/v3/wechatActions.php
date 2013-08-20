<?php

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
            $rawInput = file_get_contents('php://input');
            $objMsg   = $modWechat->unpackMessage($rawInput);
            $now      = time();
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
                    // 500
                    return;
                }
                $identity_id = $modIdentity->addIdentity([
                    'provider'          => $rawIdentity->provider,
                    'external_id'       => $rawIdentity->external_id,
                    'name'              => $rawIdentity->name,
                    'external_username' => $rawIdentity->external_username,
                    'avatar'            => $rawIdentity->avatar,
                    'avatar_filename'   => $rawIdentity->avatar_filename
                ]);
                $identity    = $modIdentity->getIdentityById($identity_id);
            }
            if (!$identity) {
                // 500
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
                            if (isset($user_infos['CONNECTED'])) {
                                $user_id = $user_infos['CONNECTED'][0]['user_id'];
                            } else if (isset($user_infos['REVOKED'])) {
                                $user_id = $user_infos['REVOKED'][0]['user_id'];
                                $modUser->setUserIdentityStatus($user_id, $identity_id, 3);
                            } else {
                                $user_id  = $modUser->addUser();
                                $modUser->setUserIdentityStatus($user_id, $identity_id, 3);
                                $identity = $modIdentity->getIdentityById($identity_id);
                                // $modIdentity->sendVerification(
                                //     'Welcome', $identity, '', false, $identity->name ?: ''
                                // );
                            }
                            if (!$user_id) {
                                // 500
                                return;
                            }
                            $modIdentity = $this->getModelByName('Identity');
                            $crossHelper = $this->getHelperByName('cross');
                            $exfeeHelper = $this->getHelperByName('exfee');
                            $rtnMessage  = '';
                            $rtnType     = 'text';
                            $idBot = explode(',', SMITH_BOT)[0];
                            $bot   = $modIdentity->getIdentityById($idBot);
                            switch ($event) {
                                case 'subscribe':
                                    $rtnMessage = "【封闭测试中敬请期待…若你有兴趣参与公开测试，请留言。】\n Welcome {$identity->name}！";
                                    break;
                                case 'click':
                                    switch ($objMsg->EventKey) {
                                        case 'LIST_MAPS':
                                            $exfee_id_list = $exfeeHelper->getExfeeIdByUserid($user_id);
                                            $cross_list    = $crossHelper->getCrossesByExfeeIdList($exfee_id_list, null, null, false, $user_id);
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
                                                        // 500
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
                                                $rtnMessage = '您目前没有活点地图，立刻创建一个？';
                                            }
                                            break;
                                        case 'CREATE_MAP':
                                            if (!$modIdentity->isLabRat($identity->id)) {
                                                $rtnMessage = '你还不是内测用户，就不让你用！';
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
                                                // 500
                                                return;
                                            }
                                            // enable routex
                                            $rawMaps = httpKit::request(
                                                EXFE_AUTH_SERVER . "/v3/routex/_inner/users/{$user_id}/crosses/{$cross_id}",
                                                null,  [
                                                    'save_breadcrumbs' => true,
                                                    'after_in_seconds' => 7200,
                                                ], false, false, 3, 3, 'json'
                                            );
                                            // get invitation
                                            $invitation = $exfeeHelper->getRawInvitationByCrossIdAndIdentityId(
                                                $cross_id, $idBot
                                            );
                                            if (!$invitation) {
                                                // 500
                                                return;
                                            }
                                            // returns
                                            touchCross($cross_id, $identity->connected_user_id);
                                            $rtnMessage = '<a href="'
                                                        . SITE_URL
                                                        . "/!{$cross_id}/routex?xcode={$invitation['token']}&via={$identity->external_username}@{$identity->provider}\">{$objCross->title} "
                                                        . SITE_URL
                                                        . "/!{$cross_id}/routex?xcode="
                                                        . substr($invitation['token'], 0, 3)
                                                        . "…</a> \n点链接开启这张活点地图。长按此消息转发给朋友们，就能互相看到所处方位。";
                                            break;
                                        case 'HELP_01':
                                            break;
                                        case 'HELP_02':
                                            break;
                                        default:
                                            // 404
                                            return;
                                    }
                                    break;
                                case 'location':
                                    httpKit::request(
                                        EXFE_AUTH_SERVER . "/v3/routex/_inner/breadcrumbs/users/{$user_id}",
                                        ['coordinate' => 'earth'], [[
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
                                // 500
                                return;
                            }
                            echo $strReturn;
                            break;
                        case 'unsubscribe':
                            // check user
                            $user_infos = $modUser->getUserIdentityInfoByIdentityId($identity_id);
                            if (!isset($user_infos['CONNECTED'])) {
                                // 400
                                return;
                            }
                            $modIdentity->revokeIdentity($identity_id);
                            break;
                        default:
                            error_log('Unknow Event');
                    }
                    break;
                case 'text':
                    switch ($objMsg->Content) {
                        case '233':
                            $modIdentity->setLabRat($identity->id);
                            $strReturn = $modWechat->packMessage(
                                $identity->external_username, '你现在能创建活点地图了！'
                            );
                            break;
                        default:
                            $strReturn = $modWechat->packMessage(
                                $identity->external_username, "【封闭测试中敬请期待…若你有兴趣参与公开测试，请留言。】\n" . shell_exec('/usr/local/bin/fortune ')
                            );
                    }
                    if (!$strReturn) {
                        // 500
                        return;
                    }
                    echo $strReturn;
                    break;
                default:
                    error_log('Unknow MsgType');
            }
        }
    }

}
