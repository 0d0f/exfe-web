<?php

class IdentityActions extends ActionController
{

    public function doGet()
    {
        $IdentityData  = $this->getModelByName('identity');

        $arrIdentities = json_decode($_GET['identities'], true);

        $responobj['response']['identities'] = array();

        foreach ($arrIdentities as $identityI => $identityItem) {
            $identity = $IdentityData->getIdentity($identityItem['id']);

            if (intval($identity['id']) > 0) {
                if (!$identity['avatar_file_name'] || !$identity['name']) {
                    $userData = $this->getModelByName('user');
                    $user     = $userData->getUserProfileByIdentityId($identity['id']);
                    $identity = humanIdentity($identity, $user);

                    //get user default
                    //if ($identity['avatar_file_name'] == '')
                    //    $identity['avatar_file_name'] = $user['avatar_file_name'];
                    //if ($identity['avatar_file_name'] == '')
                    //    $identity['avatar_file_name'] = 'default.png';
                    //if ($identity['name'] == '')
                    //    $identity['name'] = $user['name'];
                }
            }

            if ($identity) {
                $responobj['response']['identities'][] = $identity;
            }
        }

        $responobj['meta']['code'] = 200;
        //$responobj['meta']['errType'] = 'Bad Request';
        //$responobj['meta']['errorDetail'] = 'invalid_auth';

        echo json_encode($responobj);

        exit();
    }

}
