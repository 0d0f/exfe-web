<?php

class ErrorActions extends ActionController {
    /*
     * 404 error...
     */
    public function do404(){
        $errorKey = exGet("e");
        $errorArray = array(
            "PageNotFound"      =>"Page not found",
            "theMissingCross"   =>"the missing cross"
        );
        if(array_key_exists($errorKey, $errorArray)) {
            $this->setVar("error", $errorArray[$errorKey]);
        }
        include VIEW_DIR."/error/404.php";
    }
}
