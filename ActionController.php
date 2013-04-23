<?php

//require_once("Common.php");
//require_once("io.php");

abstract class ActionController {
    protected $name;
    protected $model;
    protected $action;
    protected $viewData   = [];
    protected $modelArray = [];

    // public function __construct() {
    //     ini_set('session.cookie_domain', ROOT_DOMAIN);
    // }

    public function setName($name) {
        $this->name = $name;
    }

    public function getName() {
        return $this->name;
    }

    public function getModel() {
        return $this->model;
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
            exit('Page not found:' . $name . '::' . $action . 'View.php');
        }
        // Create variables for the template
        foreach ($this->viewData as $key => $value) {
            $key = $value;
        }
        include VIEW_DIR . '/' . $name . '/' . $action . 'View.php';
        exit(0);
    }

    public function displayViewByAction($action) {
        $this->displayViewByNameAction($this->getName(),$action);
    }

    public function displayView() {
        $this->displayViewByAction($this->action);
    }

    public function getHelperByNamev1($name) {
        $class = ucfirst($name) . 'Helper';
        $helperfile = HELPER_DIR. '/' . $class . '.php';
        include_once $helperfile;
        return new $class;
    }

    public function getHelperByName($name, $version = '') {
        if($version == '') {
            $this->getHelperByNamev1($name);
        }

        $class = ucfirst($name) . 'Helper';
        $helperfile = HELPER_DIR . '/' . $version. '/' . $class . '.php';
        include_once $helperfile;
        return new $class;
    }

    public function getModelByNamev1($name) {
        $class = ucfirst($name) . 'Models';
        $modelfile = MODEL_DIR . '/' . $class . '.php';
        include_once $modelfile;
        return new $class;
    }

    public function getModelByName($name, $version = '') {
        if($version == '') {
            $this->getModelByNamev1($name);
        }
        $class = ucfirst($name) . 'Models';
        $modelfile = MODEL_DIR . '/' . $version . '/' . $class . '.php';
        include_once $modelfile;
        return new $class;
    }

    public function getDataModel($action) {
        $class = ucfirst($this->getName()) . 'Models';
        $modelfile = MODEL_DIR . '/' .  $class . '.php';
        include_once $modelfile;
        $this->model = new $class;
        return $this->model;
    }

    public function dispatchAPIAction($action, $params) {
        $actionMethod = 'do' . ucfirst($action);
        if (!method_exists($this, $actionMethod)) {
            header('HTTP/1.1 404 Not Found');
            exit('Action not found:' . $actionMethod);
        }
        $this->action = $action;
        $this->params = $params;
        $this->$actionMethod();
    }

    public function dispatchAction($action) {
        $actionMethod = 'do' . ucfirst($action);
        if (!method_exists($this, $actionMethod)) {
            header('HTTP/1.1 404 Not Found');
            exit('Action not found:' . $actionMethod);
        }
        $this->action=$action;
        $this->$actionMethod();
    }

    public function getPhotoArray($arrays) {
        return ['count' => count($arrays), 'items' => empty($arrays) ? [] : $arrays];
    }

}
