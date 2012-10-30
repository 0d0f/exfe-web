<?php

class TimeActions extends ActionController {

    public function doIndex() {

    }


    public function doRecognize() {
        if (!($strTime = @trim($_POST['time_string']))) {
            apiError(400, 'no_time_string', '');
        }
        $modTime = $this->getModelByName('Time');
        $modTime->parseTimeString($strTime);

    }

}
