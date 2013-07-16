<?php

require_once dirname(__FILE__) . '/ActionController.php';


class FrontController {

    protected function getParams($params = []) {
        foreach ($_GET as $gI => $gItem) {
            $params[$gI] = $gItem;
        }
        foreach ($_SERVER as $sI => $sItem) {
            if (preg_match('/^HTTP_.*$/', $sI)) {
                $sI = strtolower(preg_replace('/^HTTP_(.*)$/', '$1', $sI));
                // @todo debug for @googollee by @leask{
                // error_log("HEADER: {$sI} = {$sItem}");
                // }
                $params[$sI] = $sItem;
            }
        }
        return $params;
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
            if ($arrPath) {
                $action = array_shift($arrPath);
            }

        //objects/action
        } else {
            $action = array_shift($arrPath);

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
        $route   = isset($_GET['_route']) ? $_GET['_route'] : $_SERVER['REQUEST_URI'];
        $arrPath = explode(
            '/', strtolower(current(explode('?', $route)))
        );
        array_shift($arrPath);
        $first = array_shift($arrPath);
        $last  = sizeof($arrPath) - 1;
        if ($arrPath[$last] === '') {
            unset($arrPath[$last]);
        }
        if (!$first) {
            $this->rockWeb('home', $arrPath, $route);
        } else if (preg_match('/^v\d+$/', $first)) {
            $controller = array_shift($arrPath);
            $this->rockApi($controller, $arrPath, $first, $route);
        } else if ($arrPath[0] === '500') {
            header('location: /500');
            return;
        } else {
            $this->rockWeb($first, $arrPath);
        }
    }

}
