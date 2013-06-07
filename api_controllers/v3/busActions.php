<?php

require_once dirname(dirname(__FILE__)) . '/../lib/httpkit.php';


class BusActions extends ActionController {

    public function doUpdateIdentity() {
        // get raw data
        $id                = isset($_POST['id'])                ? intval($_POST['id'])                                  : null;
        $provider          = isset($_POST['provider'])          ? mysql_real_escape_string($_POST['provider'])          : null;
        $external_id       = isset($_POST['external_id'])       ? mysql_real_escape_string($_POST['external_id'])       : null;
        $name              = isset($_POST['name'])              ? mysql_real_escape_string($_POST['name'])              : '';
        $nickname          = isset($_POST['nickname'])          ? mysql_real_escape_string($_POST['nickname'])          : '';
        $bio               = isset($_POST['bio'])               ? mysql_real_escape_string($_POST['bio'])               : '';
        $avatar_filename   = isset($_POST['avatar_filename'])   ? mysql_real_escape_string($_POST['avatar_filename'])   : '';
        $external_username = isset($_POST['external_username']) ? mysql_real_escape_string($_POST['external_username']) : '';
        // check data
        if (!$id || !$provider || !$external_id) {
            $this->jsonError(500, 'identity_error');
            return;
        }
        // do update
        $modIdentity = $this->getModelByName('Identity');
        $id = $modIdentity->updateIdentityByGobus($id, array(
            'provider'          => $provider,
            'external_id'       => $external_id,
            'name'              => $name,
            'nickname'          => $nickname,
            'bio'               => $bio,
            'avatar_filename'   => $avatar_filename,
            'external_username' => $external_username,
        ));
        // return
        if (!$id) {
            $this->jsonError(500, 'internal_server_error');
            return;
        }
        $this->jsonResponse(['identity_id' => $id]);
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
        if ($chkCross['error']) {
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
        $cross->attribute->state = 'draft';
        $gthResult = $crossHelper->gatherCross($cross, $identity_id, $user_id);
        $cross_id = @$gthResult['cross_id'];
        if (!$cross_id) {
            $this->jsonError(500, 'gathering_error');
            return;
        }

        // publish on time {
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
        // }

        $rspData = ['cross_id' => $cross_id];
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
         || (!isset($args->cross_id) && !isset($args->exfee_id))
         || !isset($args->cross)
         || !isset($args->by_identity)) {
            $this->jsonError(500, 'cross_error');
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
        foreach ($rsvp_priority as $priority) {
            if ($by_identity) {
                break;
            }
            foreach ($old_exfee->invitations as $invitation) {
                if ($invitation->rsvp_status == $priority
                 && ($invitation->identity->connected_user_id === $user_id
                  || $invitation->identity->id                === $identity_id)) {
                    $by_identity = $invitation->identity;
                    break;
                }
            }
        }
        if (!$by_identity) {
            $this->jsonError(400, 'not_authorized');
            return;
        }

        // update crosss
        $cross_id = $rawResult = true;
        $cross_id = $crossHelper->editCross($cross, $by_identity->id);
        if (isset($cross->exfee)) {
            $rawResult = $modExfee->updateExfee($cross->exfee, $by_identity->id, $user_id, true, false, true);
        }
        if (!$cross_id || !$rawResult) {
            $this->jsonError(500, 'internal_server_error');
            return;
        }
        $hlpCross = $this->getHelperByName('Cross');
        $rtResult = [
            'cross_id' => $cross_id,
            'exfee_id' => $rawResult['exfee_id'],
            'cross'    => $hlpCross->getCross($cross_id)
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
                if ($invitation->rsvp_status === $priority
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
                error_log($str_args);
            }
            return;
        }
        // decode json
        $obj_args = json_decode($str_args);
        if (!$obj_args || !$obj_args->user_id || !is_array($obj_args->identities)) {
            $this->jsonError(500, 'json_error');
            if (DEBUG) {
                error_log('JSON error!');
                error_log($str_args);
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
                error_log($str_args);
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
        if (!($str_args = @file_get_contents('php://input'))) {
            $this->jsonError(500, 'no_input');
            if (DEBUG) {
                error_log('No input!');
                error_log($str_args);
            }
            return;
        }
        // decode json
        $obj_args     = (array) json_decode($str_args);
        $objRecipient = @$obj_args['recipient'];
        $strError     = @$obj_args['error'];
        if (!$objRecipient
         || !$objRecipient->external_username
         || !$objRecipient->provider) {
            $this->jsonError(500, 'json_error');
            if (DEBUG) {
                error_log('JSON error!');
                error_log($str_args);
            }
            return;
        }
        // rawjob
        switch ($objRecipient->provider) {
            case 'email':
            case 'phone':
            case 'twitter':
            case 'facebook':
            case 'google':
                $identity_id = $modIdentity->getIdentityByProviderAndExternalUsername(
                    $objRecipient->provider, $objRecipient->external_username, true
                );
                if (!$identity_id) {
                    $this->jsonError(500, 'identity_not_found');
                    if (DEBUG) {
                        error_log('Identity not found!');
                        error_log(json_encode($objRecipient));
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
                    $objRecipient->external_username,
                    $objRecipient->provider, $strError
                );
                break;
            default:
                $this->jsonError(500, 'unknow_provide');
                if (DEBUG) {
                    error_log('Unknow provider!');
                    error_log(json_encode($objRecipient));
                }
                return;
        }
        // return
        $this->jsonResponse($objRecipient);
    }


    public function doRevokeIdentity() {
        // get model
        $modIdentity = $this->getModelByName('Identity');
        // get raw data
        if (!($str_args = @file_get_contents('php://input'))) {
            $this->jsonError(500, 'no_input');
            if (DEBUG) {
                error_log('No input!');
                error_log($str_args);
            }
            return;
        }
        // decode json
        $identity = (array) json_decode($str_args);
        $identity['id']                = isset($identity['id'])                ? (int) $identity['id']                                    : 0;
        $identity['provider']          = isset($identity['provider'])          ? mysql_real_escape_string($identity['provider'])          : '';
        $identity['external_username'] = isset($identity['external_username']) ? mysql_real_escape_string($identity['external_username']) : '';
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
                $this->jsonError(403, 'forbidden');
                return;
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


    public function doTutorials() {
        // init models
        $modIdentity = $this->getModelByName('Identity');
        $modTime     = $this->getModelByName('Time');
        $modBkg      = $this->getModelByName('Background');
        $modConv     = $this->getModelByName('Conversation');
        $modExfee    = $this->getModelByName('Exfee');
        $hlpCross    = $this->getHelperByName('cross');
        // get inputs
        $params      = $this->params;
        $now         = time();
        if (!($stepId      = @ (int) $params['id'])) {
            $this->jsonError(500, 'no_step_id');
            return;
        }
        if (!($identityId  = @ (int) $params['identity_id'])) {
            $this->jsonError(500, 'no_identity_id');
            return;
        }
        if (!($objIdentity = $modIdentity->getIdentityById($identityId))) {
            $this->jsonError(500, 'identity_error');
            return;
        }
        // check robots
        if (!($btAIdentity = $modIdentity->getIdentityById(TUTORIAL_BOT_A))
         || !($btBIdentity = $modIdentity->getIdentityById(TUTORIAL_BOT_B))
         || !($btCIdentity = $modIdentity->getIdentityById(TUTORIAL_BOT_C))
         || !($btDIdentity = $modIdentity->getIdentityById(TUTORIAL_BOT_D))
         || !($btEIdentity = $modIdentity->getIdentityById(TUTORIAL_BOT_E))) {
            $this->jsonError(500, 'robot_error');
            return;
        }
        switch ($stepId) {
            case 1: // gather
                $objCross = new stdClass;
                $objCross->title       = 'Watch the Star Trek Movie!';
                $objCross->description = 'Star Trek Into Darkness';
                $objCross->by_identity = $objIdentity;
                $objCross->time        = $modTime->parseTimeString('tomorrow', '+00:00');
                $objCross->place       = new Place(
                    0, '星美国际影城', '中国上海市浦东新区陆家嘴东路168号',
                    '121.49984399999994', '31.237148',
                    'google', '0281fa8a12a90a47c8c8bc697c4f525deaffc526',
                    $now, $now
                );
                $objCross->attribute   = new stdClass;
                $objCross->attribute->state = 'published';
                $objBackground         = new stdClass;
                $allBgs = $modBkg->getAllBackground();
                $objCross->widget      = [
                    new Background($allBgs[rand(0, sizeof($allBgs) - 1)])
                ];
                $objCross->type        = 'Cross';
                $objCross->exfee       = new Exfee;
                $objCross->exfee->invitations = [
                    new Invitation(
                        0, $btAIdentity, $btAIdentity, $btAIdentity,
                        'ACCEPTED', 'EXFE', '', $now, $now, true,  0, []
                    ),
                    new Invitation(
                        0, $objIdentity, $btAIdentity, $btAIdentity,
                        'NORESPONSE', 'EXFE', '', $now, $now, false, 0, []
                    ),
                ];
                $gtResult = $hlpCross->gatherCross(
                    $objCross, $btAIdentity->id,
                    $btAIdentity->connected_user_id > 0
                  ? $btAIdentity->connected_user_id : 0
                );
                $cross_id = @ (int) $gtResult['cross_id'];
                if ($cross_id > 0) {
                    $objCross = $hlpCross->getCross($cross_id);
                    // fire step 2 {
                    httpKit::request(
                        EXFE_GOBUS_SERVER . '/v3/queue/-/POST/'
                      . base64_url_encode(
                            SITE_URL . '/v3/bus/tutorials/2'
                          . "?cross_id={$cross_id}"
                          . "&exfee_id={$objCross->exfee->id}"
                          . "&identity_id={$objIdentity->id}"
                        ),
                        ['update' => 'once', 'ontime' => $now], [],
                        false, false, 3, 3, 'txt'
                    );
                    // }
                    touchCross($cross_id, $btAIdentity->connected_user_id);
                    $this->jsonResponse($objCross);
                    return;
                }
                $this->jsonError(500, 'internal_server_error');
                break;
            case 2:
            case 3:
            case 4:
                // get inputs
                if (!($cross_id     = @ (int) $params['cross_id'])) {
                    $this->jsonError(500, 'no_cross_id');
                    return;
                }
                if (!($exfeeId      = @ (int) $params['exfee_id'])) {
                    $this->jsonError(500, 'no_exfee_id');
                    return;
                }
                // get cross
                if (!($objCross     = $hlpCross->getCross($cross_id))) {
                    $this->jsonError(500, 'cross_error');
                    return;
                }
                // get exfee
                if (!($exfee        = $modExfee->getExfeeById($exfeeId))) {
                    $this->jsonError(500, 'exfee_error');
                    return;
                }
                $leaved = true;
                foreach ($exfee->invitations as $invitation) {
                    if ($invitation->identity->id === $identityId) {
                        $leaved = false;
                        break;
                    }
                }
                if ($leaved) {
                    $this->jsonError(500, 'user_leaved');
                    return;
                }
                switch ($stepId) {
                    case 2: // invite
                        $exfee->invitations = [new Invitation(
                            0, $btBIdentity, $btAIdentity, $btAIdentity,
                            'NORESPONSE', 'EXFE', '', $now, $now, false, 0, []
                        )];
                        $udeResult = $modExfee->updateExfee(
                            $exfee, $btAIdentity->id,
                            $btAIdentity->connected_user_id
                        );
                        if ($udeResult) {
                            $objCross = $hlpCross->getCross($cross_id);
                            // fire step 3 {
                            httpKit::request(
                                EXFE_GOBUS_SERVER . '/v3/queue/-/POST/'
                              . base64_url_encode(
                                    SITE_URL . '/v3/bus/tutorials/3'
                                  . "?cross_id={$cross_id}"
                                  . "&exfee_id={$objCross->exfee->id}"
                                  . "&identity_id={$objIdentity->id}"
                                ),
                                ['update' => 'once', 'ontime' => $now], [],
                                false, false, 3, 3, 'txt'
                            );
                            // }
                            saveUpdate(
                                $cross_id,
                                ['exfee' => [
                                    'updated_at'  => date('Y-m-d H:i:s', $now),
                                    'identity_id' => $btAIdentity->id,
                                ]]
                            );
                            touchCross(
                                $cross_id, $btAIdentity->connected_user_id
                            );
                            $this->jsonResponse($objCross);
                            return;
                        }
                        $this->jsonError(500, 'internal_server_error');
                        break;
                    case 3: // conversation
                        $objPost = new Post(
                            0, $btAIdentity, '喵喵~~', $exfeeId, 'exfee'
                        );
                        $objPost->by_identity_id = $btAIdentity->id;
                        $pstResult = $modConv->addPost($objPost);
                        if ($pstResult && $pstResult['post']) {
                            // fire step 4 {
                            httpKit::request(
                                EXFE_GOBUS_SERVER . '/v3/queue/-/POST/'
                              . base64_url_encode(
                                    SITE_URL . '/v3/bus/tutorials/4'
                                  . "?cross_id={$cross_id}"
                                  . "&exfee_id={$objCross->exfee->id}"
                                  . "&identity_id={$objIdentity->id}"
                                ),
                                ['update' => 'once', 'ontime' => $now], [],
                                false, false, 3, 3, 'txt'
                            );
                            // }
                            touchCross($cross_id, $btAIdentity->connected_user_id);
                            $this->jsonResponse($pstResult['post']);
                            return;
                        }
                        $this->jsonError(500, 'internal_server_error');
                        break;
                    case 4: // conversation
                        $objPost = new Post(
                            0, $btBIdentity, '汪汪~~', $exfeeId, 'exfee'
                        );
                        $objPost->by_identity_id = $btBIdentity->id;
                        $pstResult = $modConv->addPost($objPost);
                        if ($pstResult && $pstResult['post']) {
                            touchCross($cross_id, $btBIdentity->connected_user_id);
                            $this->jsonResponse($pstResult['post']);
                            return;
                        }
                        $this->jsonError(500, 'internal_server_error');
                }
                break;
            default:
                $this->jsonError(500, 'unknow_step_id');
        }
    }

}
