<?php

require_once dirname(__FILE__) . '/ActionController.php';


class FrontController {

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
            header("location:/error/404?e=PageNotFound");
            return;
            //exit("Page not found:".$file);
            // 404
        }
        require_once $ctlFile;
        $controller = new $controller();
        $controller->setName($controllerName);
        $action = 'index';
        $controller->dispatchAction($action);
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
        // 兼容老代码 { // by @leaskh
        foreach ($_GET as $gI => $gItem) {
            $params[$gI] = $gItem;
        }
        // }
        $controller->dispatchAction($action, $params);
        return 0;
    }


    public function dispatch() {
        $arrPath = explode(
            '/',
            strtolower(current(explode('?', $_SERVER['REQUEST_URI'])))
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
            echo '404';
        }
    }

}
