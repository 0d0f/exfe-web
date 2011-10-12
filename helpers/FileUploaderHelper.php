<?php
class FileUploaderHelper extends ActionController {
    private $allowedExtensions = array();
    private $sizeLimit = 10485760;
    private $file;

    function __construct(array $allowedExtensions = array(), $sizeLimit = 10485760){
        $allowedExtensions = array_map("strtolower", $allowedExtensions);

        $this->allowedExtensions = $allowedExtensions;
        $this->sizeLimit = $sizeLimit;
        
        $this->checkServerSettings();

        if (isset($_GET['exfile'])) {
            $this->file = new exUploadedFileAjax();
        } elseif (isset($_FILES['exfile'])) {
            $this->file = new exUploadedFileForm();
        } else {
            $this->file = false; 
        }
    }

    public function initialize($allowedExtensions = array(), $sizeLimit = 10485760){
        $this->__construct($allowedExtensions, $sizeLimit);
    }
    
    private function checkServerSettings(){        
        $postSize = $this->toBytes(ini_get('post_max_size'));
        $uploadSize = $this->toBytes(ini_get('upload_max_filesize'));        
        
        if ($postSize < $this->sizeLimit || $uploadSize < $this->sizeLimit){
            $size = max(1, $this->sizeLimit / 1024 / 1024) . 'M';             
            die("{'error':'increase pho.ini post_max_size and upload_max_filesize to $size'}");    
        }        
    }
    
    private function toBytes($str){
        $val = trim($str);
        $last = strtolower($str[strlen($str)-1]);
        switch($last) {
            case 'g': $val *= 1024;
            case 'm': $val *= 1024;
            case 'k': $val *= 1024;        
        }
        return $val;
    }
    
    /**
     * Returns $return_data = array( "filename"=>"", "error"=>0, "msg"=>"");
     */
    function handleUpload($uploadDirectory, $replaceOldFile = FALSE){
        $timestamp = getMicrotime();
        $return_data = array(
            "filename"  =>"",
            "file_ext"  =>"",
            "file_path"  =>"",
            "error"     =>0,
            "msg"       =>""
        );


        if (!is_writable($uploadDirectory)){
            $return_data['error'] = 1;
            $return_data['msg'] = "Server error. Upload directory isn't writable.";
            return $return_data;
        }
        
        if (!$this->file){
            $return_data['error'] = 1;
            $return_data['msg'] = "No files were uploaded.";
            return $return_data;
        }
        
        $size = $this->file->getSize();
        if ($size == 0) {
            $return_data['error'] = 1;
            $return_data['msg'] = "File is empty.";
            return $return_data;
        }
        
        if ($size > $this->sizeLimit) {
            $return_data['error'] = 1;
            $return_data['msg'] = "File is too large.";
            return $return_data;
        }
        
        $pathinfo = pathinfo($this->file->getName());
        $filename = $pathinfo['filename'];
        $filename = md5(randStr(20).$filename.getMicrotime().uniqid());
        //$filename = md5(uniqid());
        $ext = $pathinfo['extension'];

        if($this->allowedExtensions && !in_array(strtolower($ext), $this->allowedExtensions)){
            $ext_str = implode(', ', $this->allowedExtensions);
            $return_data['error'] = 1;
            $return_data['msg'] = "File has an invalid extension, it should be one of ". $ext_str . ".";
            return $return_data;
        }
        
        if(!$replaceOldFile){
            /// don't overwrite previous files that were uploaded
            while (file_exists($uploadDirectory . $filename . '.' . $ext)) {
                $filename = md5(randStr(20).$filename.getMicrotime().uniqid());
            }
        }
        
        if ($this->file->save($uploadDirectory . $filename.'.'.$ext)){
            $return_data['filename'] = $filename.'.'.$ext;
            $return_data['file_ext'] = $ext;
            $return_data['file_path'] = $uploadDirectory;
            $return_data['msg'] = "Upload File success.";
        } else {
            $return_data['error'] = 1;
            $return_data['msg'] = "Could not save uploaded file.The upload was cancelled, or server error encountered";
        }
        return $return_data;
    }    
}

/**
 * Handle file uploads via Ajax(XMLHttpRequest)
 */
class exUploadedFileAjax {
    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */
    function save($path) {    
        $input = fopen("php://input", "r");
        $temp = tmpfile();
        $realSize = stream_copy_to_stream($input, $temp);
        fclose($input);
        
        if ($realSize != $this->getSize()){            
            return false;
        }
        
        $target = fopen($path, "w");        
        fseek($temp, 0, SEEK_SET);
        stream_copy_to_stream($temp, $target);
        fclose($target);
        
        return true;
    }
    function getName() {
        return $_GET['exfile'];
    }
    function getSize() {
        if (isset($_SERVER["CONTENT_LENGTH"])){
            return (int)$_SERVER["CONTENT_LENGTH"];            
        } else {
            throw new Exception('Getting content length is not supported.');
        }      
    }   
}

/**
 * Handle file uploads via regular form post (uses the $_FILES array)
 */
class exUploadedFileForm {  
    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */
    function save($path) {
        if(!move_uploaded_file($_FILES['exfile']['tmp_name'], $path)){
            return false;
        }
        return true;
    }
    function getName() {
        return $_FILES['exfile']['name'];
    }
    function getSize() {
        return $_FILES['exfile']['size'];
    }
}
