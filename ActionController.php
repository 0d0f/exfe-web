<?php

abstract class ActionController {

    protected $name;

    protected $action;

    protected $params     = [];

    protected $timezone   = '';

    protected $local      = '';

    protected $viewData   = [];

    protected $httpStatus = [
        206 => 'Partial Content',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        500 => 'Internal Server Error',
    ];


    public function setName($name) {
        $this->name = $name;
    }


    public function getName() {
        return $this->name;
    }


    public function setVar($key, $value) {
        $this->viewData[$key] = $value;
    }


    public function getVar($key) {
        if (array_key_exists($key, $this->viewData)) {
            return $this->viewData[$key];
        }
    }


    public function displayViewByNameAction($name, $action) {
        if (!is_file(VIEW_DIR . '/' . $name . '/' . $action . 'View.php')) {
            header('HTTP/1.1 404 Not Found');
            return;
        }
        foreach ($this->viewData as $key => $value) {
            $key = $value;
        }
        include VIEW_DIR . '/' . $name . '/' . $action . 'View.php';
    }


    public function displayViewByAction($action) {
        $this->displayViewByNameAction($this->getName(),$action);
    }


    public function displayView() {
        $this->displayViewByAction($this->action);
    }


    public function getHelperByName($name) {
        $class = ucfirst($name) . 'Helper';
        $helperfile = HELPER_DIR . '/' . $class . '.php';
        include_once $helperfile;
        return new $class;
    }


    public function getModelByName($name) {
        $class = ucfirst($name) . 'Models';
        $modelfile = MODEL_DIR . '/' . $class . '.php';
        include_once $modelfile;
        return new $class;
    }


    public function dispatchAction($action, $params = []) {
        $actionMethod = 'do' . ucfirst($action);
        if (!method_exists($this, $actionMethod)) {
            header('HTTP/1.1 404 Not Found');
            return;
        }
        $this->action = $action;
        $this->params = $params;
        foreach ($params as $pI => $pItem) {
            switch ($pI) {
                case 'accept_language':
                    if (($pItem = explode(',', $pItem))
                     && ($pItem = $pItem[0])
                     && ($pItem = explode(';', $pItem))
                     && ($pItem = $pItem[0])) {
                        switch ($pItem) {
                            case 'zh-hant':
                                $pItem = 'zh-tw';
                                break;
                            case 'zh-hans':
                                $pItem = 'zh-cn';
                        }
                        $this->locale   = mysql_real_escape_string(strtolower(trim($pItem)));
                    }
                    break;
                case 'accept_timezone':
                    if (($pItem = explode(',', $pItem))
                     && ($pItem = $pItem[0])
                     && ($pItem = explode(';', $pItem))
                     && ($pItem = $pItem[0])) {
                        $this->timezone = mysql_real_escape_string($pItem);
                    }
            }
        }
        $this->$actionMethod();
    }


    public function jsonResponse($data, $code = 200, $warning = null) {
        header('Content-Type: application/json; charset=UTF-8');
        if ($code !== 200) {
            if (isset($this->httpStatus[$code])) {
                header("HTTP/1.1 {$code} {$this->httpStatus[$code]}");
            } else {
                return;
            }
        }
        $output = new stdClass;
        if ($data) {
            $output->data    = $data;
        }
        if ($warning) {
            $output->warning = $warning;
        }
        echo json_encode($output);
    }


    public function jsonError($code, $type = '', $message = '', $data = null) {
        header('Content-Type: application/json; charset=UTF-8');
        if (isset($this->httpStatus[$code])) {
            header("HTTP/1.1 {$code} {$this->httpStatus[$code]}");
        } else {
            return;
        }
        if ($code === 500) {
            $type = $type ?: 'server_error';
        }
        if (!$code || !$type) {
            return;
        }
        $output = new stdClass;
        $output->error = [
            'code'    => $code,
            'type'    => $type,
            'message' => $message,
        ];
        if ($data) {
            $output->data = $data;
        }
        echo json_encode($output);
    }

}
