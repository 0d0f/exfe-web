<?php
require_once 'Metainfo.php';
require_once 'EFTime.php';
require_once 'Widget.php';
require_once 'User.php';
require_once 'Identity.php';
require_once 'Exfee.php';
require_once 'Invitation.php';
require_once 'Recipient.php';

abstract class EFobject{

    public $id   = null;

    public $type = null;

    public function __construct($id = 0, $type = 'EFObject') {
        $this->id   = intval($id);
        $this->type = $type;
    }

}

