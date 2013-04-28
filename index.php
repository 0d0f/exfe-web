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
