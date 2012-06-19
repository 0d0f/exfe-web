<?php

class RelationHelper extends ActionController {

	protected $modRelation = null;


	public function __construct() {
		$this->modRelation = $this->getModelByName('Relation', 'v2');
	}


    public function saveRelations($userid, $r_identityid) {
    	$this->modRelation->saveRelations($userid, $r_identityid);
    }

}
