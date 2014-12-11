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
        $modTime    = $this->getModelByName('Time');
        $cross_time = $modTime->parseTimeString($strTime, $strZone);
        switch ($cross_time) {
            case 'timezone_error':
                apiError(400, 'timezone_error', '');
        }
        apiResponse(['cross_time' => $cross_time]);
    }

}
