<?php
// start profiling by @leaskh
// xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
xhprof_enable();







error_reporting(E_ALL ^ E_NOTICE);
session_start();
#header('Content-Type: application/json; charset=UTF-8');

define("PAGE_DIR", dirname(__FILE__) . "/pages");
define("HELPER_DIR", dirname(__FILE__) . "/helpers");
define("MODEL_DIR", dirname(__FILE__) . "/models");
define("APICONTROLLER_DIR", dirname(__FILE__) . "/controllers/api");
define("VIEW_DIR", dirname(__FILE__) . "/views");
define("URL_PREFIX", "/exfe");

require_once "common.php";
require_once "APIFrontController.php";

define ("REQUEST_METHOD",$_SERVER['REQUEST_METHOD']);

define("INVITATION_MAYBE", 3);
define("INVITATION_YES", 1);
define("INVITATION_NO", 2);

//echo "apiindex";
//print "class:".$_GET["class"];
//print "<br/>";
//print $_GET["path"];
//print "<br/>";
//print_r($_GET);
//print "<br/>";
//$path=$_GET["path"];
//$paths=explode("?",$path);
//print_r($paths);

//$pathinfo=$_SERVER["REQUEST_URI"];
//$pathinfos=explode("?",$pathinfo);
//print "<br/>";
//$paths=explode("/",$pathinfos[0]);
//print_r($paths);
//print "<br/>";
//print "param:".$pathinfos[1];
//print "<br/>";
//print $_GET["class"];
//print "<br/>";
//print $_GET["path"];
// FrontController::createInstance()->dispatch();









// stop profiler by @leaskh
$xhprof_data = xhprof_disable();
// display raw xhprof data for the profiler run
//print_r($xhprof_data);
