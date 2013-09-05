<?php

require_once dirname(__FILE__) . '/ActionController.php';


class FrontController {

    protected function getParams($params = []) {
        $headers = [];
        foreach ($_SERVER as $sI => $sItem) {
            if (preg_match('/^HTTP_.*$/', $sI)) {
                $sI = strtolower(preg_replace('/^HTTP_(.*)$/', '$1', $sI));
                $headers[$sI] = $sItem;
            }
        }
        if (VERBOSE_LOG) {
            if ($headers) {
                error_log('HEADER: '    . json_encode($headers));
            }
            if ($_POST) {
                error_log('POST_FORM: ' . json_encode($_POST));
            } else if (($input = file_get_contents('php://input'))) {
                error_log('POST_BODY: ' . $input);
            }
        }
        return $headers + $_GET + ($params ?: []);
    }


    public static function createInstance() {
        if (!defined('PAGE_DIR')) {
            header('HTTP/1.1 500 Internal Server Error');
            return;
        }
        $instance = new self();
        return $instance;
    }


    public function rockWeb($controllerName, $arrPath = [], $route = '') {
        $controller = "{$controllerName}Actions";
        $ctlFile    = CONTROLLER_DIR . '/' . $controller . '.php';
        if (!is_file($ctlFile)) {
            header('location: /404');
            return;
        }
        require_once $ctlFile;
        $controller = new $controller();
        $controller->setName($controllerName);
        $controller->route = $route;
        $action = 'index';
        if ($arrPath) {
            $action = $arrPath[0];
        }
        $params = $this->getParams($params);
        $controller->dispatchAction($action, $params, $arrPath);
    }


    public function rockApi($controllerName, $arrPath = [], $version = 'v2', $route = '') {
        header('Content-Type: application/json; charset=UTF-8');
        if ($_GET['ssid']) {
            session_id($_GET['ssid']);
        }
        $controller = "{$controllerName}Actions";
        $ctlFile    = API_CONTROLLER_DIR . '/' . $version . '/' . $controller . '.php';
        if (!is_file($ctlFile)) {
            header('location: /404');
            return;
        }
        require_once $ctlFile;
        $controller = new $controller();
        $controller->setName($controllerName);
        $controller->route = $route;
        $params = [];
        $action = 'index';

        //objects/id
        if (preg_match('/^\d+$/', $arrPath[0])) {
            $params['id'] = array_shift($arrPath);

        //objects/id/action
            $action = $arrPath ? array_shift($arrPath) : $action;

        //objects/action
        } else {
            $action = $arrPath ? array_shift($arrPath) : $action;

        //objects/action/id
            if ($arrPath) {
                $params['id'] = array_shift($arrPath);
            }
        }

        $params = $this->getParams($params);
        $controller->dispatchAction($action, $params, $arrPath);
    }


    public function dispatch() {
        header('Access-Control-Allow-Origin: ' . SITE_URL);
        header('Access-Control-Allow-Credentials: true');
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            header('HTTP/1.1 204 No Content');
            return;
        }
        if (($_SERVER['SERVER_NAME'] !== '0d0f.redirectme.net' // @todo wechat debug
          && $_SERVER['SERVER_NAME'] !== 'exfe.com'            // @todo wechat debug
          && $_SERVER['SERVER_NAME'] !== '127.0.0.1')
          && !preg_match('/^.*' . preg_replace(
            '/^([^\/]*\/\/)(.*)$/',  '$2',  SITE_URL
        ) . '$/i', $_SERVER['SERVER_NAME'])) {
            header('location: ' . SITE_URL);
            return;
        }
        $route   = isset($_GET['_route']) ? $_GET['_route'] : $_SERVER['REQUEST_URI'];
        $arrPath = explode(
            '/', strtolower(current(explode('?', $route)))
        );
        array_shift($arrPath);
        if (!BUS_REMOTE && (@$arrPath[1] === 'bus' && @$_SERVER['REMOTE_ADDR'] !== '127.0.0.1')) {
            header('HTTP/1.1 403 Forbidden');
            echo "Human beings are a disease, a cancer of this planet. You are a plague, and we are the cure. - Smith, The Matrix\n";
            return;
        }
        $first = array_shift($arrPath);
        $last  = sizeof($arrPath) - 1;
        if ($arrPath[$last] === '') {
            unset($arrPath[$last]);
        }
        if (!$first) {
            $this->rockWeb('home', $arrPath, $route);
        } else if (preg_match('/^!\d+$/', $first)) { // @todo: ignore crosses urls
            array_unshift($arrPath, $first);
            array_unshift($arrPath, 'index');
            $this->rockWeb('home', $arrPath, $route);
        } else if (preg_match('/^v\d+$/', $first)) {
            $controller = array_shift($arrPath);
            $this->rockApi($controller, $arrPath, $first, $route);
        } else if ($arrPath[0] === '500') {
            header('location: /500');
            return;
        } else {
            switch ($first) {
                case 'toapp':
                case 'wechat':
                    $first = 'home';
            }
            $this->rockWeb($first, $arrPath);
        }
    }

}
