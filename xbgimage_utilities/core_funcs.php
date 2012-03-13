<?php

error_reporting(E_ALL ^ E_NOTICE);

require_once dirname(__FILE__)."/common.php";
require_once dirname(__FILE__)."/DataModel.php";

function resizeImage($srcFile,$toWidth,$toHeight,$toFile,$toQuality)
{
	$tinfo='';
	$data = GetImageSize($srcFile,$tinfo);
	switch ($data[2]) 
	{
		case 1: 
			$curImage = ImageCreateFromGIF($srcFile); 
		break; 
		case 2:
			$curImage = ImageCreateFromJpeg($srcFile);  
		break; 
		case 3:
			$curImage = ImageCreateFromPNG($srcFile);  
		break; 
	}
	
	$curWidth = imagesx($curImage);
    $curHeight = imagesy($curImage);

	$ratioWidth = $toWidth / $curWidth;
	$ratioHeight = $toHeight / $curHeight;

	$ratioX = $ratioWidth < $ratioHeight ? $ratioHeight : $ratioWidth;			

	$draftWidth = $curWidth * $ratioX;
	$draftHeight = $curHeight * $ratioX;
	$draftImage = imagecreatetruecolor($draftWidth,$draftHeight);
	imagecopyresampled($draftImage, $curImage, 0, 0, 0, 0, $draftWidth, $draftHeight, $curWidth, $curHeight);			
	
	$newImage = imagecreatetruecolor($toWidth,$toHeight);
	imagecopyresampled($newImage, $draftImage, 0, 0, ($draftWidth-$toWidth)/2, ($draftHeight-$toHeight)/2, $toWidth, $toHeight, $toWidth, $toHeight);			
	
	$toType = explode('.',$toFile);
	$toType = strtolower($toType[count($toType)-1]);
	
	if (file_exists($toFile)){unlink($toFile);}
	
	switch ($toType)
	{
		case 'gif':
			ImageGif ($newImage,$toFile);	
		break;
		case 'jpg':
		case 'jpeg':
		    ImageJpeg ($newImage,$toFile,$toQuality);
		break;
		case 'png':
			ImagePng ($newImage,$toFile);
		break;
	}
	
	ImageDestroy($draftImage);    
	ImageDestroy($newImage);	
}




?>