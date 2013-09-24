<?php

require_once dirname(dirname(__FILE__)) . '/../lib/httpkit.php';


class BusActions extends ActionController {

    public function doUpdateIdentity() {
        // get raw data
        $id                = isset($_POST['id'])                ? intval($_POST['id'])                                  : null;
        $provider          = isset($_POST['provider'])          ? dbescape($_POST['provider'])          : null;
        $external_id       = isset($_POST['external_id'])       ? dbescape($_POST['external_id'])       : null;
        $name              = isset($_POST['name'])              ? dbescape($_POST['name'])              : '';
        $nickname          = isset($_POST['nickname'])          ? dbescape($_POST['nickname'])          : '';
        $bio               = isset($_POST['bio'])               ? dbescape($_POST['bio'])               : '';
        $avatar_filename   = isset($_POST['avatar_filename'])   ? dbescape($_POST['avatar_filename'])   : ''; // @todo submit by array @leask to @googollee
        $external_username = isset($_POST['external_username']) ? dbescape($_POST['external_username']) : '';
        // check data
        if (!$id || !$provider || !$external_id) {
            $this->jsonError(500, 'identity_error');
            return;
        }
        // do update
        $modIdentity = $this->getModelByName('Identity');
        $id = $modIdentity->updateIdentityByGobus($id, [
            'provider'          => $provider,
            'external_id'       => $external_id,
            'name'              => $name,
            'nickname'          => $nickname,
            'bio'               => $bio,
            'avatar_filename'   => $avatar_filename,
            'external_username' => $external_username,
        ]);
        // return
        if (!$id) {
            $this->jsonError(500, 'internal_server_error');
            return;
        }
        $this->jsonResponse(['identity_id' => $id]);
    }


    public function doRecipients() {
        $identity_id = @strtolower(trim($_GET['identity_id']));
        if (!$identity_id) {
            $this->jsonError(400, 'error_identity_id');
            return;
        }
        $modUser     = $this->getModelByName('User');
        $modIdentity = $this->getModelByName('Identity');
        $modDevice   = $this->getModelByName('Device');
        $modTime     = $this->getModelByName('Time');
        $strReg      = '/(.*)@([^\@]*)/';
        $external_username = preg_replace($strReg, '$1', $identity_id);
        $provider          = preg_replace($strReg, '$2', $identity_id);
        $identity    = $modIdentity->getIdentityByProviderAndExternalUsername(
            $provider, $external_username
        );
        if (!$identity) {
            $this->jsonError(404, 'recipient_not_found');
            return;
        }
        $user_id     = $modUser->getUserIdByIdentityId($identity->id);
        $recipients  = [];
        if ($user_id) {
            $objUser = $modUser->getUserById($user_id);
            if (!$objUser || !$objUser->identities) {
                $this->jsonError(404, 'recipient_not_found');
                return;
            }
            $devices = $modDevice->getDevicesByUserid(
                $user_id, $objUser->identities[0]
            );
            foreach (array_merge($objUser->identities, $devices) as $idItem) {
                $recipients[] = new Recipient(
                    $idItem->id,
                    $idItem->connected_user_id,
                    $idItem->name,
                    $idItem->auth_data ?: '',
                    $modTime->getDigitalTimezoneBy($objUser->timezone),
                    '', // cross invitation token
                    $objUser->locale,
                    $idItem->provider,
                    $idItem->external_id,
                    $idItem->external_username
                );
            }
        } else {
             $recipients[] = new Recipient(
                $identity->id,
                $identity->connected_user_id,
                $identity->name,
                $identity->auth_data ?: '',
                $identity->timezone,
                '', // cross invitation token
                $identity->locale,
                $identity->provider,
                $identity->external_id,
                $identity->external_username
            );
        }
        if (!$recipients) {
            $this->jsonError(404, 'recipient_not_found');
            return;
        }
        $this->jsonResponse($recipients);
    }


    public function doGather() {
        // init models
        $modIdentity = $this->getModelByName('identity');
        $modUser     = $this->getModelByName('User');
        $crossHelper = $this->getHelperByName('cross');

        // grep inputs
        $cross_str = @file_get_contents('php://input');
        $cross = json_decode($cross_str);
        $chkCross = $crossHelper->validateCross($cross);
        if ($chkCross['error']
        || !$cross->exfee
        || !$cross->exfee->invitations) {
            $this->jsonError(400, 'cross_error', $chkCross['error'][0]);
            return;
        }
        $cross = $chkCross['cross'];

        // get host identity
        $identity_id = 0;
        $identity = $modIdentity->getIdentityByProviderAndExternalUsername(
            $cross->by_identity->provider,
            $cross->by_identity->external_username
        );
        if ($identity) {
            $identity_id = $identity->id;
        } else {
            $identity_id = $modIdentity->addIdentity([
                'provider'          => $cross->by_identity->provider,
                'external_id'       => $cross->by_identity->external_id,
                'name'              => $cross->by_identity->name,
                'external_username' => $cross->by_identity->external_username,
                'avatar'            => $cross->by_identity->avatar,
                'avatar_filename'   => $cross->by_identity->avatar_filename
            ]);
            $identity    = $modIdentity->getIdentityById($identity_id);
        }
        if (!$identity) {
            $this->jsonError(500, 'identity_error');
            return;
        }

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
            $modIdentity->sendVerification(
                'Welcome', $identity, '', false, $identity->name ?: ''
            );
        }
        if (!$user_id) {
            $this->jsonError(500, 'user_error');
            return;
        }

        // gather
        if (!isset($cross->attribute)) {
            $cross->attribute = new stdClass;
        }
        if (!isset($cross->attribute->state)) {
            $cross->attribute->state = 'published';
        }
        $gthResult = $crossHelper->gatherCross($cross, $identity_id, $user_id);
        $cross_id = @$gthResult['cross_id'];
        if (!$cross_id) {
            $this->jsonError(500, 'gathering_error');
            return;
        }

        // publish on time {
        if ($cross->attribute->state !== 'published') {
            httpKit::request(
                EXFE_GOBUS_SERVER . '/v3/queue/-/POST/'
              . base64_url_encode(SITE_URL . '/v3/bus/publishx'),
                ['update' => 'once', 'ontime' => time() + 60 * 10],
                [
                    'cross_id'    => $cross_id,
                    'exfee_id'    => $gthResult['exfee_id'],
                    'user_id'     => $user_id,
                    'identity_id' => $identity_id,
                ],
                false, false, 3, 3, 'json'
            );
        }
        // }

        $rspData = $crossHelper->getCross($cross_id, true);
        if (!$rspData->updated) {
            $rspData->updated = new stdClass;
        }
        touchCross($cross_id, $user_id);

        if (@$gthResult['over_quota']) {
            $this->jsonResponse($rspData, '206', [
                'code'    => -1,
                'type'    => @$gthResult['over_hard_quota']
                          ? 'exfee_over_hard_quota'
                          : 'exfee_over_soft_quota',
                'message' => [
                    'exfee_soft_quota' => EXFEE_QUOTA_SOFT_LIMIT,
                    'exfee_hard_quota' => EXFEE_QUOTA_HARD_LIMIT,
                ]
            ]);
            return;
        }

        $this->jsonResponse($rspData);
    }


    public function doXUpdate() {
        // init models
        $modExfee    = $this->getModelByName('Exfee');
        $modIdentity = $this->getModelByName('Identity');
        $modUser     = $this->getModelByName('User');
        $crossHelper = $this->getHelperByName('cross');

        // grep inputs
        $args_str = @file_get_contents('php://input');
        $args = json_decode($args_str);
        if (!$args
         || !is_object($args)
         || (!isset($args->cross_id) && !isset($args->exfee_id))) {
            $this->jsonError(500, 'cross_error');
            return;
        }
        if (isset($args->cross)) {
            if (!isset($args->by_identity)) {
                $this->jsonError(500, 'cross_error');
                return;
            }
        } else {
            $exfee_id = isset($args->exfee_id)
                      ? (int) $args->exfee_id
                      : (int) $modExfee->getExfeeIdByCrossId((int) $args->cross_id);
            $modExfee->updateExfeeTime($exfee_id);
            if (isset($args->cross_id)) {
                $modRoutex = $this->getModelByName('Routex');
                $modRoutex->delRoutexStatusCache($args->cross_id);
            }
            $this->jsonResponse(new stdClass);
            return;
        }
        $chkCross = $crossHelper->validateCross($args->cross);
        if ($chkCross['error']) {
            $this->jsonError(500, 'cross_error', $chkCross['error'][0]);
            return;
        }
        $cross = $chkCross['cross'];

        // get identity
        $identity_id = 0;
        $identity = $modIdentity->getIdentityByProviderAndExternalUsername(
            $args->by_identity->provider,
            $args->by_identity->external_username
        );
        if ($identity) {
            $identity_id = $identity->id;
        } else {
            $identity_id = $modIdentity->addIdentity([
                'provider'          => $cross->by_identity->provider,
                'external_id'       => $cross->by_identity->external_id,
                'name'              => $cross->by_identity->name,
                'external_username' => $cross->by_identity->external_username,
                'avatar'            => $cross->by_identity->avatar,
                'avatar_filename'   => $cross->by_identity->avatar_filename,
            ]);
            $identity    = $modIdentity->getIdentityById($identity_id);
        }
        if (!$identity) {
            $this->jsonError(500, 'identity_error');
            return;
        }

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
            $modIdentity->sendVerification(
                'Welcome', $identity, '', false, $identity->name ?: ''
            );
        }
        if (!$user_id) {
            $this->jsonError(500, 'user_error');
            return;
        }

        // check user identities in exfee
        if (isset($args->cross_id)) {
            $args->exfee_id = $modExfee->getExfeeIdByCrossId($args->cross_id);
        } else if (isset($args->exfee_id)) {
            $args->cross_id = $modExfee->getCrossIdByExfeeId($args->exfee_id);
        }
        $cross->id = $args->cross_id;
        if (isset($cross->exfee)) {
            $cross->exfee->id = $args->exfee_id;
        }
        $old_exfee = $modExfee->getExfeeById($args->exfee_id);
        if (!$old_exfee) {
            $this->jsonError(404, 'cross_not_found');
            return;
        }
        $rsvp_priority = array(
            'ACCEPTED', 'INTERESTED', 'NORESPONSE', 'DECLINED', 'NOTIFICATION'
        );
        $by_identity   = null;
        $isHost        = false;
        foreach ($rsvp_priority as $priority) {
            if ($by_identity) {
                break;
            }
            foreach ($old_exfee->invitations as $invitation) {
                if ($invitation->response == $priority
                 && ($invitation->identity->connected_user_id === $user_id
                  || $invitation->identity->id                === $identity_id)) {
                    $by_identity = $invitation->identity;
                    $isHost = $invitation->host ?: $isHost;
                    break;
                }
            }
        }
        if (!$by_identity) {
            $this->jsonError(400, 'not_authorized');
            return;
        }

        // get current cross
        $curCross = $crossHelper->getCross($cross->id);
        $draft    = isset($curCross->attribute)
                 && isset($curCross->attribute['state'])
                 && $curCross->attribute['state'] === 'draft';

        if ($draft && !$isHost) {
            $this->jsonError(400, 'not_authorized');
            return;
        }

        // update crosss
        $cross->exfee_id = $old_exfee->id; // @todo: exfee_id in missing!?
        $cross_rs  = $crossHelper->editCross($cross, $by_identity->id);
        $cross_id  = $cross_rs && $cross_rs['cross_id'] ? $cross_rs['cross_id'] : 0;
        $rawResult = true;
        if (isset($cross->widgets) && is_array($cross->widgets)) {
            foreach ($cross->widgets as $widget) {
                if ($widget->type === 'routex') {
                    $modWidget = $this->getModelByName('Widget');
                    $modWidget->updateByCrossIdAndType($cross_id, $widget->type, $by_identity->id);
                    break;
                }
            }
        }
        if (isset($cross->exfee)) {
            $timezone  = @$cross->time->begin_at->timezone
                      ?: @$curCross->time->begin_at->timezone;
            $rawResult = $modExfee->updateExfee($cross->exfee, $by_identity->id, $user_id, true, $draft, true, $timezone);
        }
        if (!$cross_id || !$rawResult) {
            $this->jsonError(500, 'internal_server_error');
            return;
        }
        $hlpCross = $this->getHelperByName('Cross');
        $rtResult = [
            'cross_id' => $cross_id,
            'exfee_id' => $rawResult['exfee_id'],
            'cross'    => $hlpCross->getCross($cross_id, true)
        ];
        touchCross($cross_id, $user_id);
        if ($rawResult['soft_quota'] || $rawResult['hard_quota']) {
            $this->jsonResponse($rtResult, 206, [
                'code'    => -1,
                'type'    => @$rawResult['hard_quota']
                           ? 'exfee_over_hard_quota'
                           : 'exfee_over_soft_quota',
                'message' => [
                    'exfee_soft_quota' => EXFEE_QUOTA_SOFT_LIMIT,
                    'exfee_hard_quota' => EXFEE_QUOTA_HARD_LIMIT,
                ]
            ]);
            return;
        }
        $this->jsonResponse($rtResult);
    }


    public function doPublishx() {
        $modCross = $this->getModelByName('Cross');
        $modExfee = $this->getModelByName('Exfee');
        $modQueue = $this->getModelByName('Queue');
        $hlpCross = $this->getHelperByName('Cross');
        $params   = $this->params;
        $args_str = @file_get_contents('php://input');
        $args     = json_decode($args_str);
        if (!$args
         || !$args->cross_id
         || !$args->exfee_id
         || !$args->user_id
         || !$args->identity_id) {
            $this->jsonError(500, 'input_error');
            return;
        }
        if (!$modCross->getDraftStatusBy($args->cross_id)) {
            $this->jsonError(500, 'internal_server_error');
            return;
        }
        $hostIds = $modExfee->getHostIdentityIdsByExfeeId($args->exfee_id);
        if (!$hostIds || !in_array($args->identity_id, $hostIds)) {
            $this->jsonError(500, 'internal_server_error');
            return;
        }
        if (!$modCross->publishCrossBy($args->cross_id)) {
            $this->jsonError(500, 'internal_server_error');
            return;
        }
        $cross = $hlpCross->getCross($args->cross_id, true);
        if (!$cross) {
            $this->jsonError(500, 'internal_server_error');
            return;
        }
        $modQueue->despatchInvitation(
            $cross, $cross->exfee, $args->user_id, $args->identity_id
        );
        $this->jsonResponse(['cross_id' => $cross->id]);
    }


    public function doPostConversation() {
        // init model
        $modUser         = $this->getModelByName('User');
        $modIdentity     = $this->getModelByName('Identity');
        $modCnvrstn      = $this->getModelByName('Conversation');
        $hlpCross        = $this->getHelperByName('Cross');

        // grep input
        $cross_id        = (int)$_POST['cross_id'];
        $iom             = trim($_POST['iom']);
        $provider        = trim($_POST['provider']);
        $external_id     = trim($_POST['external_id']);
        $content         = trim($_POST['content']);
        $exclude         = @$_POST['exclude'] ?: '';
        $time            = strtotime($_POST['time']);
        if ((!$cross_id && !$iom) || !$provider || !$external_id || !$content || !$time) {
            $this->jsonError(500, 'input_error');
            return;
        }

        // get identity
        $identity_id = 0;
        $identity = $modIdentity->getIdentityByProviderAndExternalUsername(
            $provider, $external_id
        );
        if ($identity) {
            $identity_id = $identity->id;
        } else {
            $identity_id = $modIdentity->addIdentity([
                'provider'          => $provider,
                'external_id'       => $external_id,
                'external_username' => $external_id,
            ]);
            $identity    = $modIdentity->getIdentityById($identity_id);
        }
        if (!$identity) {
            $this->jsonError(500, 'identity_error');
            return;
        }

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
            $modIdentity->sendVerification(
                'Welcome', $identity, '', false, $identity->name ?: ''
            );
        }
        if (!$user_id) {
            $this->jsonError(500, 'user_error');
            return;
        }

        // get cross id by iom
        if (!$cross_id) {
            $objCurl = curl_init();
            curl_setopt($objCurl, CURLOPT_URL, IOM_URL . "/iom/{$user_id}/{$iom}");
            curl_setopt($objCurl, CURLOPT_HEADER, 0);
            curl_setopt($objCurl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($objCurl, CURLOPT_CONNECTTIMEOUT, 3);
            $curlResult = curl_exec($objCurl);
            curl_close($objCurl);
            $cross_id = (int) $curlResult;
        }
        // get cross
        $cross = $hlpCross->getCross($cross_id, true);
        if (!$cross) {
            $this->jsonError(500, 'cross_error');
            return;
        }

        // check user identities in cross
        $rsvp_priority = array(
            'ACCEPTED', 'INTERESTED', 'NORESPONSE', 'DECLINED', 'NOTIFICATION'
        );
        $by_identity   = null;
        foreach ($rsvp_priority as $priority) {
            if ($by_identity) {
                break;
            }
            foreach ($cross->exfee->invitations as $invitation) {
                if ($invitation->response === $priority
                 && ($invitation->identity->connected_user_id === $user_id
                  || $invitation->identity->id                === $identity_id)) {
                    $by_identity = $invitation->identity;
                    break;
                }
            }
        }
        if (!$by_identity) {
            $this->jsonError(500, 'internal_server_error');
            return;
        }

        // add post to conversation
        $post     = new Post(0, null, $content, $cross->exfee->id, 'exfee');
        $post->by_identity_id = $by_identity->id;
        $rstPost  = $modCnvrstn->addPost($post, $time);
        if (!($post_id = $rstPost['post_id'])) {
            $this->jsonError(500, 'internal_server_error');
            return;
        }
        // get the new post
        $post     = $modCnvrstn->getPostById($post_id);
        // call Gobus {
        $modQueue = $this->getModelByName('Queue');
        if ($exclude) {
            $arrExclude = [];
            foreach (explode(',', $exclude) ?: [] as $rawIdentity) {
                $external_username = preg_replace('/^(.*)@[^@]*$/', '$1', $rawIdentity);
                $provider          = preg_replace('/^.*@([^@]*)$/', '$1', $rawIdentity);
                if ($external_username && $provider) {
                    $excInvitation = new stdClass();
                    $excInvitation->identity = new stdClass();
                    $excInvitation->identity->external_username = strtolower($external_username);
                    $excInvitation->identity->provider          = strtolower($provider);
                    $arrExclude[]  = $excInvitation;
                }
            }
        }
        $modQueue->despatchConversation(
            $cross, $post, $by_identity->connected_user_id,
            $post->by_identity_id, $arrExclude
        );
        // }
        $modExfee = $this->getModelByName('exfee');
        $modExfee->updateExfeeTime($cross->exfee->id);
        // return
        touchCross($cross_id, $user_id);
        $this->jsonResponse($post);
    }


    public function doAddFriends() {
        // get model
        $modUser     = $this->getModelByName('User');
        $modRelation = $this->getModelByName('Relation');
        // get raw data
        if (!($str_args = @file_get_contents('php://input'))) {
            $this->jsonError(500, 'no_input');
            if (DEBUG) {
                error_log('No input!');
            }
            return;
        }
        // decode json
        $obj_args = json_decode($str_args);
        if (!$obj_args || !$obj_args->user_id || !is_array($obj_args->identities)) {
            $this->jsonError(500, 'json_error');
            if (DEBUG) {
                error_log('JSON error!');
            }
            return;
        }
        // save relations
        $error = false;
        foreach ($obj_args->identities as $identity) {
            if (!$modRelation->saveExternalRelations($obj_args->user_id, $identity)) {
                $error = true;
            }
        }
        if ($error) {
            $this->jsonError(500, 'internal_server_error');
            if (DEBUG) {
                error_log('Save error!');
            }
            return;
        }
        // build identities indexes
        if (!$modRelation->buildIdentitiesIndexes($obj_args->user_id)) {
            $this->jsonError(500, 'internal_server_error');
            if (DEBUG) {
                error_log('Index error!');
            }
            return;
        }
        // return
        $this->jsonResponse(['user_id' => $obj_args->user_id]);
    }


    public function doNotificationCallback() {
        // get model
        $modIdentity = $this->getModelByName('Identity');
        $modDevice   = $this->getModelByName('Device');
        // get raw data
        if (!($strArgs = @file_get_contents('php://input'))) {
            $this->jsonError(500, 'no_input');
            if (DEBUG) {
                error_log('No input!');
            }
            return;
        }
        // decode json
        $objArgs       = (array) json_decode($strArgs);
        $rawIdentityId = @$objArgs['identity_id'];
        $strError      = @$objArgs['error'];
        if (!$rawIdentityId
         || !($external_username = preg_replace('/^(.*)@[^@]*$/', '$1', $rawIdentityId))
         || !($provider          = preg_replace('/^.*@([^@]*)$/', '$1', $rawIdentityId))) {
            $this->jsonError(500, 'json_error');
            if (DEBUG) {
                error_log('JSON error!');
            }
            return;
        }
        // rawJob
        switch ($provider) {
            case 'email':
            case 'phone':
            case 'twitter':
            case 'facebook':
            case 'google':
            case 'wechat':
                $identity_id = $modIdentity->getIdentityByProviderAndExternalUsername(
                    $provider, $external_username, true
                );
                if (!$identity_id) {
                    $this->jsonError(500, 'identity_not_found');
                    if (DEBUG) {
                        error_log('Identity not found!');
                    }
                    return;
                }
                $modIdentity->updateIdentityById($identity_id, [
                    'unreachable' => strlen($strError) ? 1 : 0
                ]);
                break;
            case 'iOS':
            case 'Android':
                $modDevice->updateDeviceReachableByUdid(
                    $external_username,
                    $provider, $strError
                );
                break;
            default:
                $this->jsonError(500, 'unknow_provide');
                if (DEBUG) {
                    error_log('Unknow provider!');
                }
                return;
        }
        // return
        $this->jsonResponse($rawIdentityId);
    }


    public function doRevokeIdentity() {
        // get model
        $modIdentity = $this->getModelByName('Identity');
        // get raw data
        if (!($str_args = @file_get_contents('php://input'))) {
            $this->jsonError(500, 'no_input');
            if (DEBUG) {
                error_log('No input!');
            }
            return;
        }
        // decode json
        $identity = (array) json_decode($str_args);
        $identity['id']                = isset($identity['id'])                ? (int) $identity['id']                                    : 0;
        $identity['provider']          = isset($identity['provider'])          ? dbescape($identity['provider'])          : '';
        $identity['external_username'] = isset($identity['external_username']) ? dbescape($identity['external_username']) : '';
        if (!$identity['id']) {
            if ($identity['provider'] && $identity['external_username']) {
                // get identity id
                $identity['id'] = $modIdentity->getIdentityByProviderAndExternalUsername(
                    $identity['provider'], $identity['external_username'], true
                );
            }
        }
        if ($identity['id']) {
            // revoke
            $modIdentity->revokeIdentity($identity['id']);
            // get identity
            $objIdentity = $modIdentity->getIdentityById($identity['id']);
            if ($objIdentity) {
                $this->jsonResponse($objIdentity);
                return;
            }
        }
        $this->jsonError(404, 'identity_not_found');
    }


    public function doCrosses() {
        $params     = $this->params;
        $updated_at = $params['updated_at'];
        $id         = @ (int) $params['id'];
        $user_id    = @ (int) $params['user_id'];
        if ($updated_at) {
            $intTime    = strtotime($updated_at);
            $updated_at = date('Y-m-d H:i:s', $intTime);
        }
        if ($id) {
            $modCross = $this->getModelByName('Cross');
            $modExfee = $this->getModelByName('Exfee');
            $hlpCross = $this->getHelperByName('Cross');
            $exfee_id = $modCross->getExfeeByCrossId($id);
            if (!$user_id) {
                // for wechat
                // $this->jsonError(403, 'forbidden');
                // return;
            } else if ($user_id > 0) {
                $userids = $modExfee->getUserIdsByExfeeId($exfee_id, true);
                if (!in_array($user_id, $userids)) {
                    $this->jsonError(403, 'forbidden');
                    return;
                }
            } else if ($user_id < 0) {
                $identityids = $modExfee->getIdentityIdsByExfeeId($exfee_id);
                if (!in_array(-$user_id, $identityids)) {
                    $this->jsonError(403, 'forbidden');
                    return;
                }
            }
            $cross = $hlpCross->getCross($id, true, false, $updated_at);
            if ($updated_at && $cross->updated) {
                foreach ($cross->updated as $uI => $uItem) {
                    $itemTime = strtotime($uItem['updated_at']);
                    if ($itemTime < $intTime) {
                        unset($cross->updated[$uI]);
                    }
                }
            }
            if ($cross) {
                switch ($cross->attribute['state']) {
                    case 'deleted':
                        $this->jsonError(403, 'forbidden');
                        return;
                    case 'draft':
                        if ($user_id > 0 && !in_array($user_id, $cross->exfee->hosts)) { // @todo 可能有安全问题 by @Leask
                            $this->jsonError(403, 'forbidden');
                            return;
                        }
                }
                if ($updated_at && (strtotime($updated_at) > strtotime($cross->exfee->updated_at) || !$cross->updated)) {
                    $this->jsonError(304, 'not_modified');
                    return;
                }
                if (!$cross->updated) {
                    $cross->updated = new stdClass;
                }
                $this->jsonResponse($cross);
                return;
            }
        }
        $this->jsonError(404, 'cross_not_found');
    }


    public function doGetRouteXUrl() {
        $crossHelper = $this->getHelperByName('cross');
        $params = $this->params;
        $cross  = $crossHelper->getCross(@(int)$params['cross_id']);
        if ($cross) {
            if ($cross->attribute['state'] === 'deleted') {
                $this->jsonError(500, 'cross_error');
                return;
            }
            $modExfee = $this->getModelByName('Exfee');
            foreach (explode(',', SMITH_BOT) as $idBot) {
                $invitation = $modExfee->getRawInvitationByCrossIdAndIdentityId(
                    $cross->id, $idBot
                );
                if ($invitation) {
                    break;
                }
            }
            if (!$invitation) {
                $this->jsonError(500, 'cross_error');
                return;
            }
            $this->jsonResponse(SITE_URL . "/#!token={$invitation['token']}/routex/");
            return;
        }
        $this->jsonError(404, 'cross_not_found');
    }


    public function doExfees() {
        $params     = $this->params;
        $id         = @ (int) $params['id'];
        $user_id    = @ (int) $params['user_id'];
        if ($id) {
            $modExfee = $this->getModelByName('Exfee');
            if (!$user_id) {
                $this->jsonError(403, 'forbidden');
                return;
            } else if ($user_id > 0) {
                $userids = $modExfee->getUserIdsByExfeeId($id, true);
                if (!in_array($user_id, $userids)) {
                    $this->jsonError(403, 'forbidden');
                    return;
                }
            } else if ($user_id < 0) {
                $identityids = $modExfee->getIdentityIdsByExfeeId($id);
                if (!in_array(-$user_id, $identityids)) {
                    $this->jsonError(403, 'forbidden');
                    return;
                }
            }
            $exfee = $modExfee->getExfeeById($id);
            if ($exfee) {
                $this->jsonResponse($exfee);
                return;
            }
        }
        $this->jsonError(404, 'exfee_not_found');
    }


    public function doConversation() {
        $params     = $this->params;
        $exfee_id   = $params['id'];
        $updated_at = $params['updated_at'];
        $direction  = $params['direction'];
        $quantity   = $params['quantity'];
        $clear      = $params['clear'];

        if (!$exfee_id) {
            $this->jsonError(500, 'no_exfee_id');
            return;
        }

        if ($updated_at) {
            $raw_updated_at = strtotime($updated_at);
            if ($raw_updated_at !== false) {
                $updated_at = date('Y-m-d H:i:s', $raw_updated_at);
            } else {
                $updated_at = '';
            }
        } else {
            $updated_at = '';
        }

        $helperData   = $this->getHelperByName('conversation');
        $conversation = $helperData->getConversationByExfeeId(
            $exfee_id, $updated_at, $direction, $quantity
        );

        if (!is_array($conversation)) {
            $this->jsonError(500, 'internal_server_error');
            return;
        }

        if ($clear !== 'false') {
            //clear counter
            $conversationData=$this->getModelByName('conversation');
            $conversationData->clearConversationCounter($exfee_id, $result['uid']);
        }
        $modExfee = $this->getModelByName('exfee');
        $cross_id = $modExfee->getCrossIdByExfeeId($exfee_id);
        touchCross($cross_id, $result['uid']);

        $this->jsonResponse($conversation);
    }


    public function doAddPhotos() {
        $params   = $this->params;
        $cross_id = @ (int) $params['id'];
        if (!$cross_id) {
            $this->jsonError(500, 'no_photox_id');
            return;
        }
        // get model
        $modPhoto = $this->getModelByName('Photo');
        // get raw data
        if (!($str_args = @file_get_contents('php://input'))) {
            $this->jsonError(500, 'no_input');
            return;
        }
        // decode json
        $photos = json_decode($str_args);
        if (is_array($photos)) {
            if ($photos) {
                $identity_id = @ (int) $photos[0]->by_identity->id;
                if ($identity_id) {
                    $result = $modPhoto->addPhotosToCross(
                        $cross_id, $photos, $identity_id
                    );
                    if ($result) {
                        $this->jsonResponse(['photox_id' => $cross_id]);
                        return;
                    }
                }
            }
        }
        $this->jsonError(500, 'internal_server_error');
    }


    public function doUsers() {
        $modUser     = $this->getModelByName('User');
        $modIdentity = $this->getModelByName('Identity');
        if (!($strArgs  = @file_get_contents('php://input'))
         || !($identity = @json_decode($strArgs, true))) {
            $this->jsonError(500, 'invalid_input');
            return;
        }
        // decode json
        $external_id       = @$identity['external_id'];
        $external_username = @$identity['external_username'];
        $provider          = @$identity['provider'];
        if ($provider === 'wechat' && !$external_id) {
            $this->jsonError(500, 'error_external_id');
            return;
        }
        // check identity
        $identity = $modIdentity->getIdentityByProviderAndExternalUsername(
            $provider, $external_username
        );
        if (!$identity) {
            $identity_id = $modIdentity->addIdentity([
                'provider'          => $provider,
                'external_id'       => $external_id,
                'external_username' => $external_username,
                'name'              => @$identity['name'],
                'bio'               => @$identity['bio'],
                'locale'            => @$identity['locale'],
                'timezone'          => @$identity['timezone'],
                'avatar_filename'   => @$identity['avatar_filename'],
            ]);
            $identity    = $modIdentity->getIdentityById($identity_id);
        }
        if (!$identity) {
            $this->jsonError(500, 'identity_error');
            return;
        }
        $identity_id  = $identity->id;
        // check user
        $user_infos   = $modUser->getUserIdentityInfoByIdentityId($identity_id);
        $user_id      = 0;
        if (isset($user_infos['CONNECTED'])) {
            $user_id  = $user_infos['CONNECTED'][0]['user_id'];
        } else if (isset($user_infos['REVOKED'])) {
            $user_id  = $user_infos['REVOKED'][0]['user_id'];
            $modUser->setUserIdentityStatus($user_id, $identity_id, 3);
        } else {
            $user_id  = $modUser->addUser();
            $modUser->setUserIdentityStatus($user_id, $identity_id, 3);
            $identity = $modIdentity->getIdentityById($identity_id);
            $modIdentity->sendVerification(
                'Welcome', $identity, '', false, $identity->name ?: ''
            );
        }
        if (!$user_id || !($user = $modUser->getUserById($user_id))) {
            $this->jsonError(500, 'user_error');
            return;
        }
        // return
        $siResult = $modUser->rawSignin($user_id);
        if ($siResult) {
            $user->password = $siResult['password'];
            $this->jsonResponse([
                'user'          => $user,
                'authorization' => [
                    'user_id' => $siResult['user_id'],
                    'token'   => $siResult['token'],
                    'name'    => $siResult['name'],
                ],
            ]);
            return;
        }
        $this->jsonError(500, 'server_error');
    }


    public function doSetPassword() {
        $modUser = $this->getModelByName('User');
        if (!($user_id = (int) $_POST['user_id'])) {
            $this->jsonError(400, 'invalid_user_id');
            return;
        }
        if (!validatePassword($passwd = $_POST['password'])) {
            $this->jsonError(400, 'invalid_password');
            return;
        }
        if ($modUser->setUserPassword($user_id, $passwd)) {
            $this->jsonResponse(['user_id' => $user_id]);
            return;
        }
        $this->jsonError(400, 'bad_request');
    }


    public function doCheckWechatFollowing() {
        set_time_limit(5);
        if (($external_id = @$this->params['external_id'])) {
            $modWechat = $this->getModelByName('Wechat');
            $identity  = $modWechat->getIdentityBy($external_id);
            $this->jsonResponse(['following' => !!$identity]);
            return;
        }
        $this->jsonError(400, 'bad_request');
    }


    public function doSendWechatMessage() {
        $modWechat = $this->getModelByName('Wechat');
        $rawInput  = @file_get_contents('php://input');
        $msgObject = json_decode($rawInput, true);
        if ($msgObject
         && isset($msgObject['touser'])
         && isset($msgObject['template_id'])
         && isset($msgObject['data'])) {
            $result = $modWechat->sendTemplateMessage(
                $msgObject['touser'],
                $msgObject['template_id'],
                $msgObject['data']
            );
            if ($result) {
                $this->jsonResponse(new stdClass);
                return;
            }
        }
        $this->jsonError(400, 'bad_request');
    }


    public function doRequestXTitle() {
        $modWechat = $this->getModelByName('Wechat');
        $params    = $this->params;
        if ($modWechat->requestXTitle(
            $params['cross_id'],
            $params['cross_title'],
            $params['external_id']
        )) {
            $this->jsonResponse(new stdClass);
        } else {
            $this->jsonError(400, 'bad_request');
        }
    }


    public function doTutorials() {
        // init models
        $modIdentity = $this->getModelByName('Identity');
        $modUser     = $this->getModelByName('User');
        $modConv     = $this->getModelByName('Conversation');
        $modExfee    = $this->getModelByName('Exfee');
        $modQueue    = $this->getModelByName('Queue');
        $hlpCross    = $this->getHelperByName('cross');
        // init functions
        function nextStep($step_id, $cross_id, $exfee_id, $identity_id, $delay = 5, $created_at = 0) {
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
        $editExfee = function ($exfee, $cross_id, $invitation, $by_identity) use ($modExfee, $hlpCross) {
            $exfee->invitations = [$invitation];
            $udeResult = $modExfee->updateExfee(
                $exfee, $by_identity->id, $by_identity->connected_user_id
            );
            $objCross = $hlpCross->getCross($cross_id);
            saveUpdate($cross_id, ['exfee' => [
                'updated_at'  => date('Y-m-d H:i:s', time()),
                'identity_id' => $by_identity->id,
            ]]);
            touchCross($cross_id, $by_identity->connected_user_id);
            return $udeResult ? $objCross : null;
        };
        $post = function ($cross_id, $exfee_id, $identity, $content) use ($modConv, $hlpCross, $modQueue) {
            $objPost = new Post(0, $identity, $content, $exfee_id, 'exfee');
            $objPost->by_identity_id = $identity->id;
            $pstResult = $modConv->addPost($objPost);
            if (($post_id = @$pstResult['post_id'])) {
                $post  = $modConv->getPostById($post_id);
                $cross = $hlpCross->getCross($cross_id, true);
                $draft = isset($cross->attribute)
                    && isset($cross->attribute['state'])
                    && $cross->attribute['state'] === 'draft';
                if (!$draft) {
                    $modQueue->despatchConversation(
                        $cross, $post, $identity->connected_user_id, $identity->id
                    );
                }
            }
            touchCross($cross_id, $identity->connected_user_id);
            return $pstResult && $pstResult['post'] ? $pstResult['post'] : null;
        };
        // get inputs
        $params      = $this->params;
        $now         = time();
        $delay       = 5;
        if (!($step_id     = @ (int) $params['id'])) {
            $this->jsonError(500, 'no_step_id');
            return;
        }
        if (!($identity_id = @ (int) $params['identity_id'])) {
            $this->jsonError(500, 'no_identity_id');
            return;
        }
        if (!($objIdentity = $modIdentity->getIdentityById($identity_id))) {
            $this->jsonError(500, 'identity_error');
            return;
        }
        // get robots
        $bot233      = $modIdentity->getIdentityById(TUTORIAL_BOT_A);
        $botFrontier = $modIdentity->getIdentityById(TUTORIAL_BOT_B);
        $botCashbox  = $modIdentity->getIdentityById(TUTORIAL_BOT_C);
        $botClarus   = $modIdentity->getIdentityById(TUTORIAL_BOT_D);
        // gather
        if ($step_id === 1) {
            $objCross = $hlpCross->doTutorial($objIdentity);
            if ($objCross) {
                $this->jsonResponse($objCross);
            } else {
                $this->jsonError(500, 'internal_server_error');
            }
            return;
        }
        // get inputs
        if (!($cross_id = @ (int) $params['cross_id'])) {
            $this->jsonError(500, 'no_cross_id');
            return;
        }
        if (!($exfee_id = @ (int) $params['exfee_id'])) {
            $this->jsonError(500, 'no_exfee_id');
            return;
        }
        // get cross
        if (!($objCross = $hlpCross->getCross($cross_id))) {
            $this->jsonError(500, 'cross_error');
            return;
        }
        // get exfee
        if (!($exfee    = $modExfee->getExfeeById($exfee_id))) {
            $this->jsonError(500, 'exfee_error');
            return;
        }
        // get created_at
        $created_at = @ (int) $params['created_at'];
        // get leaved
        $leaved = true;
        foreach ($exfee->invitations as $invitation) {
            if ($invitation->identity->id === $identity_id) {
                $leaved = false;
                break;
            }
        }
        if ($leaved) {
            $this->jsonError(500, 'user_leaved');
            return;
        }
        // steps
        $idxLang = $objIdentity->locale === 'zh_cn' ? 1 : 0;
        $scripts = [
            2  => ['%NAME%, warm welcome! Shuady ·X· is a group utility, it makes gathering with friends easier.', '欢迎%NAME%！水滴·汇是一个群组工具，用来和朋友们组织活动。'],
            4  => ['woof woof~', '汪~ 汪~'],
            6  => ['Can we do this later after meal?', '能等我先吃完饭嘛？'],
            7  => ["Hey Cashbox be kind, can't you eat later?", '嘿钱柜，你能热情点等会再吃吗？'],
            8  => ['Gathering with Shuady ·X·, without worrying contacts. Email, SMS, Facebook even iMessage (trialing)…all cool. Never force friends to download the same app, nor left someone out. Shuady connects everyone. Be “contact-agnostic”.',
                   '用水滴·汇组织活动可以不在意朋友们的联系方式，邮件、短信、甚至iMessage（试验中）都行，还将会支持其它账号（如微信）。您不必要求每位朋友都安装相同的应用软件，不用担心丢下谁，水滴·汇能把大家联系起来。'],
            9  => ['Your contacts and web accounts are your ‘identities’. Add them into your profile facilitates gathering, and consequently all your ·X· will be displayed collectively, get rid of switching accounts back and forth.',
                   '每个联系方式都是一个“身份”（邮箱、手机、各种网站账号等），把自己的各种身份添加到账号中，就能把所有参加组织过的活动汇总起来，不需要来回切换账号。'],
            10 => ['BTW, ·X· (as "cross") is a gathering, a hangout, an event or anything you want to do with friends.', '对了。·汇·的意思是相聚，像逛街、约会、出游或任何要跟朋友们一起做的事。'],
            11 => ['As group utility, ·X· has cool features like RouteX, a route map showing everyone’s location dynamically, and more to come…', '水滴·汇 群组工具还有一些很酷的功能。像“活点地图”，能在地图上动态展示每个人的位置和轨迹。还有更多后续功能……'],
            13 => ['My friend Dogcow is joining us to welcome %NAME%.', '我朋友“奶牛狗”也来欢迎%NAME%。'],
            15 => ['moof~', 'moof~'],
            16 => ['@%NAME%, why not setting a portrait in your profile, so your friends can recognize?', '@%NAME%给自己设个头像吧，方便大家认出你。'],
            17 => ["Hey~ This message is sent directly by replying email! Don't even need to open website or app, cool!", '嘿~ 我可以直接回邮件发出这条消息，连网站和手机app都不用打开，酷！'],
            18 => ['Actually, you can also gather a ·X· by cc %EMAIL% when you email friends.', '事实上你群发邮件给朋友们时直接抄送 %EMAIL% 就能创建新活动了。'],
            19 => ['Don’t forget to get Shuady ·X· app for mobility, instant updates, routes and more…', '别忘了安装水滴·汇的iPhone应用，可以随时随地即刻得知活动变化、使用活点地图……'],
            20 => ['moof!', 'moof!'],
        ];
        switch ($step_id) {
            case 2:
                $result = $post($cross_id, $exfee_id, $bot233, str_replace('%NAME%', $objIdentity->name, $scripts[$step_id][$idxLang]));
                break;
            case 3:
                $result = $editExfee($exfee, $cross_id, new Invitation(
                    0, $botFrontier, $botFrontier, $botFrontier,
                    'ACCEPTED', 'EXFE', '', $now, $now, false, 0, []
                ), $botFrontier);
                break;
            case 4:
                $result = $post($cross_id, $exfee_id, $botFrontier, $scripts[$step_id][$idxLang]);
                $delay  = 60;
                break;
            case 5:
                $result = $editExfee($exfee, $cross_id, new Invitation(
                    0, $botCashbox, $botCashbox, $botCashbox,
                    'DECLINED', 'EXFE', '', $now, $now, false, 0, []
                ), $botCashbox);
                break;
            case 6:
                $result = $post($cross_id, $exfee_id, $botCashbox, $scripts[$step_id][$idxLang]);
                break;
            case 7:
                $result = $post($cross_id, $exfee_id, $bot233, $scripts[$step_id][$idxLang]);
                break;
            case 8:
                $result = $post($cross_id, $exfee_id, $bot233, $scripts[$step_id][$idxLang]);
                break;
            case 9:
                $result = $post($cross_id, $exfee_id, $botFrontier, $scripts[$step_id][$idxLang]);
                break;
            case 10:
                $result = $post($cross_id, $exfee_id, $bot233, $scripts[$step_id][$idxLang]);
                break;
            case 11:
                $delay  = 60 * 7;
                $result = $post($cross_id, $exfee_id, $bot233, $scripts[$step_id][$idxLang]);
                $created_at = $now;
                break;
            case 12:
                if (getCrossTouchTime($cross_id, $objIdentity->connected_user_id)
                || ($now - $created_at >= 60 * 60 * 24)) {
                    $result = $editExfee($exfee, $cross_id, new Invitation(
                        0, $botCashbox, $bot233, $bot233,
                        'ACCEPTED', 'EXFE', '', $now, $now, false, 0, []
                    ), $botCashbox);
                } else {
                    $result = new stdClass;
                    $delay  = 60 * 60;
                    $step_id--;
                }
                break;
            case 13:
                $result = $post($cross_id, $exfee_id, $botCashbox, str_replace('%NAME%', $objIdentity->name, $scripts[$step_id][$idxLang]));
                break;
            case 14:
                $result = $editExfee($exfee, $cross_id, new Invitation(
                    0, $botClarus, $botCashbox, $botCashbox,
                    'NORESPONSE', 'EXFE', '', $now, $now, false, 0, []
                ), $botCashbox);
                break;
            case 15:
                $result = $post($cross_id, $exfee_id, $botClarus, $scripts[$step_id][$idxLang]);
                break;
            case 16:
                $result = $objIdentity->avatar && !preg_match('/^http(s)*:\/\/.+\/v2\/avatar\/default\?name=.*$/i', $objIdentity->avatar['80_80'])
                        ? new stdClass
                        : $post($cross_id, $exfee_id, $botFrontier, str_replace('%NAME%', $objIdentity->name, $scripts[$step_id][$idxLang]));
                break;
            case 17:
                $result = $post($cross_id, $exfee_id, $botCashbox, $scripts[$step_id][$idxLang]);
                break;
            case 18:
                $result = $post($cross_id, $exfee_id, $bot233, str_replace('%EMAIL%', 'x@' . preg_replace('/\.(.*)/', '$1', ROOT_DOMAIN), $scripts[$step_id][$idxLang]));
                break;
            case 19:
                $modDevice = $this->getModelByName('device');
                $result = $modDevice->getDevicesByUserid($objIdentity->connected_user_id)
                        ? new stdClass
                        : $post($cross_id, $exfee_id, $botFrontier, $scripts[$step_id][$idxLang]);
                break;
            case 20:
                $result = $post($cross_id, $exfee_id, $botClarus, $scripts[$step_id][$idxLang]);
                return;
            default:
                $this->jsonError(500, 'unknow_step_id');
                return;
        }
        if ($result) {
            $this->jsonResponse($result);
            nextStep($step_id, $cross_id, $exfee_id, $identity_id, $delay, $created_at);
            return;
        }
        $this->jsonError(500, 'internal_server_error');
    }

}
