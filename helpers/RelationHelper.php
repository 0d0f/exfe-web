<?php

class RelationHelper extends ActionController {

	protected $modRelation = null;


	public function __construct() {
		$this->modRelation = $this->getModelByName('Relation');
	}


    public function saveRelations($userid, $r_identityid) {
    	$this->modRelation->saveRelations($userid, $r_identityid);
    }

}
