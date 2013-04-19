<?php

//require_once("Common.php");
//require_once("io.php");

abstract class ActionController {
    protected $name;
    protected $model;
    protected $action;
    protected $viewData = array();
    protected $modelArray=array();

    //public function genUUID($type="101") {
    //	return file_get_contents("http://api.qieke.local:8080/u?$type");
    //}

    //  public function getModelByName($modelName) {
    //
    //        if(array_key_exists($modelName, $this->modelArray)) {
    //                return $this->modelArray[$modelName];
    //        } else {
    //                $model = $this->_getModelByName($modelName);
    //                $this->modelArray[$modelName] = $model;
    //                return $model;
    //        }
    //}

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

    public function displayViewByNameAction($name,$action) {
        if (!is_file(VIEW_DIR . "/" . $name . "/" . $action . "View.php")) {
            exit("Page not found:".$name."::". $action . "View.php");
        }
        //Create variables for the template
        foreach ($this->viewData as $key => $value) {
            $key = $value;
        }
        include VIEW_DIR . "/" . $name . "/" . $action . "View.php";
        exit(0);
    }

    public function displayViewByAction($action) {
        $this->displayViewByNameAction($this->getName(),$action);
#  if (!is_file(VIEW_DIR . "/" . $action . "View.php")) {
#    exit("Page not found:" . $action . "View.php");
#  }
#  //Create variables for the template
#  foreach ($this->viewData as $key => $value) {
#    $key = $value;
#  }
#  include VIEW_DIR . "/" . $action . "View.php";
#  exit(0);
    }

    public function displayView()
    {
        $this->displayViewByAction($this->action);
    }

    public function getHelperByNamev1($name)
    {
        $class = ucfirst($name) . "Helper";
        $helperfile = HELPER_DIR. "/" . $class.  ".php";
        include_once $helperfile;
        return new $class;
    }
    public function getHelperByName($name,$version="")
    {
        if($version=="")
            $this->getHelperByNamev1($name);

        $class = ucfirst($name) . "Helper";
        $helperfile = HELPER_DIR. "/".$version. "/" . $class.  ".php";
        include_once $helperfile;
        return new $class;
    }


    public function getModelByNamev1($name)
    {
        $class = ucfirst($name) . "Models";
        $modelfile = MODEL_DIR . "/"  . $class.  ".php";
        include_once $modelfile;
        return new $class;
    }
    public function getModelByName($name,$version="")
    {
        if($version=="")
            $this->getModelByNamev1($name);

        $class = ucfirst($name) . "Models";
        $modelfile = MODEL_DIR . "/".$version. "/" . $class.  ".php";
        include_once $modelfile;
        return new $class;
    }


    public function getDataModel($action)
    {
        $class = ucfirst($this->getName()) . "Models";
        $modelfile = MODEL_DIR . "/" .  $class.  ".php";
        include_once $modelfile;
        $this->model=new $class;
        return $this->model;
    }

    public function dispatchAPIAction($action,$params)
    {
        $actionMethod = "do" . ucfirst($action);
        if (!method_exists($this, $actionMethod)) {
            header('HTTP/1.1 404 Not Found');
            exit("Action not found:".$actionMethod);
        }
        $this->action=$action;
        $this->params=$params;
        $this->$actionMethod();
    }
    public function dispatchAction($action) {
        $actionMethod = "do" . ucfirst($action);
        if (!method_exists($this, $actionMethod)) {
            header('HTTP/1.1 404 Not Found');
            exit("Action not found:".$actionMethod);
        }
        $this->action=$action;
#$this->getDataModel($action);
        $this->$actionMethod();
#$this->displayView($action);
    }

    public function getPhotoArray($arrays) {
        return array("count"=>count($arrays), 'items'=>empty($arrays)?array():$arrays);
    }
}
