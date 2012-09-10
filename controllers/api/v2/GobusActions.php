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
            header('HTTP/1.1 400 Internal Server Error');
            echo json_encode(['code' => 233, 'error' => 'User not connected.']);
            return;
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
        $post_id  = $modCnvrstn->addPost($post, $time);
        if (!$post_id) {
            header('HTTP/1.1 500 Internal Server Error');
            return;
        }
        // get the new post
        $post     = $modCnvrstn->getPostById($post_id);
        // call Gobus {
        $hlpGobus = $this->getHelperByName('gobus');
        $modExfee = $this->getModelByName('exfee');
        $modConv  = $this->getModelByName('conversation');
        $cross_id = $modExfee->getCrossIdByExfeeId($post->postable_id);
        $cross    = $hlpCross->getCross($cross_id, true);
        $msgArg   = array(
            'cross'         => $cross,
            'post'          => $post,
            'to_identities' => array(),
            'by_identity'   => $by_identity,
        );
        $chkUser = array();
        foreach ($cross->exfee->invitations as $invitation) {
            $msgArg['to_identities'][] = $invitation->identity;
            // @todo: $msgArg['depended'] = false;
            if ($invitation->identity->connected_user_id > 0
             && !$chkUser[$invitation->identity->connected_user_id]) {
                // get mobile identities
                $mobIdentities = $modDevice->getDevicesByUserid(
                    $invitation->identity->connected_user_id,
                    $invitation->identity
                );
                foreach ($mobIdentities as $mI => $mItem) {
                    $msgArg['to_identities'][] = $mItem;
                }
                // set conversation counter
                if ($invitation->identity->connected_user_id
                !== $raw_by_identity->connected_user_id) {
                    $modConv->addConversationCounter(
                        $cross->exfee->id,
                        $invitation->identity->connected_user_id
                    );
                }
                // depended
                if ($invitation->identity->connected_user_id
                === $by_identity->connected_user_id) {
                    // @todo: $msgArg['depended'] = true;
                }
                // marked
                $chkUser[$invitation->identity->connected_user_id] = true;
            }
        }
        if (DEBUG) {
            error_log(json_encode($msgArg));
        }
        $hlpGobus->send('cross', 'Update', $msgArg);
        $modExfee->updateExfeeTime($cross->exfee->id);
        // }
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

}
