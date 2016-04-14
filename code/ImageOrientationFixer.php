<?php
//Debugger::inspect(extension_loaded('imagick')?'yes':'no');
class ImageOrientationFixer extends DataExtension {
	//if (extension_loaded('gd') && function_exists('gd_info')) {
	//if(extension_loaded('imagick')) {
		
	public function updateCMSFields( FieldList $fields ) {
		$path = $this->owner->getFullPath();
		$orientation = $this->get_orientation($path);
	}
	
	public function onBeforeWrite() {
		
		$path = $this->owner->getFullPath();
		if ($orientation = $this->get_orientation($path)) {
			
			if (extension_loaded('imagick')) {
				$this->rotate_imagick($path, $orientation);
			} elseif (extension_loaded('gd')) {
				$this->rotate_gd($path, $orientation);
			}
		}
		
		parent::onBeforeWrite();
	}
	
	private function get_orientation($path) {
		$exif_data = exif_read_data($path);
		$orientation = !empty($exif_data['Orientation']) ? $exif_data['Orientation'] : null;
		/**
		 * this image will help you understand the orientation and the difference between encoded and printed
		 * http://www.kendawson.ca/wp-content/uploads/orient_flag2.gif
		 */
		return $orientation == 1 ? false : $orientation;
	}
	
	private function rotate_imagick($imagePath, $orientation) {
		$imagick = new Imagick($imagePath);
		$imagick->setImageOrientation(imagick::ORIENTATION_TOPLEFT);
		$deg = 0;
		switch($orientation) {
			case 3:
				$deg = -180;
				break;
			case 6:
				$deg = 90;
				break;
			case 8:
				$deg = -90;
				break;
		}
		$imagick->rotateImage(new ImagickPixel('#00000000'), $deg);
		$imagick->writeImage($imagePath);
		$imagick->clear(); 
		$imagick->destroy(); 
	}
	
	private function rotate_gd($path, $orientation) {
		$imgInfo = getimagesize($path);
		switch ($imgInfo[2]) {
			case 1:
				$image = imagecreatefromgif($path);
				break;
			case 2:
				$image = imagecreatefromjpeg($path);
				break;
			case 3:
				$image = imagecreatefrompng($path);
				break;
			default:
				return false;
		}
		
		switch($orientation) {
			case 3:
				$image = imagerotate($image, 180, 0);
				break;
			case 6:
				$image = imagerotate($image, -90, 0);
				break;
			case 8:
				$image = imagerotate($image, 90, 0);
				break;
		}
		
		switch ($imgInfo[2]) {
			case 1:
				imagegif($image, $path);
				break;
			case 2:
				imagejpeg($image, $path, 100);
				break;
			case 3:
				imagepng($image, $path);
				break;
			default:
				imagedestroy($image);
				return false;
		}
		
		imagedestroy($image);
		
		return true;
	}
}