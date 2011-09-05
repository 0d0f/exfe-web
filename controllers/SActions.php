<?php

class SActions extends ActionController {
  public function doTestUser()
  {
    $identityData = $this->getModelByName("identity");
    $identityData->setRelation($_GET["identity_id"]);
    
  }
  public function doAdd()
  {
    $identity= $_GET["identity"];
    $provider= $_GET["provider"];
    $password = $_GET["password"];


    #package as a  transaction
    if(intval($_SESSION["userid"])>0)
    {
	$userid=$_SESSION["userid"];
    }
    else
    {
	$Data = $this->getModelByName("user");
	$userid = $Data->addUser($password);
    }
    $identityData = $this->getModelByName("identity");
    $identityData->addIdentity($userid,$provider,$identity);
  }

  public function doUploadavatar()
  {
     
     if($_GET["a"]=="close")
     {
    	$this->displayViewByAction("close");
     }
	require_once "imgcommon.php";
    if(intval($_SESSION["userid"])>0)
    {
	$self_url="/s/uploadavatar";
	$upload_dir = "eimgs"; 				// The directory for the images to be saved in
	$upload_path = $upload_dir."/";				// The path to where the image will be saved
	$large_image_name = $_FILES['image']['name']; 		// New name of the large image
	if($large_image_name=="")
	    $large_image_name=$_SESSION["upload_imgname"];
	$max_file = "1148576"; 						// Approx 1MB
	$max_width = "500";							// Max width allowed for the large image
	$thumb_width = "80";						// Width of thumbnail image
	$thumb_height = "80";						// Height of thumbnail image
	$big_thumb_width = "240";						// Width of thumbnail image
	$big_thumb_height = "240";						// Height of thumbnail image
	$this->setVar("thumb_width", $thumb_width);
	$this->setVar("thumb_height", $thumb_height);

	$thumb_image_name = $thumb_width.'_'.$thumb_height."_".$large_image_name; 	// New name of the thumbnail image
	$big_thumb_image_name = $big_thumb_width.'_'.$big_thumb_height."_".$large_image_name; 	// New name of the thumbnail image
	//Image Locations
	$large_image_location = $upload_path.$large_image_name;
	$thumb_image_location = $upload_path.$thumb_image_name;
	$big_thumb_image_location = $upload_path.$big_thumb_image_name;

	$this->setVar("large_image_location", $large_image_location);
	$this->setVar("thumb_image_location", $thumb_image_location);


	
	//Create the upload directory with the right permissions if it doesn't exist
	if(!is_dir($upload_dir)){
		mkdir($upload_dir, 0777);
		chmod($upload_dir, 0777);
	}
	
	//Check to see if any images with the same names already exist
	if (file_exists($large_image_location)){
		if(file_exists($thumb_image_location)){
			$thumb_photo_exists = "<img src=\"/".$upload_path.$thumb_image_name."\" alt=\"Thumbnail Image\"/>";
		}else{
			$thumb_photo_exists = "";
		}
	   	$large_photo_exists = $upload_path.$large_image_name;
	   	#$large_photo_exists = "<img src=\"/".$upload_path.$large_image_name."\" alt=\"Large Image\"/>";
	} else {
	   	$large_photo_exists = "";
		$thumb_photo_exists = "";
	}
	 $this->setVar("large_photo_exists", $large_photo_exists);
	
	if (isset($_POST["upload"])) { 
		//Get the file information
		$userfile_name = $_FILES['image']['name'];
		$userfile_tmp = $_FILES['image']['tmp_name'];
		$userfile_size = $_FILES['image']['size'];
		$filename = basename($_FILES['image']['name']);
		$file_ext = substr($filename, strrpos($filename, '.') + 1);
		#
		#//Only process if the file is a JPG and below the allowed limit
		if((!empty($_FILES["image"])) && ($_FILES['image']['error'] == 0)) {
			if (($file_ext!="jpg") && ($userfile_size > $max_file)) {
				$error= "ONLY jpeg images under 1MB are accepted for upload";
			}
		}else{
			$error= "Select a jpeg image for upload";
		}
		//Everything is ok, so we can upload the image.
		if (strlen($error)==0){
			
			if (isset($_FILES['image']['name'])){
				
				//move_uploaded_file($userfile_tmp, $large_image_location);
				move_uploaded_file($userfile_tmp, $large_image_location);
				chmod($large_image_location, 0604);
				#
				$width = getWidth($large_image_location);
				$height = getHeight($large_image_location);
	
	 			$filename = stripslashes($_FILES['image']['name']);
	        		$extension = getExtension($filename);
	  			$extension = strtolower($extension);
				//Scale the image if it is greater than the width set above
				if ($width > $max_width){
					$scale = $max_width/$width;
					$uploaded = resizeImage($large_image_location,$width,$height,$scale,$extension);
				}else{
					$scale = 1;
					$uploaded = resizeImage($large_image_location,$width,$height,$scale,$extension);
				}



				//Delete the thumbnail file so the user can create a new one
				if (file_exists($thumb_image_location)) {
					unlink($thumb_image_location);
				}
			}
			//Refresh the page to show the new uploaded image
			header("location:".$self_url);
			$_SESSION["upload_imgname"]=$large_image_name;
			exit();
		}
	}
	
	if (isset($_POST["upload_thumbnail"]) && strlen($large_photo_exists)>0) {
		//Get the new coordinates to crop the image.
		$x1 = $_POST["x1"];
		$y1 = $_POST["y1"];
		$x2 = $_POST["x2"];
		$y2 = $_POST["y2"];
		$w = $_POST["w"];
		$h = $_POST["h"];
		//Scale the image to the thumb_width set above
		$big_scale = $big_thumb_width/$w;
		$scale = $thumb_width/$w;
	
	 	$filename = stripslashes($large_image_location);
	        $extension = getExtension($filename);
	  	$extension = strtolower($extension);
	
		$cropped = resizeThumbnailImage($big_thumb_image_location, $large_image_location,$w,$h,$x1,$y1,$big_scale,$extension);
		$cropped = resizeThumbnailImage($thumb_image_location, $large_image_location,$w,$h,$x1,$y1,$scale,$extension);
		$userData = $this->getModelByName("user");
		$userData->saveUserAvatar($large_image_name,$_SESSION["userid"]);
		//Reload the page again to view the thumbnail
		header("location:".$self_url."?a=close");
		exit();
	}

    	$this->displayView();
	}
	else
    	{
		header( 'Location: /s/login' ) ;
    	    exit(0);
    	}
  }
  public function doProfile()
  {
    if(intval($_SESSION["userid"])>0)
    {
	$identityData = $this->getModelByName("identity");
	$identities=$identityData->getIdentitiesByUser($_SESSION["userid"]);
	$this->setVar("identities", $identities);

	$userData = $this->getModelByName("user");
	$user=$userData->getUser($_SESSION["userid"]);
	$this->setVar("user", $user);

    	$this->displayView();
    }
    else
    {
	header( 'Location: /s/login' ) ;
	exit(0);
    }
  }

  public function doIfIdentityExist()
  {
//TODO: private API ,must check session
    $identity=$_GET["identity"];
    $identityData = $this->getModelByName("identity");
    $exist=$identityData->ifIdentityExist($identity);

    $responobj["meta"]["code"]=200;
    //$responobj["meta"]["errType"]="Bad Request";
    //$responobj["meta"]["errorDetail"]="invalid_auth";

    if($exist!==FALSE)
	$responobj["response"]["identity_exist"]="true";
    else
	$responobj["response"]["identity_exist"]="false";
    echo json_encode($responobj);
    exit();
  }
  public function doSaveUserIdentity()
  {
    //TODO: private API ,must check session
    $name=$_POST["name"];
    $userid=intval($_SESSION["userid"]);
    if ($userid > 0)
    {
	$userData = $this->getModelByName("user");
	$user=$userData->saveUser($name,$userid);
	$responobj["meta"]["code"]=200;
    	//$responobj["meta"]["errType"]="Bad Request";
    	//$responobj["meta"]["errorDetail"]="invalid_auth";
    	$responobj["response"]["user"]=$user;
    	echo json_encode($responobj);
    	exit();
    }
  }
  public function doGetUserProfile()
  {
    //TODO: private API ,must check session
    $name=$_POST["name"];
    $userid=intval($_SESSION["userid"]);
    if ($userid > 0)
    {
	$userData = $this->getModelByName("user");
	$user=$userData->getUser($userid);
	$responobj["meta"]["code"]=200;
    	//$responobj["meta"]["errType"]="Bad Request";
    	//$responobj["meta"]["errorDetail"]="invalid_auth";
    	$responobj["response"]["user"]=$user;
    	echo json_encode($responobj);
    	exit();
    }
  }

  public function doLogout()
  {
	unset($_SESSION["userid"]);
	unset($_SESSION["identity_id"]);
	unset($_SESSION["identity"]);
	unset($_SESSION["tokenIdentity"]);
  }

  public function doLogin()
  {
    $identity=$_POST["identity"];
    $password=$_POST["password"];
    $repassword=$_POST["retypepassword"];
    $displayname=$_POST["displayname"];
    $autosignin=$_POST["auto_signin"];

    $isNewIdentity=FALSE;

    if(isset($identity) && isset($password)  && isset($repassword) && isset($displayname) )
    {
	$Data = $this->getModelByName("user");
	$userid = $Data->AddUser($password);
	$identityData = $this->getModelByName("identity");
	$provider= $_POST["provider"];
	if($provider=="")
	    $provider="email";
	$identityData->addIdentity($userid,$provider,$identity);
	//TODO: check return value
	$isNewIdentity=TRUE;
    }


    if(isset($identity) && isset($password))
    {
	$Data=$this->getModelByName("identity");
    	$userid=$Data->login($identity,$password);
	if(intval($userid)>0)
	{
	    //$_SESSION["userid"]=$userid;
	    if($isNewIdentity===TRUE)
		$this->setVar("isNewIdentity", TRUE);

	    if(intval($autosignin)>0)
	    {
		//TODO: set cookie
		//set cookie
	    }

	    if($_GET["url"]!="")
		header( 'Location:'.$_GET["url"] ) ;
	    else
		$this->displayView();
	}
	else
	{
		$this->displayView();
	}
    }
    else
    {
	$this->displayView();
    }
  }
  public function doDialogaddidentity()
  {
    $identity=$_POST["identity"];
    $password=$_POST["password"];
    $repassword=$_POST["retypepassword"];
    $displayname=$_POST["displayname"];
    $autosignin=$_POST["auto_signin"];

    if(isset($identity) && isset($password) && isset($displayname) )
    {
   	 $identityData = $this->getModelByName("identity");
   	 $exist=$identityData->ifIdentityExist($identity);
	if($exist===FALSE)
	{
        	$Data = $this->getModelByName("user");
        	$userid = $Data->AddUser($password);
        	$identityData = $this->getModelByName("identity");
        	$provider= $_POST["provider"];
        	if($provider=="")
        	    $provider="email";
        	$identity_id=$identityData->addIdentity($userid,$provider,$identity);
    		$userid=$identityData->login($identity,$password);
		if(intval($userid)>0)
		{
		    $responobj["response"]["success"]="true";
		    $responobj["response"]["userid"]=$userid;
		    $responobj["response"]["identity_id"]=$identity_id;
		    $responobj["response"]["identity"]=$identity;
		    echo json_encode($responobj);
    		    exit();
		}
	}
        //TODO: check return value
        //$isNewIdentity=TRUE;


	    //$_SESSION["userid"]=$userid;
	//    if($isNewIdentity===TRUE)
	//	$this->setVar("isNewIdentity", TRUE);

	//    if(intval($autosignin)>0)
	//    {
	//	//TODO: set cookie
	//	//set cookie
	//    }

	//}
	}
	$responobj["response"]["success"]="false";
	echo json_encode($responobj);
    	exit();
  }
  public function doDialoglogin()
  {
    $identity=$_POST["identity"];
    $password=$_POST["password"];
    if(isset($identity) && isset($password))
    {
	$Data=$this->getModelByName("identity");
    	$userid=$Data->login($identity,$password);

	if(intval($userid)>0)
	{
	    $responobj["response"]["success"]="true";
	    $responobj["response"]["userid"]=$userid;
	}
	else
	    $responobj["response"]["success"]="false";
    }
    else
	$responobj["response"]["success"]="false";
    echo json_encode($responobj);
    exit();
  }
  public function doSetpwd()
  {
    $responobj["meta"]["code"]=200;
    #$cross_id=base62_to_int($_POST["cross_id"]);
    $cross_id=intval($_POST["cross_id"]);
    $token=$_POST["token"];
    if(strlen($token)>32)
	$token=substr($token,0,32);
    $password=$_POST["password"];
    $displayname=$_POST["displayname"];
    if($password=="")
    {
	$responobj["response"]["success"]=$result;
    	$responobj["response"]["error"]="must set password";
    	echo json_encode($responobj);
    	exit();
    }
    if($displayname=="")
    {
	$responobj["response"]["success"]=$result;
    	$responobj["response"]["error"]="must set display name";
    	echo json_encode($responobj);
    	exit();
    }
    
    $identityData=$this->getModelByName("identity");
    $identity_id=$identityData->loginWithXToken($cross_id, $token);
    $result="false";

    if(intval($identity_id)>0)
    {
	$userData=$this->getModelByName("user");
	$r=$userData->setPassword($identity_id,$password,$displayname);
	if(intval($r)==1)
	{
	    $result="true";
	    $userid=$identityData->loginByIdentityId($identity_id);
	}
    }

    $responobj["response"]["success"]=$result;
    if($result=="false")
    {
	$responobj["response"]["error"]["identity_id"]=$identity_id;
	$responobj["response"]["error"]["user_id"]=$user_id;
	$responobj["response"]["error"]["setpassword"]=$r;
	$responobj["response"]["error"]["action"]="login with $cross_id and $token";
    }

    echo json_encode($responobj);
    exit();
    
    //$responobj["meta"]["errType"]="Bad Request";
    //$responobj["meta"]["errorDetail"]="invalid_auth";
    //setPassword($identity_id,$password)
  }
}

