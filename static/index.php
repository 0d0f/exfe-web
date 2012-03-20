<?php
define('MINIFY_MIN_DIR', dirname(__FILE__));

// load config
require MINIFY_MIN_DIR . '/config.php';

// setup include path
set_include_path($min_libPath . PATH_SEPARATOR . get_include_path());

require 'Minify.php';

Minify::$uploaderHoursBehind = $min_uploaderHoursBehind;
Minify::setCache(
    isset($min_cachePath) ? $min_cachePath : ''
    ,$min_cacheFileLocking
);

if ($min_documentRoot) {
    $_SERVER['DOCUMENT_ROOT'] = $min_documentRoot;
    Minify::$isDocRootSet = true;
}

$min_serveOptions['minifierOptions']['text/css']['symlinks'] = $min_symlinks;
// auto-add targets to allowDirs
foreach ($min_symlinks as $uri => $target) {
    $min_serveOptions['minApp']['allowDirs'][] = $target;
}

if ($min_allowDebugFlag) {
    require_once 'Minify/DebugDetector.php';
    $min_serveOptions['debug'] = Minify_DebugDetector::shouldDebugRequest($_COOKIE, $_GET, $_SERVER['REQUEST_URI']);
}

if ($min_errorLogger) {
    require_once 'Minify/Logger.php';
    if (true === $min_errorLogger) {
        require_once 'FirePHP.php';
        $min_errorLogger = FirePHP::getInstance(true);
    }
    Minify_Logger::setLogger($min_errorLogger);
}

// check for URI versioning
if (preg_match('/&\\d/', $_SERVER['QUERY_STRING'])) {
    $min_serveOptions['maxAge'] = 31536000;
}

if (isset($_GET['g'])) {
    // well need groups config
    $min_serveOptions['minApp']['groups'] = (require MINIFY_MIN_DIR . '/groupsConfig.php');
}

//如果为测试环境，则输出JS和CSS原文件。
if($dev_environment){
    if (isset($_GET['f'])) {
        $file = $_GET["f"];
        $file_type_arr = array("js","css");
        $ext = strtolower(substr($file,strrpos($file,'.')+1));
        if(!in_array($ext, $file_type_arr)){
            echo "<h1>Error file-type...</h1>";
            exit;
        }
        $file_contents = file_get_contents($file);

        if($ext == "css"){
            $file_contents = str_replace("../images/", "/static/images/", $file_contents);
            header("Content-Type:text/css; charset=UTF-8");
        }
        if($ext == "js"){
            header("Content-Type:application/x-javascript; charset=UTF-8");
        }
        $file = STATIC_FILE_ROOT."/".$file;
        echo $file_contents;
        exit;
    }
    if (isset($_GET['g'])) {
        $group_name = $_GET['g'];
        $group_type = substr($group_name, 0, strrpos($group_name, '_'));
        if(!array_key_exists($group_name, $min_serveOptions['minApp']['groups'])){
            echo "<h1>Group error...</h1>";
            exit;
        }
        $file_array = $min_serveOptions['minApp']['groups'][$group_name];
        $file_contents = "";
        foreach($file_array as $f){
            $file_name = STATIC_FILE_ROOT."/".$f;
            $file_contents .= file_get_contents($file_name);
        }
        if($group_type == "css"){
            $file_contents = str_replace("../images/", "/static/images/", $file_contents);
            header("Content-Type:text/css; charset=UTF-8");
        }
        if($group_type == "js"){
            header("Content-Type:application/x-javascript; charset=UTF-8");
        }
        echo $file_contents;
        exit;
    }
    exit;
}

if (isset($_GET['f']) || isset($_GET['g'])) {
    // serve!
    if (! isset($min_serveController)) {
        require 'Minify/Controller/MinApp.php';
        $min_serveController = new Minify_Controller_MinApp();
    }
    Minify::serve($min_serveController, $min_serveOptions);
}
