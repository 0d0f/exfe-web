<?php

class GobusActions extends ActionController {

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
            header('HTTP/1.1 500 Internal Server Error');
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
            header('HTTP/1.1 500 Internal Server Error');
            return;
        }
        apiResponse(['identity_id' => $id]);
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
            header('HTTP/1.1 400 Bad Request');
            apiError(400, 'error_cross', $chkCross['error'][0]);
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
            header('HTTP/1.1 500 Internal Server Error');
            apiError(500, 'error_identity', 'error_identity');
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
            header('HTTP/1.1 500 Internal Server Error');
            apiError(500, 'error_user', 'error_user');
        }

        // gather
        $cross_id = $crossHelper->gatherCross($cross, $identity_id, $user_id);
        if (!$cross_id) {
            header('HTTP/1.1 500 Internal Server Error');
            apiError(500, 'gather_error', "Can't gather this Cross.");        
        }
        apiResponse(['cross_id' => $cross_id]);
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
            header('HTTP/1.1 400 Bad Request');
            apiError(400, 'error_cross', 'error_cross');
        }
        $chkCross = $crossHelper->validateCross($args->cross);
        if ($chkCross['error']) {
            header('HTTP/1.1 400 Bad Request');
            apiError(400, 'error_cross', $chkCross['error'][0]);
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
            header('HTTP/1.1 500 Internal Server Error');
            apiError(500, 'error_identity', 'error_identity');
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
            header('HTTP/1.1 500 Internal Server Error');
            apiError(500, 'error_user', 'error_user');
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
            header('HTTP/1.1 404 Not Found');
            apiError(400, 'cross_not_found', 'cross_not_found');
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
            header('HTTP/1.1 400 Bad Request');
            apiError(400, 'not_authorized', 'not_authorized');
            return;
        }

        // update crosss
        $cross_id = $rawResult = true;
        $cross_id = $crossHelper->editCross($cross, $by_identity->id);
        if (isset($cross->exfee)) {
            $rawResult = $modExfee->updateExfee($cross->exfee, $by_identity->id, $user_id, true);
        }
        if (!$cross_id || !$rawResult) {
            header('HTTP/1.1 500 Internal Server Error');
            apiError(500, 'update_error', "Can't update this Cross.");
        }
        $hlpCross = $this->getHelperByName('Cross');
        apiResponse([
            'cross_id' => $cross_id,
            'exfee_id' => $rawResult['exfee_id'],
            'cross'    => $hlpCross->getCross($cross_id)
        ]);  
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
        $time            = strtotime($_POST['time']);
        if ((!$cross_id && !$iom) || !$provider || !$external_id || !$content || !$time) {
            header('HTTP/1.1 500 Internal Server Error');
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
            header('HTTP/1.1 500 Internal Server Error');
            apiError(500, 'error_identity', 'error_identity');
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
            header('HTTP/1.1 500 Internal Server Error');
            apiError(500, 'error_user', 'error_user');
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
        $cross = $hlpCross->getCross($cross_id);
        if (!$cross) {
            header('HTTP/1.1 500 Internal Server Error');
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
            header('HTTP/1.1 500 Internal Server Error');
            return;
        }

        // add post to conversation
        $post     = new Post(0, null, $content, $cross->exfee->id, 'exfee');
        $post->by_identity_id = $by_identity->id;
        $rstPost  = $modCnvrstn->addPost($post, $time);
        if (!($post_id = $rstPost['post_id'])) {
            header('HTTP/1.1 500 Internal Server Error');
            return;
        }
        // get the new post
        $post     = $modCnvrstn->getPostById($post_id);
        // call Gobus {
        $modQueue = $this->getModelByName('Queue');
        $modQueue->despatchConversation(
            $cross, $post, $by_identity->connected_user_id, $post->by_identity_id
        );
        // }
        $modExfee = $this->getModelByName('exfee');
        $modExfee->updateExfeeTime($cross->exfee->id);
        // return
        apiResponse(['post' => $post]);
    }


    public function doAddFriends() {
        // get model
        $modUser     = $this->getModelByName('User');
        $modRelation = $this->getModelByName('Relation');
        // get raw data
        if (!($str_args = @file_get_contents('php://input'))) {
            header('HTTP/1.1 500 Internal Server Error');
            echo 'No input!';
            if (DEBUG) {
                error_log('No input!');
                error_log($str_args);
            }
            return;
        }
        // decode json
        $obj_args = json_decode($str_args);
        if (!$obj_args || !$obj_args->user_id || !is_array($obj_args->identities)) {
            header('HTTP/1.1 500 Internal Server Error');
            echo 'JSON error!';
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
            header('HTTP/1.1 500 Internal Server Error');
            if (DEBUG) {
                error_log('Save error!');
            }
        }
        // build identities indexes
        if (!$modUser->buildIdentitiesIndexes($obj_args->user_id)) {
            header('HTTP/1.1 500 Internal Server Error');
            if (DEBUG) {
                error_log('Index error!');
                error_log($str_args);
            }
        }
        // return
        apiResponse(['user_id' => $obj_args->user_id]);
    }


    public function doNotificationCallback() {
        // get model
        $modIdentity = $this->getModelByName('Identity');
        $modDevice   = $this->getModelByName('Device');
        // get raw data
        if (!($str_args = @file_get_contents('php://input'))) {
            header('HTTP/1.1 500 Internal Server Error');
            echo 'No input!';
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
            header('HTTP/1.1 500 Internal Server Error');
            echo 'JSON error!';
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
                $identity_id = $modIdentity->getIdentityByProviderAndExternalUsername(
                    $objRecipient->provider, $objRecipient->external_username, true
                );
                if (!$identity_id) {
                    header('HTTP/1.1 500 Internal Server Error');
                    echo 'Identity not found!';
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
                header('HTTP/1.1 500 Internal Server Error');
                echo 'Unknow provider!';
                if (DEBUG) {
                    error_log('Unknow provider!');
                    error_log(json_encode($objRecipient));
                }
                return;
        }
        // return
        apiResponse(['recipient' => $objRecipient]);
    }


    public function doRevokeIdentity() {
        // get model
        $modIdentity = $this->getModelByName('Identity');
        // get raw data
        if (!($str_args = @file_get_contents('php://input'))) {
            header('HTTP/1.1 500 Internal Server Error');
            echo 'No input!';
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
                echo json_encode($objIdentity);
                return;
            }
        }
        header('HTTP/1.1 500 Internal Server Error');
        echo 'Identity not found!';
    }


    public function doGetCrossById() {
        $params = $this->params;
        $id = @ (int) $params['id'];
        if ($id) {
            $hlpCross = $this->getHelperByName('Cross');
            $cross = $hlpCross->getCross($id, true);
            if ($cross) {
                echo json_encode($cross);
                return;
            }
        }
        header('HTTP/1.1 404 Not Found');
    }


    public function doAddPhotos() {
        $params   = $this->params;
        $cross_id = @ (int) $params['id'];
        if (!$cross_id) {
            header('HTTP/1.1 500 Internal Server Error');
            echo 'No Cross id!';
            return;
        }
        // get model
        $modPhoto = $this->getModelByName('Photo');
        // get raw data
        if (!($str_args = @file_get_contents('php://input'))) {
            header('HTTP/1.1 500 Internal Server Error');
            echo 'No input!';
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
                        return;
                    }
                }
            }
        }
        header('HTTP/1.1 500 Internal Server Error');
        echo 'Error input!';
    }

}
