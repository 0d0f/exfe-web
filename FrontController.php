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
                $params[$sI] = $sItem;
            }
        }
        return $params;
    }


    public static function createInstance() {
        if (!defined('PAGE_DIR')) {
            exit("Critical error: Cannot proceed without PAGE_DIR.");
        }
        $instance = new self();
        return $instance;
    }


    public function rockWeb($controllerName, $arrPath = []) {
        $controller = "{$controllerName}Actions";
        $ctlFile    = CONTROLLER_DIR . '/' . $controller . '.php';
        if (!is_file($ctlFile)) {
            header("location: /error/404?e=PageNotFound");
            return;
        }
        require_once $ctlFile;
        $controller = new $controller();
        $controller->setName($controllerName);
        $action = 'index';
        if ($arrPath) {
            $action = $arrPath[0];
        }
        $params = $this->getParams($params);
        $controller->dispatchAction($action, $params);
    }


    public function rockApi($controllerName, $arrPath = [], $version = 'v2') {
        header('Content-Type: application/json; charset=UTF-8');
        header('Access-Control-Allow-Origin: ' . SITE_URL);
        header('Access-Control-Allow-Credentials: true');
        if ($_GET['ssid']) {
            session_id($_GET['ssid']);
        }
        $controller = "{$controllerName}Actions";
        $ctlFile    = API_CONTROLLER_DIR . '/' . $version . '/' . $controller . '.php';
        if (!is_file($ctlFile)) {
            // 404
            echo 'Page not found:' . $ctlFile;
            return;
        }
        require_once $ctlFile;
        $controller = new $controller();
        $controller->setName($controllerName);
        $params = [];
        $action = 'index';
        if (sizeof($arrPath) === 1) {
            if (preg_match('/^\d+$/', $arrPath[0])) {
                $action       = 'index';
                $params['id'] = $arrPath[0];
            } else {
                $action       = $arrPath[0];
            }
        } else {
            if ($controllerName === 'gobus') {
                $action       = $arrPath[0];
                $params['id'] = $arrPath[1];
            } else {
                $action       = $arrPath[1];
                $params['id'] = $arrPath[0];
            }
        }
        $params = $this->getParams($params);
        $controller->dispatchAction($action, $params);
    }


    public function dispatch() {
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
            $this->rockWeb('home', $arrPath);
        } else if (preg_match('/^v\d+$/', $first)) {
            $controller = array_shift($arrPath);
            $this->rockApi($controller, $arrPath, $first);
        } else if ($arrPath[0] === '500') {
            echo '500';
        } else {
            $this->rockWeb($first, $arrPath);
        }
    }

}
