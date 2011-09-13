<?php
class UsersActions extends ActionController {

    public function doIndex()
    {

    }
    public function doX()
    {
        print "user/x";
        print_r($this->params);
    }
}
