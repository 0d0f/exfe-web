<?php

function getExtension($str) {

         $i = strrpos($str,".");
         if (!$i) { return ""; } 
         $l = strlen($str) - $i;
         $ext = substr($str,$i+1,$l);
         return $ext;
 }
//Image functions
//You do not need to alter these functions
function resizeImage($image,$width,$height,$scale,$extension) {
	$newImageWidth = ceil($width * $scale);
	$newImageHeight = ceil($height * $scale);
	$newImage = imagecreatetruecolor($newImageWidth,$newImageHeight);

	if($extension=="jpg" || $extension=="jpeg" )
	{
	$source = imagecreatefromjpeg($image);
	}
	else if($extension=="png")
	{
	$source = imagecreatefrompng($image);
	}
	else if($extension=="gif")
	{
	$source = imagecreatefromgif($image);
	}
	else
	{
	    return FALSE;
	}
	 
	//$source = imagecreatefromjpeg($image);
	imagecopyresampled($newImage,$source,0,0,0,0,$newImageWidth,$newImageHeight,$width,$height);
	if($extension=="jpg" || $extension=="jpeg" )
	    imagejpeg($newImage,$image,90);
	else if($extension=="png")
	    imagepng($newImage,$image,9);
	else if($extension=="gif")
	    imagegif($newImage,$image);
	chmod($image, 0644);
	return $image;
}
//You do not need to alter these functions
function resizeThumbnailImage($thumb_image_name, $image, $width, $height, $start_width, $start_height, $scale,$extension){
	$newImageWidth = ceil($width * $scale);
	$newImageHeight = ceil($height * $scale);
	$newImage = imagecreatetruecolor($newImageWidth,$newImageHeight);
	//$source = imagecreatefromjpeg($image);

	if($extension=="jpg" || $extension=="jpeg" )
	{
	$source = imagecreatefromjpeg($image);
	}
	else if($extension=="png")
	{
	$source = imagecreatefrompng($image);
	}
	else 
	{
	$source = imagecreatefromgif($image);
	}


	imagecopyresampled($newImage,$source,0,0,$start_width,$start_height,$newImageWidth,$newImageHeight,$width,$height);

	if($extension=="jpg" || $extension=="jpeg" )
	    imagejpeg($newImage,$thumb_image_name,90);
	else if($extension=="png")
	    imagepng($newImage,$thumb_image_name,9);
	else if($extension=="gif")
	    imagegif($newImage,$thumb_image_name);

	chmod($thumb_image_name, 0644);
	return $thumb_image_name;
}
//You do not need to alter these functions
function getHeight($image) {
	$sizes = getimagesize($image);
	$height = $sizes[1];
	return $height;
}
//You do not need to alter these functions
function getWidth($image) {
	$sizes = getimagesize($image);
	$width = $sizes[0];
	return $width;
}


