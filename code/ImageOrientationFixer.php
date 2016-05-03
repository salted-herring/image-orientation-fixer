<?php
class ImageOrientationFixer extends DataExtension {
	
	public function onBeforeWrite() {
		$ext = $this->owner->getExtension();
		if (strtolower($ext) == 'jpg' || strtolower($ext) == 'jpeg') {
			$path = $this->owner->getFullPath();
			if ($orientation = $this->get_orientation($path)) {
				if (extension_loaded('imagick')) {
					$this->rotate_imagick($path, $orientation);
				} elseif (extension_loaded('gd')) {
					$this->rotate_gd($path, $orientation);
				}
			}
		}
		
		parent::onBeforeWrite();
	}
	
	private function get_orientation($path) {
		if (!file_exists($path)) { return false; }
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
		switch ($orientation) {
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
		$image = imagecreatefromjpeg($path);
			
		switch ($orientation) {
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
		
		imagejpeg($image, $path, 100);
		imagedestroy($image);
		return true;
	}
}