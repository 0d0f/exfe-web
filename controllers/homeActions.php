<?php

class HomeActions extends ActionController {

    public function doIndex() {
        // rsvp
        $modCross = $this->getModelByName('Cross');
        $modExfee = $this->getModelByName('Exfee');
        $modUser  = $this->getModelByName('User');
        $token = dbescape($_GET['token']);
        $rsvp  = strtolower(dbescape($_GET['rsvp']));
        if ($token && $rsvp) {
            if (($objToken = $modExfee->getRawInvitationByToken($token))
             && $objToken['valid']
             && $rsvp === 'accept') {
                $user_id = $modUser->getUserIdByIdentityId($objToken['identity_id']);
                $rsvp    = new stdClass;
                $rsvp->identity_id    = $objToken['identity_id'];
                $rsvp->rsvp_status    = 'ACCEPTED';
                $rsvp->by_identity_id = $objToken['identity_id'];
                $rawCross = $modCross->getCross($objToken['cross_id']);
                $modExfee->updateExfeeRsvpById(
                    $objToken['exfee_id'], [$rsvp], $objToken['identity_id'],
                    $user_id, (int) $rawCross['state'] === 0
                );
                touchCross($objToken['cross_id'], $user_id);
                header("location: /#!token={$token}");
            } else if ($rsvp === 'accept') {
                header("location: /#!token={$token}/accept");
            }
        }
        // get sms token
        $this->setVar('sms_token', null);
        if (isset($_GET['t'])) {
            $t = dbescape($_GET['t']);
            if (($objToken = $modUser->resolveToken($t))) {
                $objToken['origin_token'] = $t;
            }
            $this->setVar('sms_token', $objToken ?: false);
        }
        // load models
        $modOauth      = $this->getModelByName('OAuth');
        $modBackground = $this->getModelByName('Background');
        $modMap        = $this->getModelByName('Map');
        // check oauth session
        $oauthIfo      = $modOauth->getSession();
        if (VERBOSE_LOG) {
            error_log('OAUTH_ENV: ' . json_encode($oauthIfo));
        }
        // check xcode {
        $smith_id    = 0;
        $exfee_id    = 0;
        $cross       = null;
        $joined      = false;
        $title       = 'EXFE - The group utility for gathering.';
        if (isset($_GET['xcode'])) {
            $invitation = $modExfee->getRawInvitationByToken($_GET['xcode']);
            if ($invitation && $invitation['state'] !== 4) {
                if (in_array($invitation['identity_id'], explode(',', SMITH_BOT))) {
                    $smith_id = $invitation['identity_id'];
                }
                $exfee_id = $invitation['exfee_id'];
                $hlpCross = $this->getHelperByName('Cross');
                $cross = $hlpCross->getCross($invitation['cross_id']);
                if ($cross && $cross->title) {
                    $title = $cross->title;
                }
                if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')) {
                    if (@$_COOKIE['user_token'] && @$_COOKIE['wechat_open_id']) {
                        // @todo check open_id!!! by @leask
                        $objToken = $modUser->getUserToken($_COOKIE['user_token']);
                        if ($objToken && ($user_id = @$objToken['data']['user_id'])) {
                            $modIdentity = $this->getModelByName('Identity');
                            $via_identity = null;
                            if (($via = @$_GET['via'] ?: '')) {
                                $external_username = preg_replace('/^(.*)@[^@]*$/', '$1', $via);
                                $provider          = preg_replace('/^.*@([^@]*)$/', '$1', $via);
                                $via_identity      = $modIdentity->getIdentityByProviderAndExternalUsername(
                                    $provider, $external_username
                                );
                            } else {
                                $via_identity = $modIdentity->getIdentityById(explode(',', SMITH_BOT)[0]);
                            }
                            if ($via_identity) {
                                $user = $modUser->getUserById($user_id);
                                if ($user && $user->identities) {
                                    if ($cross && $cross->attribute['state'] !== 'deleted') {
                                        $exfee = $modExfee->getExfeeById($exfee_id, true);
                                        $removed  = false;
                                        $viaFound = false;
                                        $done     = false;
                                        foreach ($exfee->invitations as $invitaion) {
                                            if ($invitaion->identity->connected_user_id === $user_id) {
                                                if ($invitaion->rsvp_status === 'REMOVED') {
                                                    $removed = true;
                                                } else {
                                                    $modRoutex = $this->getModelByName('Routex');
                                                    $rtResult = $modRoutex->getRoutexStatusBy($cross->id, $user_id);
                                                    if ($rtResult !== -1) {
                                                        $cross->widget[] = [
                                                            'type'      => 'routex',
                                                            'my_status' => $rtResult,
                                                        ];
                                                    }
                                                    touchCross($cross->id, $user_id);
                                                    $joined = true;
                                                    $done = true;
                                                    break;
                                                }
                                            }
                                            if ($invitaion->identity->connected_user_id === $via_identity->connected_user_id
                                             || $invitaion->identity->id                === $via_identity->id) {
                                                $viaFound = true;
                                            }
                                        }
                                        if (!$done && !$removed && $viaFound) {
                                            $exfee     = new Exfee;
                                            $exfee->id = $exfee_id;
                                            $exfee->invitations = [];
                                            $wechatIdentity = null;
                                            $phoneIdentity  = null;
                                            $emailIdentity  = null;
                                            foreach ($user->identities as $identity) {
                                                switch ($identity->provider) {
                                                    case 'phone':
                                                        $phoneIdentity  = $phoneIdentity  ?: $identity;
                                                        break;
                                                    case 'wechat':
                                                        $wechatIdentity = $wechatIdentity ?: $identity;
                                                        break;
                                                    case 'email':
                                                        $emailIdentity  = $emailIdentity  ?: $identity;
                                                }
                                            }
                                            if ($wechatIdentity) {
                                                $objInvitation = new stdClass;
                                                $objInvitation->id = 0;
                                                $objInvitation->identity = $wechatIdentity;
                                                $objInvitation->response = 'NORESPONSE';
                                                $exfee->invitations[] = $objInvitation;
                                            }
                                            if ($phoneIdentity) {
                                                $objInvitation = new stdClass;
                                                $objInvitation->id = 0;
                                                $objInvitation->identity = $phoneIdentity;
                                                $objInvitation->response = 'NORESPONSE';
                                                $exfee->invitations[] = $objInvitation;
                                            } else if ($emailIdentity) {
                                                $objInvitation = new stdClass;
                                                $objInvitation->id = 0;
                                                $objInvitation->identity = $emailIdentity;
                                                $objInvitation->response = 'NORESPONSE';
                                                $exfee->invitations[] = $objInvitation;
                                            }
                                            $udeResult = $modExfee->updateExfee(
                                                $exfee, $via_identity->id, $via_identity->connected_user_id, false, false, false, '', true
                                            );
                                            if ($udeResult && $udeResult['changed']) {
                                                $cross = $hlpCross->getCross($invitation['cross_id']);
                                                $joined = true;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        $workflow = ['callback' => ['url' => SITE_URL . $_SERVER['REQUEST_URI']]];
                        $urlOauth = $modOauth->wechatRedirect($workflow, 1);
                        header("location: {$urlOauth}");
                        return;
                    }
                }
            }
        } else {
            $rawId  = @$this->tails[1] ?: '';
            $regExp = '/^!(.*)$/';
            if (preg_match($regExp, $rawId)) {
                $cross_id = (int) preg_replace($regExp, '$1', $rawId);
                $exfee_id = (int) $modExfee->getExfeeIdByCrossId($cross_id);
            }
        }
        // }
        $this->setVar('smith_id', $smith_id);
        $this->setVar('exfee_id', $exfee_id);
        $this->setVar('cross',    json_encode($cross));
        $this->setVar('joined',   $joined);
        $oauthRst      = null;
        if ($oauthIfo) {
            $oauthRst  = ['authorization' => null];
            if ($oauthIfo['oauth_signin']) {
                $oauthRst['authorization']   = $oauthIfo['oauth_signin'];
                $oauthRst['data']            = [
                    'identity'        => $oauthIfo['identity'],
                    'identity_status' => $oauthIfo['identity_status'],
                ];
                if ($oauthIfo['identity']->provider === 'twitter') {
                    $oauthRst['data']['twitter_following'] = $oauthIfo['twitter_following'];
                }
                if (isset($oauthIfo['workflow'])
                 && isset($oauthIfo['workflow']['verification_token'])) {
                    $oauthRst['verification_token'] = $oauthIfo['workflow']['verification_token'];
                }
            }
            if (isset($oauthIfo['workflow'])) {
                if (isset($oauthIfo['workflow']['callback']['url'])) {
                    $oauthRst['refere'] = $oauthIfo['workflow']['callback']['url'];
                }
                if (isset($oauthIfo['workflow']['callback']['args'])) {
                    $oauthRst['event']  = $oauthIfo['workflow']['callback']['args'];
                }
            }
        }
        $modOauth->resetSession();
        // show page
        $this->setVar('title',       $title);
        $this->setVar('backgrounds', $modBackground->getAllBackground());
        $this->setVar('oauth',       $oauthRst);
        $this->setVar('location',    $modMap->getCurrentLocation());
        // case USER_AGENT
        $ipad = isset($_GET['ipad']) || isset($_COOKIE['ipad']);
        if (isset($_GET['ipad'])) {
            if ($_GET['ipad'] === 'false') {
                setcookie('ipad', false, time() - 60 * 60 * 24);
                $ipad = false;
            } else {
                setcookie('ipad', true,  time() + 60 * 60 * 24);
            }
        }
        if (!$ipad
         && (strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone')
          || strpos($_SERVER['HTTP_USER_AGENT'], 'iPad')
          || strpos($_SERVER['HTTP_USER_AGENT'], 'iPod')
          || strpos($_SERVER['HTTP_USER_AGENT'], 'Android')
          || strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger'))) {
            $this->displayViewByAction('mobile');
            return;
        } else if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')
               && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 8.0')
                || strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 7.0')
                || strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 6.0')
                || strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 5.5')
                || strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 5.0'))) {
            $this->displayViewByNameAction('matters', 'browser_matters');
            return;
        } else if (strpos($_SERVER['HTTP_USER_AGENT'], 'BlackBerry')
                || strpos($_SERVER['HTTP_USER_AGENT'], 'Nokia')
                || strpos($_SERVER['HTTP_USER_AGENT'], 'Symbian')
                || strpos($_SERVER['HTTP_USER_AGENT'], 'SymbOS')
                || strpos($_SERVER['HTTP_USER_AGENT'], 'Series 60')
                || strpos($_SERVER['HTTP_USER_AGENT'], 'S60')
                || strpos($_SERVER['HTTP_USER_AGENT'], 'Windows Phone')
                || strpos($_SERVER['HTTP_USER_AGENT'], 'Windows CE')
                || strpos($_SERVER['HTTP_USER_AGENT'], 'IEMobile')
                || strpos($_SERVER['HTTP_USER_AGENT'], 'SonyEricsson')
                || strpos($_SERVER['HTTP_USER_AGENT'], 'Kindle')
                || strpos($_SERVER['HTTP_USER_AGENT'], 'NetFront')
                || strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mini')
                || strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mobi')
                || strpos($_SERVER['HTTP_USER_AGENT'], 'J2ME')
                || strpos($_SERVER['HTTP_USER_AGENT'], 'MIDP')
                || strpos($_SERVER['HTTP_USER_AGENT'], 'Skyfire')
                || strpos($_SERVER['HTTP_USER_AGENT'], 'Fennec')) {
            // @todo
            // return;
        }
        $this->displayView();
    }

}
