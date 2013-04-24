<?php

require_once dirname(__FILE__) . '/config.php';

function reverse_escape($str) {
    $search  = [ "\\\\" , "\\0" , "\\n" , "\\r" , "\Z"   , "\'" , '\"' ];
    $replace = [ "\\"   , "\0"  , "\n"  , "\r"  , "\x1a" , "'"  , '"'  ];
    return str_replace($search, $replace, $str);
}

function stripslashes_deep($value) {
    return is_array($value)
         ? array_map('reverse_escape', $value)
         : reverse_escape($value);
}

function getMainDB() {
    global $maindb;
    if (!$maindb) {
        $maindb = mysql_connect(DBHOST, DBUSER, DBPASSWD);
        mysql_select_db(DBNAME, $maindb);
     // mysql_query("SET NAMES 'utf8mb4'"); @todo by @leaskh for emoji!!!
        mysql_query("SET NAMES 'utf8'");
    }
}
getMainDB();


abstract class DataModel {

    public function getHelperByName($name) {
        $class = ucfirst($name) . 'Helper';
        $helperfile = HELPER_DIR . '/' . $class . '.php';
        include_once $helperfile;
        return new $class;
    }

    public function mysql_fetch_all($result) {
        $return = [];
        while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
            $return[] = stripslashes_deep($row);
        }
        return $return;
    }

    // check insert or delete is Error
    public function query($sql) {
        mysql_query($sql);
        if (($error = mysql_error())) {
            error_log("sql error: {$error}\nsql: {$sql}");
            return false;
        } else {
            $insert_id = mysql_insert_id();
            if ($insert_id > 0) {
                return ['insert_id' => strval($insert_id)];
            }
            return mysql_affected_rows();
        }
    }

    public function getAll($sql) {
        if($query = mysql_query($sql)) {
            $return = $this->mysql_fetch_all($query);
            if(count($return)) {
                return $return;
            }
            //Return NULL Bug Fix
            return $return;
        }
    }

    public function getRow($sql) {
        if($query = mysql_query($sql)) {
            if($return = mysql_fetch_array($query, MYSQL_ASSOC)) {
                return $return;
            }
        }
    }

    public function countNum($sql) {
        $row = $this->getRow($sql);
        if(!empty($row)) {
            foreach($row as $key => $value) {
                return $value;
            }
        } else {
            return 0;
        }
    }

    public function getColumn($sql) {
        $result = array();
        if($query = mysql_query($sql)) {
            if($data = $this->mysql_fetch_all($query)) {
                foreach($data as $row) {
                    foreach($row as $name=>$value) {
                        $result[] = $value;
                    }
                }
            }
        }
        return $result;
    }

}
