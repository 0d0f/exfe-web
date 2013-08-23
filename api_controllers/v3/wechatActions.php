<?php

set_time_limit(5);


class wechatActions extends ActionController {

    public function doCallback() {
        $modWechat = $this->getModelByName('wechat');
        $params    = $this->params;
        if (@$params['echostr']) {
            if ($modWechat->valid(
                @$params['signature'],
                @$params['timestamp'],
                @$params['nonce']
            )) {
                echo @$params['echostr'];
            }
            return;
        }
        $modUser     = $this->getModelByName('User');
        $modIdentity = $this->getModelByName('Identity');
        $modRoutex   = $this->getModelByName('Routex');
        $crossHelper = $this->getHelperByName('cross');
        $exfeeHelper = $this->getHelperByName('exfee');
        $objMsg      = $modWechat->unpackMessage(file_get_contents('php://input'));
        $bot         = $modIdentity->getIdentityById(explode(',', SMITH_BOT)[0]);
        $rtnType     = 'text';
        $rtnMessage  = '';
        $cross       = null;
        $identity    = null;
        if (($external_id = @$objMsg->FromUserName && @$objMsg->ToUserName
                          ? "{$objMsg->FromUserName}@{$objMsg->ToUserName}" : '')) {
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
        }
        if (!$identity) {
            header('HTTP/1.1 500 Internal Server Error');
            return;
        }
        // debug url {
        $debugUrlKey = "wechat_debug_{$identity_id}";
        $debugUrl    = getCache($debugUrlKey) ? '&debug=true' : '';
        // }
        $pageKey     = "wechat_routex_paging_{$identity->id}";
        switch (@$objMsg->MsgType) {
            case 'event':
                $event = @strtolower($objMsg->Event);
                if (in_array($event, ['subscribe', 'click', 'location'])) {
                    // check user
                    $user_infos = $modUser->getUserIdentityInfoByIdentityId($identity_id);
                    $user_id    = 0;
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
                }
                switch ($event) {
                    case 'subscribe':
                        $numIdentities = $modUser->getConnectedIdentityCount($user_id);
                        $tutorial_x_id = $modUser->getTutorialXId($user_id, $identity_id);
                        if ($numIdentities === 1 && !$tutorial_x_id) {
                            $cross = $crossHelper->doTutorial($identity);
                            if ($cross) {
                                $invitation = $exfeeHelper->getRawInvitationByCrossIdAndIdentityId(
                                    $cross->id, $bot->id
                                );
                                if ($invitation) {
                                    $rtnType    = 'news';
                                    $rtnMessage = [[
                                        'Title'       => '欢迎使用“活点地图”',
                                        'Description' => '',
                                        'PicUrl'      => SITE_URL . '/static/img/routex_welcome@2x.jpg',
                                        'Url'         => $modRoutex->getUrl($cross->id, $invitation['token'], $identity) . $debugUrl,
                                    ]];
                                }
                            }
                        }
                        if (!$rtnMessage) {
                            $rtnMessage = "嗨，{$identity->name}！欢迎再次开启“活点地图”。";
                        }
                        break;
                    case 'click':
                        switch ($objMsg->EventKey) {
                            case 'LIST_MAPS':
                                $exfee_id_list = $exfeeHelper->getExfeeIdByUserid($user_id);
                                $cross_list    = $crossHelper->getCrossesByExfeeIdList(
                                    $exfee_id_list, null, null, false, $user_id
                                );
                                $crosses       = [];
                                foreach ($cross_list as $i => $csItem) {
                                    if ($csItem->attribute['state'] === 'deleted'
                                    || ($csItem->attribute['state'] === 'draft'
                                     && !in_array($user_id, $csItem->exfee->hosts))) {
                                    } else {
                                        $crosses[$csItem->id] = $csItem;
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
                                    // paging {
                                    $pageSize = 4;
                                    $pageNum  = (int) getCache($pageKey);
                                    $enabled  = [];
                                    foreach ($rawMaps as $rI => $rItem) {
                                        if ($rItem['enable']) {
                                            $enabled[] = $rItem['cross_id'];
                                        }
                                    }
                                    $rawMaps = null;
                                    $curItem = $pageSize * $pageNum + $pageSize;
                                    $total   = sizeof($enabled);
                                    $maps    = array_slice($enabled, $pageNum * $pageSize, $pageSize);
                                    if ($curItem >= $total) {
                                        $pageNum = -1;
                                    }
                                    setCache($pageKey, ++$pageNum, 30);
                                    // }
                                }
                                if ($maps) {
                                    $rtnMessage = [];
                                    $rtnType    = 'news';
                                    foreach ($maps as $map) {
                                        $invitation = $exfeeHelper->getRawInvitationByCrossIdAndIdentityId(
                                            $map, $bot->id
                                        );
                                        if (!$invitation) {
                                            $exfee = new Exfee;
                                            $now   = time();
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
                                                    $map, $bot->id
                                                );
                                            }
                                        }
                                        if ($invitation) {
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
                                                'Url'         => $modRoutex->getUrl($map, $invitation['token'], $identity) . $debugUrl,
                                            ];
                                        }
                                    }
                                } else {
                                    $rtnMessage = '您现在没有“活点地图”，创建一张并邀请朋友们吧。';
                                }
                                break;
                            case 'CREATE_MAP':
                                if ($modIdentity->isLabRat($identity->id)) {
                                    $rawResult = $modRoutex->createRouteX($identity);
                                    if ($rawResult) {
                                        $cross      = $rawResult['cross'];
                                        $rtnType    = 'news';
                                        $rtnMessage = [[
                                            'Title'       => $cross->title,
                                            'Description' => '开启这张“活点地图” 就能互相看到位置和轨迹。或长按转发邀请更多朋友们。',
                                            'PicUrl'      => '',
                                            'Url'         => "{$rawResult['url']}{$debugUrl}",
                                        ]];
                                    }
                                } else {
                                    $rtnMessage = "【封闭测试中  非常抱歉】\n若您知道测试口令请回复。";
                                }
                                break;
                            case 'MORE':
                        }
                        break;
                    case 'location':
                        // @debug {
                        // httpKit::request(
                        //     EXFE_AUTH_SERVER . "/v3/routex/_inner/breadcrumbs/users/{$user_id}",
                        //     ['coordinate' => 'earth'], [[
                        //         't'   => time(),
                        //         'gps' => [
                        //             (float) $objMsg->Latitude,
                        //             (float) $objMsg->Longitude,
                        //             (float) $objMsg->Precision,
                        //         ],
                        //     ]], false, false, 3, 3, 'json'
                        // );
                        // }
                        break;
                    case 'unsubscribe':
                        $user_infos = $modUser->getUserIdentityInfoByIdentityId($identity_id);
                        if (isset($user_infos['CONNECTED'])) {
                            $modIdentity->revokeIdentity($identity_id);
                        }
                }
                break;
            case 'text':
                $strContent = dbescape(trim($objMsg->Content));
                if (($current_cross_id = $modWechat->getCurrentX($identity->external_id))) {
                    $updCross = new stdClass;
                    $updCross->id    = $current_cross_id;
                    $updCross->title = $strContent;
                    $udResult = $crossHelper->editCross($updCross, $identity->id);
                    if ($udResult) {
                        setCache("wechat_user_{$identity->external_id}_current_x_id", $updCross->id, 60);
                        touchCross($updCross->id, $identity->connected_user_id);
                        $rtnMessage = "1分钟内回复新名字可更改这张活点地图当前的名字：{$updCross->title}";
                    }
                } else {
                    switch (strtolower($strContent)) {
                        case 'threshold of the odyssey':
                        case '233':
                            $modIdentity->setLabRat($identity->id);
                            $rtnMessage = "感谢您参与测试。去创建活点地图并邀请朋友们吧！\n产品仍在不断改进，欢迎您的想法反馈。您可以在此发送以“反馈：”开头的消息。";
                            break;
                        case 'debug on':
                            setCache($debugUrlKey, 1);
                            $rtnMessage = "调试模式已开启。";
                            break;
                        case 'debug off':
                            setCache($debugUrlKey, 0);
                            $rtnMessage = "调试模式已关闭。";
                            break;
                        case 'think different':
                            $rtnMessage = 'Here’s to the crazy ones. The rebels. The troublemakers. The ones who see things differently. While some may see them as the crazy ones, we see genius. Because the people who are crazy enough to think they can change the world, are the ones who do.';
                    }
                    if (!$rtnMessage) {
                        $rtnMessage = "【封闭测试中敬请期待…若你有兴趣参与公开测试，请留言。】\n\n" . shell_exec('/usr/local/bin/fortune');
                    }
                }
                $modWechat->logMessage($identity->id, $strContent);
                break;
            case 'location':
                if (!$modIdentity->isLabRat($identity->id)) {
                    $rtnMessage = "【封闭测试中  非常抱歉】\n若您知道测试口令请回复。";
                    break;
                }
                $rawResult = $modRoutex->createRouteX($identity, new Place(
                    0, @$objMsg->Label, '', @$objMsg->Location_Y,
                    @$objMsg->Location_X, 'wechat', @$objMsg->MsgId
                ));
                if ($rawResult) {
                    $cross      = $rawResult['cross'];
                    $rtnType    = 'news';
                    $rtnMessage = [[
                        'Title'       => $rawResult['cross']->title,
                        'Description' => '开启这张“活点地图” 就能互相看到位置和轨迹。或长按转发邀请更多朋友们。',
                        'PicUrl'      => '',
                        'Url'         => "{$rawResult['url']}{$debugUrl}",
                    ]];
                }
        }
        $strReturn = $modWechat->packMessage(
            $identity->external_username, $rtnMessage, $rtnType
        );
        if (!$strReturn) {
            return;
        }
        echo $strReturn;
        ob_end_flush(); // Strange behaviour, will not work
        flush();        // Unless both are called!
        if (DEBUG && VERBOSE_LOG) {
            error_log("RESPONSE_TO_WECHAT: {$strReturn}");
        }
        // call services
        if ($cross) {
            // reset pagine
            setCache($pageKey, 0, 1);
            // request x title
            setCache("wechat_user_{$identity->external_id}_current_x_id", $cross->id, 60);
            httpKit::request(
                EXFE_GOBUS_SERVER . '/v3/queue/-/POST/'
              . base64_url_encode(
                    SITE_URL . '/v3/bus/requestxtitle/'
                  . "?cross_id={$cross->id}"
                  . "&cross_title=" . urlencode($cross->title)
                  . "&external_id={$identity->external_id}"
                ),
                ['update' => 'once', 'ontime' => time() + 7], [],
                false, false, 3, 3, 'txt'
            );
            // enable routex
            httpKit::request(
                EXFE_AUTH_SERVER
              . "/v3/routex/_inner/users/{$user_id}/crosses/{$cross->id}",
                null, ['save_breadcrumbs' => true, 'after_in_seconds' => 7200],
                false, false, 3, 3, 'json'
            );
        }
    }

}
