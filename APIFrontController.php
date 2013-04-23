<?php
require_once dirname(__FILE__) . '/ActionController.php';
require_once dirname(__FILE__) . '/DataModel.php';

class FrontController {

    public function __construct() {
        header('Content-Type: application/json; charset=UTF-8');

        if ($_SERVER['HTTP_ORIGIN'] === SITE_URL) {
            header('Access-Control-Allow-Origin: ' . SITE_URL);
            header('Access-Control-Allow-Credentials: true');
            if ($_GET['ssid']) {
                session_id($_GET['ssid']);
            }
        }
    }

    public static function createInstance() {
        if (!defined('PAGE_DIR')) {
            exit('Critical error: Cannot proceed without PAGE_DIR.');
        }
        $instance = new self();
        return $instance;
    }

    public function dispatch() {
        $classname = !empty($_GET["class"]) ? $_GET['class'] : "home";
        $action = !empty($_GET["action"]) ? $_GET["action"] : "index";
        $version= !empty($_GET["v"]) ? $_GET["v"] : "v1";

        $class = ucfirst($classname) . "Actions";

        $file = APICONTROLLER_DIR. "/" . $version . "/" .$class . ".php";
        if (!is_file($file)) {
            exit("Page not found:".$file);
        }
        $path=$_GET["path"];
        $paths=explode("?",$path);
        $params=array();
        if(sizeof($paths)>=2)
        {
            $p=explode("=",$paths[1]);
            $params[$p[0]]=$p[1];
            $action=$paths[0];
        }
        else
            $action=$paths[0];

        foreach($_GET as $k=>$v)
        {
            if($k!="v" && $k!="class" && $k!=$path)
                $params[$k]=$v;
        }

        foreach($_SERVER as $name=>$value)
        {
            if (substr($name, 0, 5) == 'HTTP_')
           {
               $name = str_replace(' ', '-', strtolower(str_replace('_', ' ', substr($name, 5))));
               $params[$name]=$value;
           }
        }

        require_once $file;

        $controller = new $class();
        $controller->setName($classname);
        $actions=explode("/",$action);
        if(sizeof($actions)==2)
        {
            if(preg_match("/![a-zA-Z0-9]+|[0-9]+/",$actions[0])==1)
            {
                $params["id"]=$actions[0];
                $action=$actions[1];
            }
        }
        else if(sizeof($actions)==1)
        {
            if(preg_match("/![a-zA-Z0-9]+|[0-9]+/",$actions[0])==1)
            {
                $params["id"]=$actions[0];
                $action="index";
            }
        }

        $controller->dispatchAPIAction($action,$params);
    }

#private function logRequest() {

#	$handle=fopen("/tmp/qieke_request", "a");

#	if(isset($_GET)) {
#		fwrite($handle, "GET: ");
#		foreach($_GET as $key=>$value) {
#			fwrite($handle, "$key=>$value,");
#		}
#		fwrite($handle, "\n");
#	}

#	if(isset($_POST)) {
#		fwrite($handle, "POST: ");
#		foreach($_POST as $key=>$value) {
#			fwrite($handle, "$key=>$value,");
#		}
#		fwrite($handle, "\n");
#	}
#	fclose($handle);
#}

}
