<?php

class IdentityActions extends ActionController {

    public function doIndex() {

    }
    
    public function doMakeDefaultAvatar() {
        $objIdentity = $this->getModelByName('identity', 'v2');
        $objIdentity->makeDefaultAvatar('vir');
    }

}
