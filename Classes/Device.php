<?php

class Device extends EFObject {

    public $name               = null;

    public $brand              = null;

    public $model              = null;

    public $os_name            = null;

    public $os_version         = null;

    public $description        = null;

    public $status             = null;

    public $first_connected_at = null;

    public $last_connected_at  = null;

    public $disconnected_at    = null;


    public function __construct(
        $id                 = 0,
        $name               = '',
        $brand              = '',
        $model              = '',
        $os_name            = '',
        $os_version         = '',
        $description        = '',
        $status             = false,
        $first_connected_at = '',
        $last_connected_at  = '',
        $disconnected_at    = ''
    ) {
        parent::__construct($id, 'device');

        $first_connected_at       = $first_connected_at ?: '0000-00-00 00:00:00';
        $last_connected_at        = $last_connected_at
                                 && $last_connected_at !== '0000-00-00 00:00:00'
                                  ? $last_connected_at : $first_connected_at;
        $disconnected_at          = $disconnected_at    ?: '0000-00-00 00:00:00';

        $this->name               = $name               ?: '';
        $this->brand              = $brand              ?: '';
        $this->model              = $model              ?: '';
        $this->os_name            = $os_name            ?: '';
        $this->os_version         = $os_version         ?: '';
        $this->description        = $description        ?: '';
        $this->status             = $status             ? 'CONNECTED' : 'DISCONNECTED';
        $this->first_connected_at = $first_connected_at . ' +0000';
        $this->last_connected_at  = $last_connected_at  . ' +0000';
        $this->disconnected_at    = $disconnected_at    . ' +0000';
    }

}
