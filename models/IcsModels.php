<?php

require_once dirname(dirname(__FILE__)) . '/lib/invite.php';


class IcsModels extends DataModel {

    protected $modInvite = null;


    public function __construct() {
        $this->modInvite = new Invite;
    }


    public function makeIcs() {
        $this->modInvite
    ->setSubject("Test Demo Invite")
    ->setDescription("The is a test invite for you to see how this thing actually works")
    ->setStart(new DateTime('2013-03-16 12:00AM EST'))
    ->setEnd(new DateTime('2013-03-16 11:59PM EST'))
    ->setLocation("Queens, New York")
    ->setOrganizer("john@doe.com", "John Doe")
    ->addAttendee("ahmad@ahmadamin.com", "Ahmad Amin");
        return $this->modInvite->getInviteContent();
    }

}
