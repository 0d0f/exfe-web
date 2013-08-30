<?php

class PreferencesModels extends DataModel {

    protected $hlpUser = null;


    protected function updateIdentityPreferences($identity_id, $sql) {
        delCache("identities:{$identity_id}");
        return $this->query(
            "UPDATE `identities` SET {$sql} WHERE `id` = {$identity_id};"
        );
    }


    public function __construct() {
        $this->hlpUser = $this->getHelperByName('User');
    }


    public function getPreferencesBy($user_id) {
        if ($user_id) {
            $user = $this->hlpUser->getRawUserById($user_id);
            if ($user) {
                return [
                    'locale'   => $user['locale'],
                    'timezone' => $user['timezone'],
                ] + (json_decode($user['preferences'], true) ?: []);
            }
        }
        return null;
    }


    public function updatePreferences($user_id, $preferences = []) {
        if ($user_id && $preferences) {
            $sqlUsr = [];
            $sqlIdt = [];
            if (isset($preferences['locale'])   && $preferences['locale']) {
                $strItem  = "`locale`   = '" . dbescape($preferences['locale'])   . "'";
                $sqlUsr[] = $strItem;
                $sqlIdt[] = $strItem;
            }
            if (isset($preferences['timezone']) && $preferences['timezone']) {
                $strItem  = "`timezone` = '" . dbescape($preferences['timezone']) . "'";
                $sqlUsr[] = $strItem;
                $sqlIdt[] = $strItem;
            }
            $curPfrs = $this->getPreferencesBy($user_id) ?: [];
            if (isset($curPfrs['locale'])) {
                unset($curPfrs['locale']);
            }
            if (isset($curPfrs['timezone'])) {
                unset($curPfrs['timezone']);
            }
            $prfChanged = false;
            if (isset($preferences['routex'])) {
                if (!isset($curPfrs['routex'])) {
                    $curPfrs['routex'] = [];
                }
                if (isset($preferences['routex']['background_gps'])) {
                    $curPfrs['routex']['background_gps'] = !!$preferences['routex']['background_gps'];
                    $prfChanged = true;
                }
            }
            if ($prfChanged) {
                $sqlUsr[] = "`preferences` = '" . json_encode($curPfrs) . "'";
            }
            if (($strSql = implode(', ', $sqlUsr))) {
                $this->query("UPDATE `users` SET `updated_at` = NOW(), {$strSql} WHERE `id` = {$user_id};");
                delCache("users:{$user_id}");
            }
            if (($strSql = implode(', ', $sqlIdt))) {
                $identity_ids = $this->hlpUser->getIdentityIdsByUserId($user_id);
                foreach ($identity_ids ?: [] as $id) {
                    $this->updateIdentityPreferences($id, $strSql);
                }
            }
            return true;
        }
        return false;
    }

}
