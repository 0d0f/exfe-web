<?php

class AvatarActions extends ActionController {

	public function doGet() {
		$params = $this->params;
		// if ($params['url']) {

		// }

		$type = strtolower(array_pop(explode('.', $params['url'])));

		switch ($type) {
			case 'png':
				$image = ImageCreateFromPNG($params['url']);
				break;
			case 'jpg':
			case 'jpeg':
				$image = ImageCreateFromJpeg($params['url']);
				break;
			case 'gif':
				$image = ImageCreateFromGif($params['url']);
				break;
			default:
				// error
		}





		header('Pragma: no-cache');
        header('Cache-Control: no-cache');
        header('Content-Transfer-Encoding: binary');
        header('Content-type: image/png');
        imagepng($image);






	}

}
