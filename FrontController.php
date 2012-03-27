<?php
require_once dirname(__FILE__)."/ActionController.php";
require_once dirname(__FILE__)."/DataModel.php";

class FrontController {


    public static function createInstance() {
        if (!defined("PAGE_DIR")) {
            exit("Critical error: Cannot proceed without PAGE_DIR.");
        }
        $instance = new self();
        return $instance;
    }

    public function dispatch() {
        $classname = !empty($_GET["class"]) ? $_GET["class"] : "home";
        $action = !empty($_GET["action"]) ? $_GET["action"] : "index";

        $class = ucfirst($classname) . "Actions";

        $file = CONTROLLER_DIR. "/" . $class . ".php";
        if (!is_file($file)) {
            header("location:/error/404?e=PageNotFound");
            exit;
            //exit("Page not found:".$file);
        }

        require_once $file;

        $controller = new $class();
        $controller->setName($classname);
        $controller->dispatchAction($action);
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
