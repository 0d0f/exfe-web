<?php

require_once dirname(dirname(__FILE__)) . '/lib/invite.php';


class IcsModels extends DataModel {

    public function makeIcs($cross) {
        // basic check
        if (!$cross
         || !$cross->time->origin
         || !$cross->time->begin_at->timezone
         || !$cross->time->begin_at->date) {
            return '';
        }
        // init
        $modInvite = new Invite("exfe_cross_{$cross->id}");
        // make time
        if ($cross->time->begin_at->time) {
            $intTime = strtotime("{$cross->time->begin_at->date} {$cross->time->begin_at->time} UTC");
            $difTime = 60 * 60;
        } else {
            $intTime = strtotime("{$cross->time->begin_at->date}");
            $difTime = 60 * 60 * 24;
        }
        $begin_at = (new DateTime)->setTimestamp($intTime);
        $end_at   = (new DateTime)->setTimestamp($intTime + $difTime);
        if (!$cross->time->begin_at->time) {
            $modInvite->all_day_begin = 'VALUE=DATE:' . $begin_at->format('Ymd');
            $modInvite->all_day_end   = 'VALUE=DATE:' . $end_at->format('Ymd');
        }
        // make place
        if ($cross->place
        &&  $cross->place->id
        && ($cross->place->title
        ||  $cross->place->description)) {
            $strPlace =  $cross->place->title
                      . ($cross->place->title ? '\n' : '')
                      .  $cross->place->description;
        } else {
            $strPlace = '';
        }
        // make
        $modInvite->setSubject($cross->title)
                  ->setDescription($cross->description)
                  ->setStart($begin_at)
                  ->setEnd($end_at)
                  ->setLocation($strPlace)
                  ->setOrganizer('', '');
        // parse invitations
        foreach ($cross->exfee->invitations as $invItem) {
            if ($invItem->rsvp_status !== 'NOTIFICATION') {
                $email = $invItem->identity->provider === 'email'
                       ? $invItem->identity->external_username : '';
                $modInvite->addAttendee(
                    $email, $invItem->identity->name
                );
                if ($invItem->host) {
                    $modInvite->setOrganizer(
                        $email, $invItem->identity->name
                    );
                }
            }
        }
        // return
        return $modInvite->getInviteContent();
    }

}
