<?php

class wechatActions extends ActionController {

    public function doIndex() {

    }


    public function doCallback() {
        $modWechat = $this->getModelByName('wechat');
        $params    = $this->params;
        if ($params['echostr']) {
            if ($modWechat->valid(
                $params['signature'],
                $params['timestamp'],
                $params['nonce']
            )) {
                echo $params['echostr'];
                return;
            }
        } else {
            $modUser     = $this->getModelByName('User');
            $modIdentity = $this->getModelByName('Identity');
            $rawInput = file_get_contents('php://input');
            $objMsg   = $modWechat->unpackMessage($rawInput);
            error_log(json_encode($objMsg));
            switch (@$objMsg->MsgType) {
                case 'event':
                    if (!($external_id = @$objMsg->FromUserName ? "{$objMsg->FromUserName}" : '')) {
                        error_log('Empty FromUserName');
                        return;
                    }
                    if (!($rawIdentity = $modWechat->getIdentityBy($external_id))) {
                        // 500
                        return;
                    }
                    $identity = $modIdentity->getIdentityByProviderAndExternalUsername(
                        $rawIdentity->provider,
                        $rawIdentity->external_username
                    );
                    if ($identity) {
                        $identity_id = $identity->id;
                    } else {
                        $identity_id = $modIdentity->addIdentity([
                            'provider'          => $rawIdentity->provider,
                            'external_id'       => $rawIdentity->external_id,
                            'name'              => $rawIdentity->name,
                            'external_username' => $rawIdentity->external_username,
                            'avatar'            => $rawIdentity->avatar,
                            'avatar_filename'   => $rawIdentity->avatar_filename
                        ]);
                        $identity    = $modIdentity->getIdentityById($identity_id);
                    }-
                    if (!$identity) {
                        // 500
                        return;
                    }
                    switch (@$objMsg->Event) {
                        case 'subscribe':
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
                                // $modIdentity->sendVerification(
                                //     'Welcome', $identity, '', false, $identity->name ?: ''
                                // );
                            }
                            if (!$user_id) {
                                // 500
                                return;
                            }
                            $strReturn = $modWechat->packMessage(
                                $identity->external_username,
                                "Welcome {$identity->name} 欢迎欢迎，测试测试，中文中文。"
                            );
                            if (!$strReturn) {
                                // 500
                                return;
                            }
                            echo $strReturn;
                            break;
                        case 'unsubscribe':
                            // check user
                            $user_infos = $modUser->getUserIdentityInfoByIdentityId($identity_id);
                            if (!isset($user_infos['CONNECTED'])) {
                                // 400
                                return;
                            }
                            $modIdentity->revokeIdentity($identity_id);
                            break;
                        default:
                            error_log('Unknow Event');
                    }
                    break;
                default:
                    error_log('Unknow MsgType');
            }
        }
    }

}
