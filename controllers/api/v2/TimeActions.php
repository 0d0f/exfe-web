<?php

class TimeActions extends ActionController {

    public function doIndex() {

    }


    public function doRecognize() {
        if (!($strTime = @trim($_POST['time_string']))) {
            apiError(400, 'no_time_string', '');
        }
        if (!($strZone = @trim($_POST['timezone']))) {
            apiError(400, 'no_timezone', '');
        }
        $modTime = $this->getModelByName('Time');
        apiResponse([
            'cross_time' => $modTime->parseTimeString($strTime, $strZone),
        ]);
    }

}
