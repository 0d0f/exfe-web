<?php

class Vote extends EFObject {

    public $status      = null;

    public $title       = null;

    public $description = null;

    public $vote_type   = null;

    public $created_by  = null;

    public $updated_by  = null;

    public $created_at  = null;

    public $updated_at  = null;

    public $choice      = '';

    public $anonymous   = null;

    public $options     = [];


    public function __construct(
        $id          = 0,
        $status      = 0,
        $title       = '',
        $description = '',
        $vote_type   = '',
        $choice      = 'radio', // radio / multiple
        $anonymous   = false,
        $created_by  = null,
        $updated_by  = null,
        $created_at  = '',
        $updated_at  = '',
        $options     = []
    ) {
        parent::__construct($id, 'vote');

        $created_at        = $created_at ?: date('Y-m-d H:i:s');
        $updated_at        = $updated_at && $updated_at !== '0000-00-00 00:00:00'
                           ? $updated_at  : $created_at;

        $arrStatus         = ['draft', 'opening', 'paused', 'closed', 'deleted'];
        $this->status      = $arrStatus[$status];
        $this->title       = $title;
        $this->description = $description;
        $this->vote_type   = $vote_type;
        $this->created_by  = $created_by;
        $this->updated_by  = $updated_by;
        $this->choice      = $choice;
        $this->anonymous   = !!$anonymous;
        $this->created_at  = $created_at . ' +0000';
        $this->updated_at  = $updated_at . ' +0000';
    }

}
