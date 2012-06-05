<?php
session_write_close();
require_once dirname(dirname(__FILE__)).'/../../lib/tmhOAuth.php';


class IdentitiesActions extends ActionController {

    public function doIndex() {
        $modIdentity = $this->getModelByName('identity', 'v2');
        $params = $this->params;
        if (!$params['id']) {
            apiError(400, 'no_identity_id', 'identity_id must be provided');
        }
        if ($objIdentity = $modIdentity->getIdentityById($params['id'])) {
            apiResponse(array('identity' => $objIdentity));
        }
        apiError(404, 'identity_not_found', 'identity not found');
    }


    public function doGet() {
        // get models
        $modUser       = $this->getModelByName('user',     'v2');
        $modIdentity   = $this->getModelByName('identity', 'v2');
        // get inputs
        $arrIdentities = trim($_POST['identities']) ? json_decode($_POST['identities']) : array();
        // ready
        $objIdentities = array();
        // get
        if ($arrIdentities) {
            foreach ($arrIdentities as $identityI => $identityItem) {
                if (!$identityItem->provider) {
                    continue;
                }
                $identity = $modIdentity->getIdentityByProviderExternalId(
                    $identityItem->provider, $identityItem->external_id
                );
                if ($identity) {
                    $objIdentities[] = $identity;
                } else {
                    switch ($identityItem->provider) {
                        case 'email':
                            $objEmail = $modIdentity->parseEmail($identityItem->external_id);
                            if ($objEmail) {
                                $objIdentities[] = new Identity(
                                    0,
                                    $identityItem->name ?: $objEmail['name'],
                                    '',
                                    '',
                                    'email',
                                    0,
                                    $objEmail['email'],
                                    $objEmail['email'],
                                    getAvatarUrl(
                                        'email',
                                        $objEmail['email'],
                                        '',
                                        80,
                                        API_URL . "/v2/avatar/default?name={$objEmail['email']}"
                                    )
                                );
                            }
                            break;
                        case 'twitter':
                            if ($identityItem->external_username) {
                                $twitterConn = new tmhOAuth(array(
                                    'consumer_key'    => TWITTER_CONSUMER_KEY,
                                    'consumer_secret' => TWITTER_CONSUMER_SECRET,
                                    'user_token'      => TWITTER_OFFICE_ACCOUNT_ACCESS_TOKEN,
                                    'user_secret'     => TWITTER_OFFICE_ACCOUNT_ACCESS_TOKEN_SECRET
                                ));
                                $responseCode = $twitterConn->request(
                                    'GET',
                                    $twitterConn->url('1/users/show'),
                                    array('screen_name' => $identityItem->external_username)
                                );
                                if ($responseCode === 200) {
                                    $twitterUser = (array)json_decode($twitterConn->response['response'], true);
                                    $objIdentities[] = new Identity(
                                        0,
                                        $twitterUser['name'],
                                        '',
                                        $twitterUser['description'],
                                        'twitter',
                                        0,
                                        $twitterUser['id'],
                                        $twitterUser['screen_name'],
                                        $modIdentity->getTwitterLargeAvatarBySmallAvatar(
                                            $twitterUser['profile_image_url']
                                        )
                                    );
                                }
                            }
                    }
                }
            }
            apiResponse(array('identities' => $objIdentities));
        } else {
            apiError(400, 'no_identities', 'identities must be provided');
        }
        apiError(500, "server_error", "Can't fetch identities.");
    }


    public function doUpdate() {
        // check signin
        $checkHelper = $this->getHelperByName('check', 'v2');
        $params = $this->params;
        $result = $checkHelper->isAPIAllow('user_edit', $params['token']);
        if ($result['check']) {
            $user_id = $result['uid'];
        } else {
            apiError(401, 'no_signin', '');
        }
        // get models
        $modUser     = $this->getModelByName('user', 'v2');
        $modIdentity = $this->getModelByName('identity', 'v2');
        // get user
        if (!($objUser = $modUser->getUserById($user_id))) {
            apiError(500, 'update_failed');
        }
        // collecting post data
        if (!($identity_id = intval($params['id']))) {
            apiError(400, 'no_identity_id', 'identity_id must be provided');
        }
        $identity = array();
        if (isset($_POST['name'])) {
            $identity['name'] = trim($_POST['name']);
        }
        if (isset($_POST['bio'])) {
            $identity['bio']  = trim($_POST['bio']);
        }
        // check identity
        foreach ($objUser->identities as $iItem) {
            if ($iItem->id === $identity_id) {
                switch ($iItem->provider) {
                    case 'email':
                        if ($identity && !$modIdentity->updateIdentityById($identity_id, $identity)) {
                            apiError(500, 'update_failed');
                        }
                        if ($objIdentity = $modIdentity->getIdentityById($identity_id)) {
                            apiResponse(array('identity' => $objIdentity));
                        }
                        apiError(500, 'update_failed');
                        break;
                    default:
                        apiError(400, 'can_not_update', 'this identity can not be update');
                }
            }
        }
        apiError(400, 'invalid_relation', 'only your connected identities can be update');
    }


    public function doUpdateByGobus() {
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
        $objIdentity = $this->getModelByName('Identity', 'v2');
        $id = $objIdentity->updateIdentityByGobus(
            $id,
            array('provider'          => $provider,
                  'external_id'       => $external_id,
                  'name'              => $name,
                  'nickname'          => $nickname,
                  'bio'               => $bio,
                  'avatar_filename'   => $avatar_filename,
                  'external_username' => $external_username)
        );
        echo json_encode(array('identity_id' => $id));
    }

}
