<?php

require_once dirname(dirname(__FILE__)) . '/lib/httpkit.php';


class RoutexModels extends DataModel {

    public function createRouteX($identity, $place = null) {
        // init cross
        $cross              = new stdClass;
        $cross->attribute   = new stdClass;
        $cross->description = '';
        $cross->type        = 'Cross';
        $cross->place       = $place ?: new Place();
        $cross->by_identity = $identity;
        $cross->widget      = [new Background('wechat.jpg')];
        $cross->attribute->state = 'published';
        // add time
        $hlpTime = $this->getHelperByName('Time');
        $cross->time = $hlpTime->parseTimeString(
            'Today',
            $hlpTime->getDigitalTimezoneBy($identity->timezone) ?: '+08:00 GMT'
        );
        $timeArray = explode('-', $cross->time->begin_at->date);
        $cross->title = "{$identity->name}的活点地图 " . (int)$timeArray[1] . '月' . (int)$timeArray[2] . '日';
        // add exfee
        $hlpIdentity = $this->getHelperByName('Identity');
        $bot = $hlpIdentity->getIdentityById(explode(',', SMITH_BOT)[0]);
        $now = time();
        $cross->exfee = new Exfee;
        $cross->exfee->invitations = [
            new Invitation(
                0, $identity, $identity, $identity,
                'ACCEPTED', 'EXFE', '', $now, $now, true,  0, []
            ),
            new Invitation(
                0, $bot,      $identity, $identity,
                'ACCEPTED', 'EXFE', '', $now, $now, false, 0, []
            )
        ];
        // gather
        $crossHelper = $this->getHelperByName('Cross');
        $gtResult = $crossHelper->gatherCross(
            $cross, $identity->id, $identity->connected_user_id
        );
        if (($cross->id = @ (int) $gtResult['cross_id'])) {
            touchCross($cross->id, $identity->connected_user_id);
            // get invitation
            $exfeeHelper = $this->getHelperByName('Exfee');
            $invitation = $exfeeHelper->getRawInvitationByCrossIdAndIdentityId(
                $cross->id, $bot->id
            );
            if ($invitation) {
                return [
                    'cross'      => $cross,
                    'invitation' => $invitation,
                    'url'        => $this->getUrl($cross->id, $invitation['token'], $identity),
                ];
            }
        }
        return null;
    }


    public function getUrl($cross_id, $token, $identity) {
        return SITE_URL
             . "/!{$cross_id}/routex?xcode={$token}"
             . "&via={$identity->external_username}@{$identity->provider}";
    }


    public function getRoutexStatusBy($cross_id, $user_id) {
        $key    = "routex_status:{$cross_id}";
        $subKey = "user_{$user_id}";
        $rawStatus = getCache($key);
        if ($rawStatus) {
            if (array_key_exists($subKey, $rawStatus)) {
                return $rawStatus[$subKey];
            }
        } else {
            $rawStatus = [];
        }
        $rawResult = httpKit::request(
            EXFE_AUTH_SERVER
          . "/v3/routex/_inner/users/{$user_id}/crosses/{$cross_id}",
            null, null, false, false, 3, 3, 'json', true, true
        );
        $userStatus = -1;
        if ($rawResult && $rawResult['http_code'] === 200) {
            $userStatus = $rawResult['json'];
        }
        $rawStatus[$subKey] = $userStatus;
        setCache($key, $rawStatus);
        return $userStatus;
    }


    public function delRoutexStatusCache($cross_id) {
        delCache("routex_status:{$cross_id}");
    }

}
