<?php
session_write_close();
require_once dirname(dirname(__FILE__))."/lib/tmhOAuth.php";


class IdentityActions extends ActionController {

    public function doIndex() {
        
    }
    
    
    public function doGet() {
        // get models
        $modUser       = $this->getModelByName('user',     'v2');
        $modIdentity   = $this->getModelByName('identity', 'v2');
        // get inputs
        $arrIdentities = trim($_POST['external_ids']) ? json_decode($_POST['identities']) : array();
        $bolWithUserIdentityStatus = intval($_POST['with_user_identity_status']);
        // ready
        $responobj['response']['identities'] = array();
        // get
        if ($arrIdentities) {
            foreach ($arrIdentities as $identityI => $identityItem) {
                if (!$identityItem->provider) {
                    continue;
                }
                $identity = $IdentityData->getIdentityByProviderExternalId(
                    $identityItem->provider, $identityItem->external_id
                );
                if ($identity) {
                    if ($bolWithUserIdentityStatus) {
                        $identity->user_identity_status = $modUser->getUserIdentityStatusByUserIdAndIdentityId(
                            0, $identity->id, true
                        );
                    }
                    $responobj['response']['identities'][] = $identity;
                } else {
                    switch ($identityItem->provider) {
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
                                    $objIdentity = new Identity(
                                        $twitterUser['name'],
                                        $twitterUser['description'],
                                        'twitter',
                                        0,
                                        "@{$twitterUser['screen_name']}@twitter",
                                        $twitterUser['screen_name'],
                                        $modIdentity->getTwitterLargeAvatarBySmallAvatar(
                                            $twitterUser['profile_image_url']
                                        )
                                    );
                                    if ($bolWithUserIdentityStatus) {
                                        $objIdentity->user_identity_status = 'NEWIDENTITY';
                                    }
                                    $responobj['response']['identities'][] = $objIdentity;
                                }
                            }
                    }
                }
            }
        }
        $responobj['meta']['code'] = 200;
        echo json_encode($responobj);
    }
    
    
    public function doDeleteIdentity() {
        $modUser     = $this->getModelByName('user',     'v2');
        $modIdentity = $this->getModelByName('identity', 'v2');
        // collecting post data
        if (!($user_id = $_SESSION['signin_user']->id)) {
            // 需要登录
        }
        if (!($identity_id = intval($_POST['identity_id']))) {
            // 需要输入identity_id
        }
        if (!($curPassword = $_POST['current_password'])) {
            // 请输入当前密码
        }
        if (!$modUser->verifyUserPassword($user_id, $curPassword)) {
            // 密码错误
        }
        if (($relation = $modUser->getUserIdentityStatusByUserIdAndIdentityId($user_id, $identity_id)) === null) {
            // 用户和身份没关系
        }
        if (($acResult = $modIdentity->deleteIdentityFromUser($identity_id, $user_id))) {
            // 成功
        }
        // 操作失败
    }
    
    
    public function doSetDefaultIdentity() {
        $modUser     = $this->getModelByName('user',     'v2');
        $modIdentity = $this->getModelByName('identity', 'v2');
        if (!($user_id = $_SESSION['signin_user']->id)) {
            // 需要登录
        }
        if (!($identity_id = intval($_POST['identity_id']))) {
            // 需要输入identity_id
        }
        if (!($curPassword = $_POST['current_password'])) {
            // 请输入当前密码
        }
        if (!$modUser->verifyUserPassword($user_id, $curPassword)) {
            // 密码错误
        }
        switch ($modUser->getUserIdentityStatusByUserIdAndIdentityId($user_id, $identity_id)) {
            case 'RELATED':
            case 'CONNECTED':
                break;
            default:
                // 用户和身份关系不正确
        }
        if ($modIdentity->setIdentityAsDefaultIdentityOfUser($identity_id, $user_id)) {
            // 成功
        }
        // 操作失败
    }
    
    
    public function doUpdate() {
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
        $id = $objIdentity->updateIdentityById(
            $id,
            array('provider'          => $provider,       
                  'external_id'       => $external_id,    
                  'name'              => $name,           
                  'nickname'          => $nickname,       
                  'bio'               => $bio,         
                  'avatar_filename'   => $avatar_filename,
                  'external_username' => $external_username),
        );
        echo json_encode(array('identity_id' => $id));
    }

}
