<?php
error_reporting(E_ALL ^ E_NOTICE);
session_start();
#header('Content-Type: application/json; charset=UTF-8');

define('PAGE_DIR',       dirname(__FILE__) . '/pages');
define('HELPER_DIR',     dirname(__FILE__) . '/helpers');
define('MODEL_DIR',      dirname(__FILE__) . '/models');
define('CONTROLLER_DIR', dirname(__FILE__) . '/controllers');
define('VIEW_DIR',       dirname(__FILE__) . '/views');

require_once 'common.php';
require_once 'FrontController.php';

frontController::createInstance()->dispatch();


        // "^/v2/([a-zA-Z]+)/(.*)$" => "apiindex.php?v=v2&class=$1&path=$2",
        // "^/v1/([a-zA-Z]+)/?([^?/]+)?(\??(.*))$" => "index.php?v=v1&class=$1&action=$2&$4",
        // "^/v1/([a-zA-Z]+)/([0-9]+)/?([^?/]+)?(\??(.*))$" => "index.php?v=v1&class=$1&action=$3&id=$2&$4",
        // "^/!([a-zA-Z0-9]+)/gather$" => "/index.php?class=x&action=gather&id=$1",
        // "^/!([a-zA-Z0-9]+)/crossEdit$" => "/index.php?class=x&action=crossEdit&id=$1",
        // "^/!([a-zA-Z0-9]+)\??(.*)$" => "/index.php?class=x&action=index&id=$1&$2",
        // "^/([0-9]+)/([a-zA-Z]+)/?([^?/]+)\??(.*)$" => "index.php?class=$2&action=$3&id=$1&$4",
        // "^/([a-zA-Z]+)/?([^?/]+)?(\??(.*))$" => "index.php?class=$1&action=$2&$4",
