<?php

class ErrorActions extends ActionController {

    public function do404() {
        $this->displayView();
    }

    public function do500() {
        $this->displayView();
    }
}
