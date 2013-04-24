<?php
error_reporting(E_ALL ^ E_NOTICE);
session_start();
#header('Content-Type: application/json; charset=UTF-8');

define('PAGE_DIR',          dirname(__FILE__) . '/pages');
define('HELPER_DIR',        dirname(__FILE__) . '/helpers');
define('MODEL_DIR',         dirname(__FILE__) . '/models');
define('APICONTROLLER_DIR', dirname(__FILE__) . '/controllers/api');
define('VIEW_DIR',          dirname(__FILE__) . '/views');

require_once 'common.php';
require_once 'APIFrontController.php';

FrontController::createInstance()->dispatch();
