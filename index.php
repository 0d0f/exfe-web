<?php

define('PAGE_DIR',           dirname(__FILE__) . '/pages');
define('HELPER_DIR',         dirname(__FILE__) . '/helpers');
define('MODEL_DIR',          dirname(__FILE__) . '/models');
define('CONTROLLER_DIR',     dirname(__FILE__) . '/controllers');
define('API_CONTROLLER_DIR', dirname(__FILE__) . '/api_controllers');
define('VIEW_DIR',           dirname(__FILE__) . '/views');

require_once 'common.php';
require_once 'FrontController.php';

session_start();
frontController::createInstance()->dispatch();


// web

// "^/v2/([a-zA-Z]+)/(.*)$" => "apiindex.php?v=v2&class=$1&path=$2",
// "^/v1/([a-zA-Z]+)/?([^?/]+)?(\??(.*))$" => "index.php?v=v1&class=$1&action=$2&$4",
// "^/v1/([a-zA-Z]+)/([0-9]+)/?([^?/]+)?(\??(.*))$" => "index.php?v=v1&class=$1&action=$3&id=$2&$4",
// "^/!([a-zA-Z0-9]+)\??(.*)$" => "/index.php?class=x&action=index&id=$1&$2",
// "^/([0-9]+)/([a-zA-Z]+)/?([^?/]+)\??(.*)$" => "index.php?class=$2&action=$3&id=$1&$4",
// "^/([a-zA-Z]+)/?([^?/]+)?(\??(.*))$" => "index.php?class=$1&action=$2&$4",


// api

// "^/v1/([a-zA-Z]+)/(.*)$" => "apiindex.php?v=v1&class=$1&path=$2",
// "^/v2/([a-zA-Z]+)/([^?/]*)/([0-9]*)$" => "apiindex.php?v=v2&class=$1&path=$2&id=$3",
// "^/v2/([a-zA-Z]+)/([^?/]*)/([0-9]*)\?(.*)$" => "apiindex.php?v=v2&class=$1&path=$2&id=$3&$4",
// "^/v2/([a-zA-Z]+)/(.*)$" => "apiindex.php?v=v2&class=$1&path=$2",
