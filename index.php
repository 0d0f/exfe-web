<?php

define('PAGE_DIR',           dirname(__FILE__) . '/pages');
define('HELPER_DIR',         dirname(__FILE__) . '/helpers');
define('MODEL_DIR',          dirname(__FILE__) . '/models');
define('CONTROLLER_DIR',     dirname(__FILE__) . '/controllers');
define('API_CONTROLLER_DIR', dirname(__FILE__) . '/api_controllers');
define('VIEW_DIR',           dirname(__FILE__) . '/views');

require_once 'common.php';
require_once 'FrontController.php';

// xhprof by @leask {
if (DEBUG && extension_loaded('xhprof')) {
    xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
    $xhprof_lib = "/usr/local/Cellar/php54-xhprof/270b75d/xhprof_lib";
    include_once "{$xhprof_lib}/utils/xhprof_lib.php";
    include_once "{$xhprof_lib}/utils/xhprof_runs.php";
}
// }

session_start();
frontController::createInstance()->dispatch();

// xhprof by @leask {
function xhprof_end () {
    if (DEBUG && extension_loaded('xhprof')) {
        // namespace for your application
        $profiler_namespace = ROOT_DOMAIN;
        $xhprof_data        = xhprof_disable();
        $xhprof_runs        = new XHProfRuns_Default('/tmp');
        $run_id = $xhprof_runs->save_run($xhprof_data, $profiler_namespace);
        // url to the XHProf UI libraries
        $route  = isset($_GET['_route']) ? $_GET['_route'] : $_SERVER['REQUEST_URI'];
        $profiler_url = sprintf(
            'http://xhprof.leask.0d0f.com/index.php?run=%s&source=%s',
            $run_id, $profiler_namespace
        );
        error_log('xhprof {');
        error_log("ACCESS: {$route}");
        error_log("PROFILER: {$profiler_url}");
        error_log('xhprof }');
    }
}
xhprof_end();
// }
