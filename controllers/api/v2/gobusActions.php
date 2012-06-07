<?php

class GobusActions extends ActionController {

    public function doUpdateIdentity() {
        // get raw data
        $id                = isset($_POST['id'])                ? intval(htmlspecialchars($_POST['id']))                                  : null;
        $provider          = isset($_POST['provider'])          ? mysql_real_escape_string(htmlspecialchars($_POST['provider']))          : null;
        $external_id       = isset($_POST['external_id'])       ? mysql_real_escape_string(htmlspecialchars($_POST['external_id']))       : null;
        $name              = isset($_POST['name'])              ? mysql_real_escape_string(htmlspecialchars($_POST['name']))              : '';
        $nickname          = isset($_POST['nickname'])          ? mysql_real_escape_string(htmlspecialchars($_POST['nickname']))          : '';
        $bio               = isset($_POST['bio'])               ? mysql_real_escape_string(htmlspecialchars($_POST['bio']))               : '';
        $avatar_filename   = isset($_POST['avatar_filename'])   ? mysql_real_escape_string(htmlspecialchars($_POST['avatar_filename']))   : '';
        $external_username = isset($_POST['external_username']) ? mysql_real_escape_string(htmlspecialchars($_POST['external_username'])) : '';
        // check data
        if (!$id || !$provider || !$external_id) {
            header('HTTP/1.1 500 Internal Server Error');
            return;
        }
        // do update
        $modIdentity = $this->getModelByName('Identity', 'v2');
        $id = $modIdentity->updateIdentityByGobus($id, array(
            'provider'          => $provider,
            'external_id'       => $external_id,
            'name'              => $name,
            'nickname'          => $nickname,
            'bio'               => $bio,
            'avatar_filename'   => $avatar_filename,
            'external_username' => $external_username,
        ));
        echo json_encode(array('identity_id' => $id));
    }


    public function doPostConversation() {
        // get model
        $modUser         = $this->getModelByName('User', 'v2');
        $modIdentity     = $this->getModelByName('Identity', 'v2');
        $modCnvrstn      = $this->getModelByName('Conversation', 'v2');
        $hlpCross        = $this->getHelperByName('Cross', 'v2');
        // get raw data
        $per_user_hash   = trim($_POST['per_user_hash']);
        $provider        = trim($_POST['provider']);
        $external_id     = trim($_POST['external_id']);
        $content         = trim($_POST['content']);
        $time            = strtotime($_POST['time']);
        // check data
        if (!$per_user_hash || !$provider || !$external_id || !$content || !$time) {
            header('HTTP/1.1 500 Internal Server Error');
            return;
        }
        // get raw identity object
        $raw_by_identity = $modIdentity->getIdentityByProviderExternalId(
            $provider, $external_id
        );
        if (!$raw_by_identity || !$raw_by_identity->connected_user_id) {
            header('HTTP/1.1 500 Internal Server Error');
            return;
        }
         // get user object
        $user = $modUser->getUserById($raw_by_identity->connected_user_id);
        if (!$user) {
            header('HTTP/1.1 500 Internal Server Error');
            return;
        }
        // get cross id by per_user_hash
        $objCurl = curl_init();
        curl_setopt($objCurl, CURLOPT_URL, PUH_URL . "/puh/{$raw_by_identity->connected_user_id}/{$per_user_hash}");
        curl_setopt($objCurl, CURLOPT_HEADER, 0);
        curl_setopt($objCurl, CURLOPT_RETURNTRANSFER, 1);
        $curlResult = curl_exec($objCurl);
        curl_close($objCurl);
        $cross_id = intval(trim($curlResult));
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
        $post    = new Post(0, null, $content, $cross->exfee->id, 'exfee');
        $post->by_identity_id = $by_identity->id;
        $post_id = $modCnvrstn->addPost($post, $time);
        if (!$post_id) {
            header('HTTP/1.1 500 Internal Server Error');
            return;
        }
        // get the new post
        $post    = $modCnvrstn->getPostById($post_id);
    }

}
