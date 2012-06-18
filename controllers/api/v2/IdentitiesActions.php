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
        apiError(500, 'server_error', "Can't fetch identities.");
    }


    public function doComplete() {
        // get models
        $modUser     = $this->getModelByName('user',     'v2');
        $modIdentity = $this->getModelByName('identity', 'v2');
        // check signin
        $checkHelper = $this->getHelperByName('check', 'v2');
        $params = $this->params;
        $result = $checkHelper->isAPIAllow('user_edit', $params['token']);
        if ($result['check']) {
            $user_id = $result['uid'];
        } else {
            apiError(401, 'no_signin', ''); // 需要登录
        }
        // get inputs
        $rangelen  = 50;
        $key       = mb_strtolower(trim($_GET['key']));
        if ($key === '') {
            apiError(400, 'empty_key_word', 'Keyword can not be empty.');
        }
        // rebuild identities indexes
        $redis = new Redis();
        $redis->connect(REDIS_SERVER_ADDRESS, REDIS_SERVER_PORT);
        $count=$redis->zCard("u:{$user_id}");
        if (!$count) {
            $user = $modUser->getUserById($user_id);
            $modUser->buildIdentitiesIndexes($user_id);
        }
        // get identities from redis
        $arrResult = array();
        $start = $redis->zRank("u:{$user_id}", $key);
        if(is_numeric($start)) {
            $endflag = false;
            $shResult  = $redis->zRange(
                "u:{$user_id}", $start + 1, $start + $rangelen
            );
            while (sizeof($shResult) > 0) {
                foreach ($shResult as $r) {
                    if ($r[strlen($r) - 1] === '*') {
                        // 根据返回的数据拆解 Key 和匹配的数据。
                        $arr_explode = explode('|', $r);
                        if (sizeof($arr_explode) ===2) {
                            $str = rtrim($arr_explode[1], '*');
                            $arrResult[$str] = $arr_explode[0];
                        }
                    }
                    if (strlen($r) === strlen($key)) {
                        $endflag = true;
                        break;
                    }
                }
                if (count($shResult) < $rangelen || $endflag === true) {
                    break;
                }
                $start += $rangelen;
                $shResult = $redis->zRange(
                    "u:{$user_id}", $start + 1, $start + $rangelen
                );
            }
        }
        // get identity objects
        $rtResult = array();
        foreach ($arrResult as $arI => $arItem) {
            $arrArI      = explode(':', $arI);
            $identity_id = (int) array_pop($arrArI);
            if ($identity_id) {
                $rtResult[] = $modIdentity->getIdentityByIdFromCache(
                    $identity_id
                );
            }
        }
        // return
        apiResponse(array('identities' => $rtResult));
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

}
