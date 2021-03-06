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
        $maindb = new mysqli(DBHOST, DBUSER, DBPASSWD, DBNAME);
        $maindb->set_charset('utf8mb4');
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
        while ($row = $result->fetch_array(MYSQL_ASSOC)) {
            $return[] = stripslashes_deep($row);
        }
        return $return;
    }

    public function query($sql) {
        global $maindb;
        $maindb->query($sql);
        if ($maindb->error) {
            error_log("SQL error: {$maindb->error}\nSQL: {$sql}");
            return null;
        }
        $result = [];
        $insert_id = $maindb->insert_id;
        if ($insert_id > 0) {
            $result['insert_id'] = strval($insert_id);
        }
        $result['affected_rows'] = $maindb->affected_rows;
        return $result;
    }

    public function getAll($sql) {
        global $maindb;
        return ($query = $maindb->query($sql))
             ? $this->mysql_fetch_all($query)
             : null;
    }

    public function getRow($sql) {
        global $maindb;
        if (($query = $maindb->query($sql))) {
            if (($return = $query->fetch_array(MYSQL_ASSOC))) {
                return $return;
            }
        }
    }

    public function countNum($sql) {
        $row = $this->getRow($sql);
        if (!empty($row)) {
            foreach ($row as $key => $value) {
                return $value;
            }
        }
        return 0;
    }

    public function getColumn($sql) {
        global $maindb;
        $result = [];
        if (($query = $maindb->query($sql))) {
            if (($data = $this->mysql_fetch_all($query))) {
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
