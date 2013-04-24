<?php

abstract class ActionController {

    protected $name;

    protected $action;

    protected $viewData   = [];


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

    public function dispatchAction($action) {
        $actionMethod = 'do' . ucfirst($action);
        if (!method_exists($this, $actionMethod)) {
            header('HTTP/1.1 404 Not Found');
            exit('Action not found:' . $actionMethod);
        }
        $this->action=$action;
        $this->$actionMethod();
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

}
