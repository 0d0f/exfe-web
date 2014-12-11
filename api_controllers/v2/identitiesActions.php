<?php

session_write_close();


class IdentitiesActions extends ActionController {

    public function doIndex() {
        $modIdentity = $this->getModelByName('identity');
        $params = $this->params;
        if (!$params['id']) {
            apiError(400, 'no_identity_id', 'identity_id must be provided');
        }
        if ($objIdentity = $modIdentity->getIdentityById($params['id'])) {
            apiResponse(['identity' => $objIdentity]);
        }
        apiError(404, 'identity_not_found', 'identity not found');
    }


    public function doCheckFollowing() {
        // check signin
        $checkHelper = $this->getHelperByName('check');
        $params = $this->params;
        $result = $checkHelper->isAPIAllow('user_edit', $params['token']);
        if ($result['check']) {
            $user_id = $result['uid'];
        } else {
            apiError(401, 'no_signin', '');
        }
        // get models
        $modUser = $this->getModelByName('user');
        // get user
        if (!($objUser = $modUser->getUserById($user_id))) {
            apiError(500, 'update_failed');
        }
        // collecting request data
        if (!($rawId = @splitIdentityId($params['identity_id']))) {
            apiError(400, 'no_identity_id', 'identity_id must be provided');
        }
        // check identity
        if ($rawId[0] && @$rawId[1] === 'wechat') {
            $modWechat = $this->getModelByName('Wechat');
            $identity = $modWechat->getIdentityBy($rawId[0]);
            apiResponse(['following' => !!$identity]);
        }
        apiError(400, 'error_identity_id', 'error wechat identity id');
    }


    public function doGet() {
        // get models
        $modUser       = $this->getModelByName('User');
        $modIdentity   = $this->getModelByName('Identity');
        $modOAuth      = $this->getModelByName('OAuth');
        // get inputs
        $arrIdentities = [];
        if (isset($_POST['identities'])) {
            $arrIdentities = @json_decode($_POST['identities']);
        } else {
            $rawIdentities = @json_decode(file_get_contents('php://input'));
            if ($rawIdentities && isset($rawIdentities->identities)) {
                $arrIdentities = $rawIdentities->identities;
            }
        }
        // ready
        $objIdentities = [];
        // get
        if ($arrIdentities) {
            foreach ($arrIdentities as $identityI => $identityItem) {
                $id_str   = '';
                $identity = null;
                if (@$identityItem->id) {
                    $identity = $modIdentity->getIdentityById($identityItem->id);
                } elseif (@$identityItem->provider
                       && @$identityItem->external_id) {
                    $id_str   = $identityItem->external_id;
                    $identity = $modIdentity->getIdentityByProviderExternalId(
                        $identityItem->provider, $id_str
                    );
                } elseif (@$identityItem->provider
                       && @$identityItem->external_username) {
                    $id_str   = $identityItem->external_username;
                    $identity = $modIdentity->getIdentityByProviderAndExternalUsername(
                        $identityItem->provider, $id_str
                    );
                } else {
                    apiError(400, 'error_identity_info', 'error identity information');
                }
                if ($identity) {
                    $objIdentities[] = $identity;
                } else {
                    switch ($identityItem->provider) {
                        case 'email':
                            $objEmail = Identity::parseEmail($id_str);
                            if ($objEmail) {
                                $name = $identityItem->name ?: $objEmail['name'];
                                $objIdentities[] = new Identity(
                                    0,
                                    $name,
                                    '',
                                    '',
                                    'email',
                                    0,
                                    $objEmail['email'],
                                    $objEmail['email'],
                                    $modIdentity->getGravatarByExternalUsername($name)
                                 ?: getDefaultAvatarUrl($name)
                                );
                            }
                            break;
                        case 'phone':
                            if (validatePhoneNumber($id_str)) {
                                $name = $identityItem->name ?: preg_replace('/^\+.*(.{3})$/', '$1', $id_str);
                                $objIdentities[] = new Identity(
                                    0,
                                    $name,
                                    '',
                                    '',
                                    'phone',
                                    0,
                                    $id_str,
                                    $id_str,
                                    getDefaultAvatarUrl($name)
                                );
                            }
                            break;
                        case 'twitter':
                            if ($identityItem->external_username) {
                                $rawIdentity = $modOAuth->getTwitterProfileByExternalUsername(
                                    $identityItem->external_username
                                );
                                if ($rawIdentity) {
                                    $objIdentities[] = $rawIdentity;
                                }
                            }
                            break;
                        case 'facebook':
                            if ($identityItem->external_username) {
                                $rawIdentity = $modOAuth->getFacebookProfileByExternalUsername(
                                    $identityItem->external_username
                                );
                                if ($rawIdentity) {
                                    $objIdentities[] = $rawIdentity;
                                }
                            }
                    }
                }
            }
            if ($objIdentities) {
                apiResponse(['identities' => $objIdentities]);
            }
            apiError(404, 'identity_not_found', 'identity not found');
        } else {
            apiError(400, 'no_identities', 'identities must be provided');
        }
        apiError(500, 'server_error', "Can't fetch identities.");
    }


    public function doComplete() {
        // get models
        $modUser     = $this->getModelByName('user');
        $modIdentity = $this->getModelByName('identity');
        $modRelation = $this->getModelByName('relation');
        // check signin
        $checkHelper = $this->getHelperByName('check');
        $params = $this->params;
        $result = $checkHelper->isAPIAllow('user_edit', $params['token']);
        if ($result['check']) {
            $user_id = $result['uid'];
        } else {
            apiError(401, 'no_signin', ''); // 需要登录
        }
        // get inputs
        $rangelen  = 50;
        $key       = mb_strtolower(trim($params['key']));
        if ($key === '') {
            apiError(400, 'empty_key_word', 'Keyword can not be empty.');
        }
        // rebuild identities indexes
        $redis = new Redis();
        $redis->connect(REDIS_SERVER_ADDRESS, REDIS_SERVER_PORT);
        $count = $redis->zCard("u:{$user_id}");
        if (!$count) {
            $user = $modUser->getUserById($user_id);
            $modRelation->buildIdentitiesIndexes($user_id);
        }
        // get identities from redis
        $arrResult = array();
        $start = $redis->zRank("u:{$user_id}", $key);
        if (is_numeric($start)) {
            $endflag = false;
            $shResult  = $redis->zRange(
                "u:{$user_id}", $start + 1, $start + $rangelen
            );
            while (sizeof($shResult) > 0) {
                foreach ($shResult as $r) {
                    if ($r[strlen($r) - 1] === '*') {
                        // 根据返回的数据拆解 Key 和匹配的数据。
                        $arr_explode = explode('|', $r);
                        if (sizeof($arr_explode) === 2) {
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
            $arrArI = explode(':', $arI);
            if (($identity_id = (int) $arrArI[1])) {
                switch ($arrArI[0]) {
                    case 'id':
                        $objIdentity = $modRelation->getRelationIdentityById(
                            $identity_id
                        );
                        break;
                    case 'rid':
                        $objIdentity = $modIdentity->getIdentityById(
                            $identity_id
                        );
                        break;
                    default:
                        $objIdentity = null;
                }
                if ($objIdentity) {
                    $rtResult[] = $objIdentity;
                }
            }
        }
        // return
        apiResponse(array('identities' => $rtResult));
    }


    public function doUpdate() {
        // check signin
        $checkHelper = $this->getHelperByName('check');
        $params = $this->params;
        $result = $checkHelper->isAPIAllow('user_edit', $params['token']);
        if ($result['check']) {
            $user_id = $result['uid'];
        } else {
            apiError(401, 'no_signin', '');
        }
        // get models
        $modUser     = $this->getModelByName('user');
        $modIdentity = $this->getModelByName('identity');
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
            $identity['name'] = formatName($_POST['name']);
        }
        if (isset($_POST['bio'])) {
            $identity['bio']  = formatDescription($_POST['bio']);
        }
        // check identity
        foreach ($objUser->identities as $iItem) {
            if ($iItem->id === $identity_id) {
                switch ($iItem->provider) {
                    case 'email':
                    case 'phone':
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
        apiError(401, 'not_allowed', 'only your connected identities can be update');
    }

}
