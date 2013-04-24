<?php

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

    public function query($sql) {
        mysql_query($sql);
        if (($error = mysql_error())) {
            error_log("SQL error: {$error}\nSQL: {$sql}");
            return null;
        }
        $result = [];
        $insert_id = mysql_insert_id();
        if ($insert_id > 0) {
            $result['insert_id'] = strval($insert_id);
        }
        $result['affected_rows'] = mysql_affected_rows();
        return $result;
    }

    public function getAll($sql) {
        return ($query = mysql_query($sql))
             ? $this->mysql_fetch_all($query)
             : null;
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
        if (!empty($row)) {
            foreach($row as $key => $value) {
                return $value;
            }
        }
        return 0;
    }

    public function getColumn($sql) {
        $result = [];
        if (($query = mysql_query($sql))) {
            if($data = $this->mysql_fetch_all($query)) {
                foreach ($data as $row) {
                    foreach ($row as $name => $value) {
                        $result[] = $value;
                    }
                }
            }
            return $result;
        }
        return null;
    }

}
