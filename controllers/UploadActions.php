<?php
require_once dirname(dirname(__FILE__))."/imgcommon.php";

class UploadActions extends ActionController {

    //上传头像文件。
    public function doUploadAvatarFile() {
        //list of valid extensions, ex. array("jpeg", "xml", "bmp")
        $allowedExtensions = array("jpeg", "gif", "png", "jpg", "bmp");
        //max file size in bytes
        $sizeLimit = 10 * 1024 * 1024;
        //The directory for the images to be saved in
        $upload_dir = "eimgs";

        $exFileUploader = $this->getHelperByName("fileUploader");
        $exFileUploader->initialize($allowedExtensions, $sizeLimit);
        $result = $exFileUploader->handleUpload($upload_dir);
        if(!$result["error"]){
            $img_name = $result["file_name"];
            $img_ext = $result["file_ext"];
            $img_path = $result["file_path"];

            //图片还要经过处理后再给客户端。
            $img_info = array(
                "source_image"      =>$img_path."/".$img_name,
                "target_image"      =>$img_path."/"."240_240_original_".$img_name,
                "width"             =>240,
                "height"            =>240,
            );
            asidoResizeImg($img_info);
        }

        // to pass data through iframe you will need to encode all html tags
        //echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
        echo json_encode($result);
    }


    //截剪头像。
    public function doSaveAvatarFile() {
        $identity_id = $_POST["identityID"];
        $img_name = $_POST["iName"];
        $img_height = $_POST["iHeight"];
        $img_width = $_POST["iWidth"];
        $img_x = $_POST["iX"];
        $img_y = $_POST["iY"];
        $img_dir = "eimgs";
        $img_path = getHashFilePath($img_name, $img_dir);

        $img_info = array(
            "source_image"      =>$img_path."/"."240_240_original_".$img_name,
            "target_image"      =>$img_path."/"."240_240_".$img_name,
            "width"             =>$img_width,
            "height"            =>$img_height,
            "x"                 =>$img_x,
            "y"                 =>$img_y
        );
        asidoResizeImg($img_info, $crop=true);

        $small_img_info = array(
            "source_image"      =>$img_path."/"."240_240_".$img_name,
            "target_image"      =>$img_path."/"."80_80_".$img_name,
            "width"             =>80,
            "height"            =>80
        );
        asidoResizeImg($small_img_info, $crop=false);

        $return_data = array(
            "status"    =>0,
            "msg"       =>""
        );

        if($identity_id == ""){
            $userData = $this->getModelByName("user");
            $userData->saveUserAvatar($img_name,$_SESSION["userid"]);
        }else{
            $identityData = $this->getModelByName("identity");
            $identityData->saveIdentityAvatar($img_name, $identity_id);
        }

        header("Content-Type:application/json; charset=UTF-8");
        echo json_encode($return_data);
        exit(0);
    }

}
