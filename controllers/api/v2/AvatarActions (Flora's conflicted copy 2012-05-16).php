<?php

class AvatarActions extends ActionController {

	public function doGet() {
		// config
		$params = $this->params;
		// if ($params['url']) {

		// }
		// get source image
		$type = strtolower(array_pop(explode('.', $params['url'])));
		switch ($type) {
			case 'png':
				$srcImage = ImageCreateFromPNG($params['url']);
				break;
			case 'jpg':
			case 'jpeg':
				$srcImage = ImageCreateFromJpeg($params['url']);
				break;
			case 'gif':
				$srcImage = ImageCreateFromGif($params['url']);
				break;
			default:
				// error
		}
		$imgSize  = array(imagesx($srcImage), imagesy($srcImage));
		// make target image
		$tgtImage = imagecreatetruecolor($imgSize[0], $imgSize[1]);
		$penColor = imagecolorallocatealpha($tgtImage, 255, 255, 255, 10);
imagecopyresampled(
                            $img2, $image, 0, 0, 0, 0,
                            80, 80,
                            80, 80
                        );
//$img2=imagecreatetruecolor(80, 80);
$coverColor = imagecolorallocatealpha($img2, 255, 255, 255, 10);
// print_r($imgSize);
// return;
for ($x = 0; $x <= $imgSize[0] - 1; $x++) {
	imageline($img2, $x, 0, $x, $imgSize[1], $coverColor);
	// for ($y = 0; $y <= $imgSize[1] - 1; $y++) {
	// 	$pixel = imagecolorat($image, $x, $y);
	// 	$pixel = array(($pixel >> 16) & 0xFF, ($pixel >> 8) & 0xFF, $pixel & 0xFF);
	// 	$color = imagecolorallocatealpha($image, $pixel[0], $pixel[1], $pixel[2], 100);
	// 	//print_r($pixel);
	// 	imagesetpixel($image, $x, $y, $color);
	// 	//echo '---';
	// 	//imagesetpixel
	// }
}


//return;














// /**
// * 将jpeg图片的某个背景颜色透明化，转成gif图片
// * @param string $jpg_img 输入文件地址
// * @param string $bg_color 背景颜色，格式 例如 #ffffff
// * @param int $alpha 图片透明度 其值从 0 到 127。0 表示完全不透明，127 表示完全透明。
// * @param float $radio RGB颜色过滤因子，0-1
// * @return boolen
// **/
// //function jpeg2transpng($jpg_img,$png_img,$bg_color,$alpha,$radio){
// function jpeg2transpng($jpg_img,$bg_color,$alpha,$radio){
//   $im_in  =imagecreatefromjpeg($jpg_img);
//   if(!$im_in) {
//     return false;
//   }
//   $size  =getimagesize($jpg_img);

//   //创建透明画布
//   $im_out =imagecreatetruecolor($size[0], $size[1]);
//   imagealphablending($im_out, true);
//   imagesavealpha($im_out, true);
//   $trans_colour = imagecolorallocatealpha($im_out, 0, 0, 0, 127);
//   imagefill($im_out, 0, 0, $trans_colour);

//   //设定透明色
//   $red = intval( hexdec(substr($bg_color,1,2))*$radio );
//   $green = intval( hexdec(substr($bg_color,3,2))*$radio);
//   $blue = intval( hexdec(substr($bg_color,5,2))*$radio);

//   //像素级的图片合成
//   for ($j=0;$j<=$size[1]-1;$j++)
//   {
//       for ($i=0;$i<=$size[0]-1;$i++)
//       {
//         $rgb = imagecolorat($im_in,$i,$j);
//         $r = ($rgb >> 16) & 0xFF;
//         $g = ($rgb >> 8) & 0xFF;
//         $b = $rgb & 0xFF;
//        if ($r>=$red && $g>=$green && $b>=$blue)
//         {
//             //echo '.';
//           continue;
//         }
//         //echo "<a href='#".dechex($r).dechex($g).dechex($b)."'>*</a>";
//           $color=imagecolorallocatealpha($im_out,$r,$g,$b,$alpha);
//          imagesetpixel($im_out,$dst_x+$i,$dst_y+$j,$color);
//       }
//       //echo "\n";
//   }

//   //生成图片
//   //imagepng($im_out,$png_img);
//   imagepng($im_out);
//   imagedestroy($im_in);
//   imagedestroy($im_out);

//   return true;
// }


// //jpeg2transpng($filename,'t.png','#ffffff',20,0.618);

// jpeg2transpng($filename,'#ffffff',20,0.618);











		header('Pragma: no-cache');
        header('Cache-Control: no-cache');
        header('Content-Transfer-Encoding: binary');
        header('Content-type: image/png');
        imagepng($img2);






	}

}
