<?php

class Request extends Metainfo {

    public $exfee_id     = null;

    public $requested_by = null;

    public $updated_by   = null;

    public $status       = null;

    public $requested_at = null;

    public $updated_at   = null;

    public $message      = null;


    public function __construct(
        $id           = 0,
        $exfee_id     = 0,
        $requested_by = 0,
        $updated_by   = 0,
        $status       = 0,
        $message      = '',
        $requested_at = '',
        $updated_at   = ''
    ) {
        parent::__construct((int) $id, 'requestaccess');

        $requested_at       = $requested_at ?: date('Y-m-d H:i:s');
        $updated_at         = $updated_at   && $updated_at !== '0000-00-00 00:00:00'
                            ? $updated_at    : $requested_at;

        $arrStatus          = ['requesting', 'approved', 'declined', 'giveup'];
        $this->exfee_id     = (int) $exfee_id;
        $this->requested_by = $requested_by ?: null;
        $this->updated_by   = $updated_by   ?: null;
        $this->status       = $arrStatus[$status];
        $this->requested_at = $requested_at  . ' +0000';
        $this->updated_at   = $updated_at    . ' +0000';
        $this->message      = $message      ?: '';
    }

}
