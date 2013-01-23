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


    public function doPostConversation() {
        // get model
        $modUser         = $this->getModelByName('User');
        $modDevice       = $this->getModelByName('Device');
        $modIdentity     = $this->getModelByName('Identity');
        $modCnvrstn      = $this->getModelByName('Conversation');
        $hlpCross        = $this->getHelperByName('Cross');
        // get raw data
        $cross_id        = (int)$_POST['cross_id'];
        $iom             = trim($_POST['iom']);
        $provider        = trim($_POST['provider']);
        $external_id     = trim($_POST['external_id']);
        $content         = trim($_POST['content']);
        $time            = strtotime($_POST['time']);
        // check data
        if ((!$cross_id && !$iom) || !$provider || !$external_id || !$content || !$time) {
            header('HTTP/1.1 500 Internal Server Error');
            return;
        }
        // get raw identity object
        $raw_by_identity = $modIdentity->getIdentityByProviderExternalId(
            $provider, $external_id
        );
        if (!$raw_by_identity) {
            header('HTTP/1.1 500 Internal Server Error');
            return;
        }
        if ($raw_by_identity->connected_user_id <= 0) {
            $user_status = $modUser->getUserIdentityInfoByIdentityId($raw_by_identity->id);
            if ($user_status) {
                header('HTTP/1.1 400 Internal Server Error');
                echo json_encode(['code' => 233, 'error' => 'User not connected.']);
                return;
            }
            // add new user
            $user_id = $modUser->addUser('', $raw_by_identity->name);
            // connect identity to new user
            $modUser->setUserIdentityStatus(
                $user_id, $raw_by_identity->id, 3
            );
            $raw_by_identity->connected_user_id = $user_id;
        }
        // get user object
        $user = $modUser->getUserById($raw_by_identity->connected_user_id);
        if (!$user) {
            header('HTTP/1.1 500 Internal Server Error');
            return;
        }
        // get cross id by iom
        if (!$cross_id) {
            $objCurl = curl_init();
            curl_setopt($objCurl, CURLOPT_URL, IOM_URL . "/iom/{$raw_by_identity->connected_user_id}/{$iom}");
            curl_setopt($objCurl, CURLOPT_HEADER, 0);
            curl_setopt($objCurl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($objCurl, CURLOPT_CONNECTTIMEOUT, 3);
            $curlResult = curl_exec($objCurl);
            curl_close($objCurl);
            $cross_id = (int) $curlResult;
        }
        if (!$cross_id) {
            header('HTTP/1.1 500 Internal Server Error');
            return;
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
                if ($invitation->identity->connected_user_id
                === $raw_by_identity->connected_user_id
                 && $invitation->rsvp_status == $priority) {
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
            case 'twitter':
            case 'facebook':
                $identity_id = $modIdentity->getIdentityByProviderAndExternalUsername(
                    $objRecipient->provider, $objRecipient->external_username, false, true
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
                    $identity['provider'], $identity['external_username'], false, true
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


    public function doAddPhotosToCross() {
        // get model
        $modPhoto = $this->getModelByName('Photo');
        // get raw data
        if (!($str_args = @file_get_contents('php://input'))) {
            header('HTTP/1.1 500 Internal Server Error');
            echo 'No input!';
            return;
        }
        // decode json
        $args = json_decode($str_args);
        if (isset($args['cross_id'])
         && isset($args['photos'])
         && is_array($args['photos'])
         && isset($args['identity_id'])) {
            $result = $modPhoto->addPhotosToCross(
                (int) $args['cross_id'], $args['photos'], (int) $args['identity_id']
            );
            if ($result) {
                return;
            }
        }
        header('HTTP/1.1 500 Internal Server Error');
        echo 'Error input!';
    }

}
