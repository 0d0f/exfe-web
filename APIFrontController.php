<?php
require_once dirname(__FILE__)."/ActionController.php";
require_once dirname(__FILE__)."/DataModel.php";

class FrontController {

#	public static function checkOauthToken() {
#
#	if(isset($_GET["magic_token"])) {
#		
#		$_SESSION["userid"] = $_GET["magic_token"];
#
#		return;
#
#	} else {
#
#		$accesstoken = '';
#
#		if(isset($_GET['oauth_token']) ) {
#			$accesstoken=$_GET['oauth_token'];
#		}
#		else if(isset($_GET['access_token']) ) {
#			$accesstoken=$_GET['access_token'];
#		}
#
#		if ($accesstoken=="") {
#
#			$responobj["meta"]["code"]=400;
#			$responobj["meta"]["errType"]="Bad Request";
#			$responobj["meta"]["errorDetail"]="invalid_auth";
#			echo json_encode($responobj);
#			exit();
#
#		} else {
#	
#			$name="user";
#	    		$class = ucfirst($name) . "Models";
#	    		$modelfile = MODEL_DIR . "/" . $name. "/" . $class.  ".php";
#	    		include_once $modelfile;
#	    		$userData=new $class;
#			$useruuid=$userData->getUserIdByAccessToken($accesstoken);
#			if(intval($useruuid)>0) {
#				$_SESSION["userid"]=$useruuid;
#			} else {
#				$responobj["meta"]["code"]=401;
#				$responobj["meta"]["errType"]="invalid_auth";
#				$responobj["meta"]["errorDetail"]="invalid_auth";
#				echo json_encode($responobj);
#				exit();
#			 }
#		}
#	}
#	}

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

#if(!($class=="UserActions" && ($action=="loginweb" || $action=="auth" || $action == 'register'))) {
#	$this->checkOauthToken();
#}

        $file = APICONTROLLER_DIR. "/" . $class . ".php";
        if (!is_file($file)) {
            exit("Page not found:".$file);
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
