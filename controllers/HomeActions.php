<?php
class HomeActions extends ActionController {

    public function doIndex() {
        if(intval($_SESSION["userid"])>0){
            header("location:/s/profile");
        }
        $this->displayView();
    }
}

