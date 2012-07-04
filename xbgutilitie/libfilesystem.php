<?php

class libFileSystem {

    public function checkPath($path) {
        return $path === '/' || $path === '~';
    }


    public function delFolder($path) {
        if ($this->checkPath($path)) {
            return false;
        }
        return $this->emptyFolder($path) && rmdir($path);
    }


    public function emptyFolder($path) {
        if ($this->checkPath($path)) {
            return false;
        }
        $ph = opendir($path);
        while (($file = readdir($ph))) {
            if ($file !== '.' && $file !== '..') {
                if (is_dir($fullpath = "{$path}/{$file}")) {
                    $this->delFolder($fullpath);
                } else {
                    unlink($fullpath);
                }
            }
        }
        closedir($ph);
        return true;
    }

}
