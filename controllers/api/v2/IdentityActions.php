<?php

class IdentityActions extends ActionController {

    public function doIndex() {
        
    }
    
    
    public function doUpdate() {
        // get raw data
        $id                = isset($_POST['id'])                ? intval(htmlspecialchars($_POST['id']))                                  : null;
        $provider          = isset($_POST['provider'])          ? mysql_real_escape_string(htmlspecialchars($_POST['provider']))          : null;
        $external_id       = isset($_POST['external_id'])       ? mysql_real_escape_string(htmlspecialchars($_POST['external_id']))       : null;
        $name              = isset($_POST['name'])              ? mysql_real_escape_string(htmlspecialchars($_POST['name']))              : '';
        $nickname          = isset($_POST['nickname'])          ? mysql_real_escape_string(htmlspecialchars($_POST['nickname']))          : '';
        $bio               = isset($_POST['bio'])               ? mysql_real_escape_string(htmlspecialchars($_POST['bio']))               : '';
        $avatar_filename   = isset($_POST['avatar_filename'])   ? mysql_real_escape_string(htmlspecialchars($_POST['avatar_filename']))   : '';
        $external_username = isset($_POST['external_username']) ? mysql_real_escape_string(htmlspecialchars($_POST['external_username'])) : '';
        // check data
        if (!$id || !$provider || !$external_id) {
            header('HTTP/1.1 500 Internal Server Error');
            return;
        }
        // do update
        $objIdentity = $this->getModelByName('Identity', 'v2');
        $id = $objIdentity->updateIdentityById(
            $id,
            array('provider'          => $provider,       
                  'external_id'       => $external_id,    
                  'name'              => $name,           
                  'nickname'          => $nickname,       
                  'bio'               => $bio,         
                  'avatar_filename'   => $avatar_filename,
                  'external_username' => $external_username),
        );
        echo json_encode(array('identity_id' => $id));
    }

}
