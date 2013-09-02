<?php

class UsersActions extends ActionController {

    public function doPreferences() {
        $modUser  = $this->getModelByName('User');
        $modPrefe = $this->getModelByName('Preferences');
        $hlpCheck = $this->getHelperByName('check');
        $params   = $this->params;
        $result   = $hlpCheck->isAPIAllow('user', $params['token']);
        if (!$result['check']) {
            if ($result['uid']) {
                $this->jsonError(403, 'not_authorized', 'You can not access the informations of this user.');
            } else {
                $this->jsonError(401, 'invalid_auth');
            }
            return;
        }
        $rawInputs = @strtolower(file_get_contents('php://input'));
        if ($rawInputs) {
            if (($arrInputs = @json_decode($rawInputs, true))) {
                foreach ($arrInputs ?: [] as $key => $value) {
                    switch ($key) {
                        case 'locale':
                            $arrInputs[$key] = trim($value);
                            break;
                        case 'timezone':
                            $strTimezone = '';
                            foreach (DateTimeZone::listAbbreviations() as $group) {
                                foreach ($group as $timezone) {
                                    if (strtolower(trim($timezone['timezone_id'])) === $value) {
                                        $strTimezone = trim($timezone['timezone_id']);
                                        break;
                                    }
                                }
                            }
                            if (!$strTimezone) {
                                $this->jsonError(400, 'unknow_timezone');
                                return;
                            }
                            $arrInputs[$key] = $strTimezone;
                            break;
                        case 'routex':
                            $arrInputs[$key] = $value;
                            break;
                        default:
                            $this->jsonError(400, 'unknow_preferences', $key);
                            return;
                    }
                }
                $udResult = $modPrefe->updatePreferences($result['uid'], $arrInputs);
                if (!$udResult) {
                    $this->jsonError(400, 'error_preferences');
                    return;
                }
            } else {
                $this->jsonError(400, 'error_preferences');
                return;
            }
        }
        $preferences = $modPrefe->getPreferencesBy($result['uid']);
        if ($preferences) {
            $this->jsonResponse($preferences);
            return;
        }
        $this->jsonError(500, 'server_error');
    }

}
