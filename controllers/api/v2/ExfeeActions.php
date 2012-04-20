<?php

class ExfeeActions extends ActionController {

    public function doIndex() {

    }
    
    
    public function doEdit() {
        $params   = $this->params;
        $modExfee = $this->getModelByName('exfee', 'v2');
        if (!$_POST['by_identity_id']) {
            echo json_encode(array('error' => 500));
            return;
        }
        if (($exfee_id = intval($params['id']))) {
            $exfee = json_decode($_POST['exfee']);
            if ($exfee && isset($exfee->invitations) && is_array($exfee->invitations)) {
                $modExfee->updateExfeeById($exfee_id, $exfee->invitations, $_POST['by_identity_id']);
                echo json_encode(array('exfee_id' => $exfee_id));
                return;
            }
        }
        echo json_encode(array('error' => 500));
    }
    public function doUpdate(){
        $params   = $this->params;
        $exfee_id=$params["id"];
        $updated_at=$params["updated_at"];

        $exfeeHelper = $this->getHelperByName('exfee', 'v2');
        $update=$exfeeHelper->getUpdate($exfee_id,$updated_at);
        print_r($update);

    }

}
