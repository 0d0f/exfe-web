<?php
require_once dirname(__FILE__)."/ActionController.php";
require_once dirname(__FILE__)."/DataModel.php";

class FrontController {
    
    public function __construct() {
        header('Origin: http://www.nczonline.net');
        header('Access-Control-Request-Method: POST');
    }

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


        //print_r($actions);
        //if($classname=='x')
        //{
        //    if(preg_match("/![a-zA-Z0-9]+/",$action)==1)
        //    {
        //        $params["id"]=$action;
        //        $action="index";
        //    }
        //}
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
