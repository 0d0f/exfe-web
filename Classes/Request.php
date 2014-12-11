<?php

class Request extends Metainfo {

    public $exfee_id    = null;

    public $by_identity = null;

    public $updated_by  = null;

    public $status      = null;

    public $created_at  = null;

    public $updated_at  = null;

    public $message     = null;


    public function __construct(
        $id          = 0,
        $exfee_id    = 0,
        $by_identity = null,
        $updated_by  = null,
        $status      = 0,
        $message     = '',
        $created_at  = '',
        $updated_at  = ''
    ) {
        parent::__construct((int) $id, 'request');

        $created_at        = $created_at ?: date('Y-m-d H:i:s');
        $updated_at        = $updated_at   && $updated_at !== '0000-00-00 00:00:00'
                           ? $updated_at    : $created_at;

        $arrStatus         = ['requesting', 'approved', 'declined', 'giveup'];
        $this->exfee_id    = (int) $exfee_id;
        $this->by_identity = $by_identity ?: null;
        $this->updated_by  = $updated_by   ?: null;
        $this->status      = $arrStatus[$status];
        $this->created_at  = $created_at  . ' +0000';
        $this->updated_at  = $updated_at    . ' +0000';
        $this->message     = $message      ?: '';
    }

}
