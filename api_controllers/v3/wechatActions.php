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
        $objMsg  = $modWechat->unpackMessage(file_get_contents('php://input'));
        // @debug for dianping api {
        // $objMsg = $modWechat->unpackMessage(
        //     '<xml><ToUserName><![CDATA[gh_8c4c8d9d14a7]]></ToUserName>
        //     <FromUserName><![CDATA[onrOgjtQLzpRFl30OGW6LIdb_qaE]]></FromUserName>
        //     <CreateTime>1377452795</CreateTime>
        //     <MsgType><![CDATA[link]]></MsgType>
        //     <Title><![CDATA[食其家(成山路店)]]></Title>
        //     <Description><![CDATA[成山路1993号巴黎春天B1楼]]></Description>
        //     <Url><![CDATA[http://m.api.dianping.com/weixinshop?shopid=4091053]]></Url>
        //     <MsgId>5916114706308792537</MsgId>
        //     </xml>'
        // );
        // }
        $msgType = @strtolower($objMsg->MsgType);
        $event   = @strtolower($objMsg->Event);
        // disabled wechat location events {
        if ($msgType === 'event' && $event === 'location') {
            return;
        }
        // }
        $modUser     = $this->getModelByName('User');
        $modIdentity = $this->getModelByName('Identity');
        $modRoutex   = $this->getModelByName('Routex');
        $crossHelper = $this->getHelperByName('cross');
        $exfeeHelper = $this->getHelperByName('exfee');
        $bot         = $modIdentity->getIdentityById(explode(',', SMITH_BOT)[0]);
        $rtnType     = 'text';
        $rtnMessage  = '';
        $cross       = null;
        $identity    = null;
        // @todo profile 需要更新
        if (($external_id = @$objMsg->FromUserName && @$objMsg->ToUserName
                          ? "{$objMsg->FromUserName}@{$objMsg->ToUserName}" : '')) {
            $identity = $modIdentity->getIdentityByProviderAndExternalUsername(
                'wechat', $external_id
            );
            if ($identity) {
                $identity_id = $identity->id;
                $refreshKey  = "wechat_identity_refresh_{$identity_id}";
                if (!getCache($refreshKey)) {
                    $rawIdentity = $modWechat->getIdentityBy($external_id);
                    if ($rawIdentity && $modIdentity->updateIdentityById(
                        $identity_id, ['name' => $rawIdentity->name]
                    )) {
                        setCache($refreshKey, true, 60 * 60 * 24);
                    }
                }
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
        // check user
        // @todo what about $event === unsubscribe? by @leask
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
        // case event
        switch ($msgType) {
            case 'event':
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
                                        'PicUrl'      => SITE_URL . '/static/img/wechat_routex_welcome@2x.jpg',
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
                                // get exfee ids
                                $exfee_ids  = $exfeeHelper->getExfeeIdByUserid($user_id);
                                // get cross ids
                                $rawCrosses = $crossHelper->getCrossesByExfeeids($exfee_ids);
                                $exfee_ids  = null;
                                $crosses    = [];
                                foreach ($rawCrosses as $rcItem) {
                                    if ((int) $rcItem['state'] !== 2) {
                                        $crosses[$rcItem['id']] = $rcItem;
                                    }
                                }
                                $rawCrosses = null;
                                // get routex cross ids
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
                                    $enabled = null;
                                    if ($curItem >= $total) {
                                        $pageNum = -1;
                                    }
                                    setCache($pageKey, ++$pageNum, 30);
                                    // }
                                }
                                //
                                if ($maps) {
                                    $rtnMessage = [];
                                    $rtnType    = 'news';
                                    foreach ($maps as $map) {
                                        $curExfee = $exfeeHelper->getExfeeById($crosses[$map]['exfee_id']);
                                        if ($csItem['status'] === 0 && !in_array($user_id, $curExfee->hosts)) {
                                            continue;
                                        }
                                        $invitation = $exfeeHelper->getRawInvitationByExfeeIdAndIdentityId(
                                            $crosses[$map]['exfee_id'], $bot->id
                                        );
                                        if (!$invitation) {
                                            $exfee = new Exfee;
                                            $now   = time();
                                            $exfee->id = $crosses[$map]['exfee_id'];
                                            $exfee->invitations = [new Invitation(
                                                0, $bot, $identity, $identity,
                                                'ACCEPTED', 'EXFE', '', $now, $now, false, 0, []
                                            )];
                                            $udeResult = $exfeeHelper->updateExfee(
                                                $exfee, $identity->id, $user_id, false, true
                                            );
                                            if ($udeResult) {
                                                $invitation = $exfeeHelper->getRawInvitationByExfeeIdAndIdentityId(
                                                    $crosses[$map]['exfee_id'], $bot->id
                                                );
                                            }
                                        }
                                        if ($invitation) {
                                            $picUrl = API_URL . "/v3/crosses/{$map}/image?xcode={$invitation['token']}&user_id={$identity->connected_user_id}";
                                            if ($rtnMessage) {
                                                foreach ($curExfee ? $curExfee->invitations : [] as $invItem) {
                                                    if ($invItem->identity->connected_user_id === $user_id
                                                     || $invItem->identity->id                === $identity_id) {
                                                        $picUrl = $invItem->invited_by->avatar['320_320'];
                                                        break;
                                                    }
                                                }
                                            }
                                            $rtnMessage[] = [
                                                'Title'       => $crosses[$map]['title'],
                                                'Description' => $crosses[$map]['description'],
                                                'PicUrl'      => $picUrl,
                                                'Url'         => $modRoutex->getUrl($map, $invitation['token'], $identity) . $debugUrl,
                                            ];
                                        }
                                    }
                                } else {
                                    $rtnMessage = '您现在没有“活点地图”，创建一张并邀请朋友们吧。';
                                }
                                $maps = null;
                                break;
                            case 'CREATE_MAP':
                                if ($modIdentity->isLabRat($identity->id)) {
                                    $rawResult = $modRoutex->createRouteX($identity);
                                    if ($rawResult) {
                                        $cross      = $rawResult['cross'];
                                        $rtnType    = 'news';
                                        $rtnMessage = [[
                                            'Title'       => $cross->title,
                                            'Description' => '开启这张“活点地图” 能互相看到位置和轨迹。长按此消息可转发邀请更多朋友们。',
                                            'PicUrl'      => API_URL . "/v3/crosses/{$cross->id}/wechatimage?identity_id={$identity->id}",
                                            'Url'         => "{$rawResult['url']}{$debugUrl}",
                                        ]];
                                    }
                                } else {
                                    $rtnMessage = "【封闭测试中  非常抱歉】\n若您知道测试口令请回复。";
                                }
                                break;
                            case 'MORE':
                                $rtnType    = 'news';
                                $rtnMessage = [[
                                    'Title'       => '水滴·汇 - 活点地图',
                                    'Description' => '',
                                    'PicUrl'      => SITE_URL . '/static/img/wechat_routex_about.jpg',
                                    'Url'         => SITE_URL . '/wechat/aboutroutex',
                                ], [
                                    'Title'       => '用微信账号登录 水滴·汇',
                                    'Description' => '',
                                    'PicUrl'      => SITE_URL . '/static/img/wechat_appicon@2x.png',
                                    'Url'         => SITE_URL . '/toapp?authenticate',
                                ]];
                        }
                        break;
                    case 'location':
                        httpKit::request(
                            EXFE_AUTH_SERVER . "/v3/routex/_inner/breadcrumbs/users/{$user_id}",
                            ['coordinate' => 'earth'], [[
                                't'   => time(),
                                'gps' => [
                                    (float) $objMsg->Latitude,
                                    (float) $objMsg->Longitude,
                                    (float) $objMsg->Precision,
                                ],
                            ]], false, false, 3, 3, 'json'
                        );
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
                    touchCross($current_cross_id, $user_id);
                    switch (strtolower($strContent)) {
                        case 'rm':
                        case 'remove':
                        case 'del':
                        case 'delete':
                        case 'undo':
                        case 'un do':
                        case '删除':
                        case '不要了':
                        case '干掉':
                        case '取消':
                        case '刪除':
                        case '不要了':
                        case '幹掉':
                        case '取消':
                            $rawCross = $crossHelper->getRawCrossById($current_cross_id);
                            if ($rawCross) {
                                $result = $crossHelper->deleteCrossByCrossIdAndUserId(
                                    $current_cross_id, $user_id
                                );
                                if ($result) {
                                    // 分页过期
                                    $rtnMessage = "“{$rawCross['title']}”已删除。";
                                }
                            }
                            break;
                        default:
                            $cross = new stdClass;
                            $cross->id    = $current_cross_id;
                            $cross->title = $strContent;
                            $udResult = $crossHelper->editCross($cross, $identity->id);
                            if ($udResult) {
                                $invitation = $exfeeHelper->getRawInvitationByCrossIdAndIdentityId(
                                    $cross->id, $bot->id
                                );
                                if ($invitation) {
                                    $rtnType    = 'news';
                                    $rtnMessage = [[
                                        'Title'       => $cross->title,
                                        'Description' => '开启这张“活点地图” 能互相看到位置和轨迹。长按此消息可转发邀请更多朋友们。',
                                        'PicUrl'      => API_URL . "/v3/crosses/{$cross->id}/wechatimage?identity_id={$identity->id}",
                                        'Url'         => $modRoutex->getUrl($cross->id, $invitation['token'], $identity) . $debugUrl,
                                    ]];
                                }
                            }
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
                            $rtnMessage = "调试模式已开启。 🙉🐛";
                            break;
                        case 'debug off':
                            setCache($debugUrlKey, 0);
                            $rtnMessage = "调试模式已关闭。 🙈🐛";
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
            case 'link':
                if (!$modIdentity->isLabRat($identity->id)) {
                    $rtnMessage = "【封闭测试中  非常抱歉】\n若您知道测试口令请回复。";
                    break;
                }
                switch ($msgType) {
                    case 'location':
                        $place = new Place(
                            0, @$objMsg->Label, '', @$objMsg->Location_Y,
                            @$objMsg->Location_X, 'wechat', @$objMsg->MsgId
                        );
                        break;
                    case 'link':
                        $strReg = '/^.*dianping\.com\/.*\?shopid=(.*)$/';
                        $place  = null;
                        if (preg_match($strReg, ($url = @$objMsg->Url))
                        && ($business_id = preg_replace($strReg, '$1', $url))) {
                            $basePlace   = new Place(
                                0, @$objMsg->Title, @$objMsg->Description, '',
                                '', 'dianping', $business_id
                            );
                            $modDianping = $this->getModelByName('Dianping');
                            $place = $modDianping->getSingleBusiness($business_id);
                            $place = $place ?: $basePlace;
                            // @debug { // 点评网 api 通过验证后删除！ by @leaskh
                            $place->title       = $basePlace->title;
                            $place->description = $basePlace->description;
                            // }
                        }
                        if (!$place) {
                            return;
                        }
                }
                $rawResult = $modRoutex->createRouteX($identity, $place);
                if ($rawResult) {
                    $cross      = $rawResult['cross'];
                    $rtnType    = 'news';
                    $rtnMessage = [[
                        'Title'       => $cross->title,
                        'Description' => '开启这张“活点地图” 能互相看到位置和轨迹。长按此消息可转发邀请更多朋友们。',
                        'PicUrl'      => API_URL . "/v3/crosses/{$cross->id}/wechatimage?identity_id={$identity->id}",
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
            setCache("wechat_user_{$identity->external_id}_current_x_id", $cross->id, 60 * 2);
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
                null, ['save_breadcrumbs' => true],
                false, false, 3, 3, 'json'
            );
        }
    }

}
