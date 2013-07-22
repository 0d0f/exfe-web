<?php

require_once 'Metainfo.php';
require_once 'EFTime.php';
require_once 'Widget.php';
require_once 'User.php';
require_once 'Identity.php';
require_once 'Exfee.php';
require_once 'Invitation.php';
require_once 'Device.php';
require_once 'Recipient.php';
require_once 'Photo.php';
require_once 'PhotoX.php';
require_once 'Response.php';
require_once 'Vote.php';
require_once 'Option.php';
require_once 'Request.php';
require_once 'Requestaccess.php';


abstract class EFobject{

    public $id   = null;

    public $type = null;


    public function __construct($id = 0, $type = 'EFObject') {
        $this->id   = intval($id);
        $this->type = $type;
    }

}

