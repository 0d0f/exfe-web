<?php

session_write_close();


class IdentitiesActions extends ActionController {

    public function doGet() {
        // get models
        $modUser       = $this->getModelByName('User');
        $modIdentity   = $this->getModelByName('Identity');
        $modOAuth      = $this->getModelByName('OAuth');
        // get inputs
        $arrIdentities = @json_decode(file_get_contents('php://input'));
        // ready
        $objIdentities = [];
        $error         = [];
        // get
        if ($arrIdentities && is_array($arrIdentities)) {
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
                    $objIdentities[] = null;
                    $error["id_{$identityI}"] = 'error_identity_information';
                    continue;
                }
                if ($identity) {
                    $objIdentities[] = $identity;
                    continue;
                }
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
                        } else {
                            $objIdentities[] = null;
                            $error["id_{$identityI}"] = 'error_identity_information';
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
                        } else {
                            $objIdentities[] = null;
                            $error["id_{$identityI}"] = 'error_identity_information';
                        }
                        break;
                    case 'twitter':
                        if ($identityItem->external_username) {
                            $rawIdentity = $modOAuth->getTwitterProfileByExternalUsername(
                                $identityItem->external_username
                            );
                            if ($rawIdentity) {
                                $objIdentities[] = $rawIdentity;
                            } else {
                                $objIdentities[] = null;
                                $error["id_{$identityI}"] = 'identity_not_found';
                            }
                        } else {
                            $objIdentities[] = null;
                            $error["id_{$identityI}"] = 'error_identity_information';
                        }
                        break;
                    case 'facebook':
                        if ($identityItem->external_username) {
                            $rawIdentity = $modOAuth->getFacebookProfileByExternalUsername(
                                $identityItem->external_username
                            );
                            if ($rawIdentity) {
                                $objIdentities[] = $rawIdentity;
                            } else {
                                $objIdentities[] = null;
                                $error["id_{$identityI}"] = 'identity_not_found';
                            }
                        } else {
                            $objIdentities[] = null;
                            $error["id_{$identityI}"] = 'error_identity_information';
                        }
                        break;
                    default:
                        $objIdentities[] = null;
                        $error["id_{$identityI}"] = 'unsupported_provider';
                }
            }
            // output {
            $success = false;
            foreach ($objIdentities as $item) {
                if ($item !== null) {
                    $success = true;
                    break;
                }
            }
            if ($success) {
                $this->jsonResponse($objIdentities, $error ? 206 : 200, $error);
            } else {
                $count404 = 0;
                foreach ($error as $item) {
                    if ($item === 'identity_not_found') {
                        $count404++;
                    }
                }
                $this->jsonError(
                    sizeof($error) === $count404 ? 404 : 400,
                    'error_identity_information',
                    'error identity information',
                    $error
                );
            }
            return;
            // }
        }
        $this->jsonError(400, 'no_identity_information', 'identity informations must be provided');
    }

}
