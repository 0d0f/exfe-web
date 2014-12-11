<?php

class BackgroundsActions extends ActionController {

    public function doGetAvailableBackgrounds() {
        $modBackground = $this->getModelByName('Background');
        $backgrounds   = $modBackground->getAllBackground();
        if ($backgrounds) {
            apiResponse(['backgrounds' => $backgrounds]);
        }
        apiError(500, 'server_error', 'Can not get backgrounds.');
    }

}
