<?php

require_once dirname(__FILE__)."/config.php";

function stripslashes_deep($value) {
    return is_array($value) ? array_map('reverse_escape', $value) : reverse_escape($value);
}


abstract class DataModel {

    static $FAKE_ID = '0';


    public function getHelperByNamev1($name)
    {
        $class = ucfirst($name) . "Helper";
        $helperfile = HELPER_DIR. "/" . $class.  ".php";
        include_once $helperfile;
        return new $class;
    }

    public function getHelperByName($name,$version="")
    {
        if($version=="")
            $this->getHelperByNamev1($name);

        $class = ucfirst($name) . "Helper";
        $helperfile = HELPER_DIR. "/".$version. "/" . $class.  ".php";
        include_once $helperfile;
        return new $class;
    }

    private function endswith($str, $test) {
        return substr($str, -strlen($test)) == $test;
    }

    private function needToConvertToFloat($name) {
        return 	 $this->endswith(strtolower($name), 'lat')
            || $this->endswith(strtolower($name), 'lng');
    }

    private function needToConvertToInt($name) {
        return $this->endswith(strtolower($name), 'id')
            || $name == 'createdAt'
            || $name == 'width'
            || $name == 'height';

    }

    public function convertDataTypeForRow($row) {
        foreach($row as $key=>$value) {
            if($this->needToConvertToInt($key)) {
                $row[$key] = intval($value);
            } else if ($this->needToConvertToFloat($key)) {
                $row[$key] = floatval($value);
            } else {
                $row[$key] = $value;
            }
        }
        return $row;
    }

    public function convertDataTypeForAll($all) {
        $result = array();
        foreach($all as $row) {
            $result[] = $this->convertDataTypeForRow($row);
        }
        return $result;
    }

    public function mysql_fetch_all($result) {
        $return = array();
        while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
            $return[] = stripslashes_deep($row);
        }
        return $return;
    }


    //check insert or delete is Error
    public function query($sql) {
        mysql_query($sql);
        if (($error = mysql_error())) {
            error_log("sql error: {$error}\nsql: {$sql}");
            return false;
        } else {
            $insert_id=mysql_insert_id();
            if($insert_id>0)
                return array("insert_id" => strval($insert_id));
            return mysql_affected_rows();
        }
    }

    //get all
    public function getAll($sql) {
        if($query = mysql_query($sql)) {
            $return = $this->mysql_fetch_all($query);
            if(count($return)) {
                return $return;
                //return $this->convertDataTypeForAll($return);
            }
            //Return NULL Bug Fix
            return $return;
        }
    }

    //get one row
    public function getRow($sql) {
        if($query = mysql_query($sql)) {
            if($return = mysql_fetch_array($query,MYSQL_ASSOC)) {
                return $return;
            }
        }
    }

    public function countNum($sql) {
        $row = $this->getRow($sql);
        if(!empty($row)) {
            foreach($row as $key=>$value) {
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

    public function getData($sql){

    }
#public function getData($page,$action) {
#  $modelMethod = "get" . ucfirst($action);
#  print $modelMethod;
#  if (!method_exists($this, $modelMethod )) {
#    exit("mode not found");
#  }
#  $this->$modelMethod();
#  #$this->displayView($action);
#}
}

function getMainDB() {
    global $mysql_config, $maindb;
    global $dbhost, $dbuser, $dbpasswd,$dbname;
    if(!$maindb) {
        $maindb = mysql_connect($dbhost, $dbuser, $dbpasswd);
        mysql_select_db($dbname, $maindb);
        mysql_query("SET NAMES 'UTF8'");
    }
    return $maindb;
}
$maindb=getMainDB();
