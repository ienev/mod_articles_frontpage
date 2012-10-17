<?php 
/**
* Detailed copyright and licensing information can be found
* in the gpl-3.0.txt file which should be included in the distribution.
* 
* @version		1.0 2012-10-02 Iskar Enev
* @copyright	2012 Iskar Enev
* @license		GPLv3 Open Source
* @since		File available since initial release
*/
 
// no direct access
defined('_JEXEC') or die('Restricted access');

class modArticlesFrontpageImage { 
	
	function resize ($source, $dest, $width_new, $height_new) {
		$lib = modArticlesFrontpageImage::getLib();
		if (!$lib) return;
		switch($lib) {
			case 'imagemagick':
				return modArticlesFrontpageImage::resizeImageMagick($source, $dest, $width_new, $height_new);
				break;
			case 'imagick':
				return modArticlesFrontpageImage::resizeImagick($source, $dest, $width_new, $height_new);
				break;
			case 'gd':
			default: 
				return modArticlesFrontpageImage::resizeGD($source, $dest, $width_new, $height_new);
		}
	}
	
	function resizeGD($source, $dest, $width_new, $height_new) {
		
		// get image properties
		list($width_orig, $height_orig, $img_type, $attr) = getimagesize($source);
		
		// determine if the image is landscape or portrait and if we are making a square image
		$landscape = ($width_orig > $height_orig) ? true : false;
		$portrait = ($width_orig < $height_orig) ? true : false;
		$square = ($width_new == $height_new) ? true : false;
		
		// offsets - only needed for square images
		$x = 0;
		$y = 0;
		
		/* UPDATE: For now we will comment this out as it doesn't work with GD as expected.
		   // If height_new = 0, then we use the width instead. This is in the case we have only one parameter for size, i.e "the wider size" 
		   if (!(int)$height_new) $height_new = $width_new;
		*/
		
		// if the original image is smaller than or equal to the dimensions we are resizing to, then we don't resize
		if (($landscape && ($width_new >= $width_orig)) || ($portrait && ($height_new >= $height_orig)) || ($square && ($width_new >= $width_orig))) {
			return -2;
		}
		
		// we have to make different calculations for a square image
		if ($square) {
			// if the original image is not square we have to cut from it :
			// from left and right if landscape image and from top and bottom if portrait image
			if ($landscape) {
				$x = ceil(($width_orig - $height_orig)/2);
				$width_orig = $height_orig;
			} elseif($portrait) {
				$y = ceil(($height_orig - $width_orig)/2);
				$height_orig = $width_orig;
			}
		} else {
			$height_new = ceil(($width_new/$width_orig)*$height_orig);
			/*
			// we preserve the ratio so we have to determine destination height if landscape and destination width if portrait
			if ($landscape) {
				$height_new = ceil(($width_new/$width_orig)*$height_orig);
			} elseif ($portrait) {
				$width_new = ceil(($height_new/$height_orig)*$width_orig);
			}
			*/
		}
		
		// create source image resource
		switch ($img_type) {
			case IMAGETYPE_GIF: $source_img = imagecreatefromgif($source); break;
			case IMAGETYPE_JPEG: $source_img = imagecreatefromjpeg($source); break;
			case IMAGETYPE_PNG: $source_img = imagecreatefrompng($source); break;
			default: return false;
		}

		// create destination image resource		
		if ($img_type == IMAGETYPE_GIF) {
			$dest_img = imagecreate($width_new, $height_new);
		} else {
			$dest_img = imagecreatetruecolor($width_new, $height_new);
		}
		
		// handle transparency for GIFs and PNGs
		if (($img_type == IMAGETYPE_GIF) || ($img_type == IMAGETYPE_PNG) ) {
			$transparent_idx = imagecolortransparent($source_img);
			if ($transparent_idx >= 0) {
				$transparent_color = imagecolorsforindex($source_img, $transparent_idx);
				$transparent_idx = imagecolorallocate($dest_img, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);
				imagefill($dest_img, 0, 0, $transparent_idx);
				imagecolortransparent($dest_img, $transparent_idx);
			} elseif ($img_type == IMAGETYPE_PNG) {
				imagealphablending($dest_img, false);
				$color = imagecolorallocatealpha($dest_img, 0, 0, 0, 127);
				imagefill($dest_img, 0, 0, $color);
				imagesavealpha($dest_img, true);
			}
		}
		
		// resize from source image to destination image
		if(!imagecopyresampled($dest_img, $source_img, 0, 0, $x, $y, $width_new, $height_new, $width_orig, $height_orig)) {
			return false;
		}
		
		// write the image
		switch ($img_type) {
			case IMAGETYPE_GIF: imagegif($dest_img, $dest); break;
			// third parameter is quality: 0 (worst quality, smaller file) to 100 (best quality, biggest file), default is 75
			case IMAGETYPE_JPEG: imagejpeg($dest_img, $dest, 90); break;
			// third parameter is compression level: from 0 (no compression) to 9
			case IMAGETYPE_PNG: imagepng($dest_img, $dest, 4); break;
			default: return false;
		}
		
		imagedestroy($source_img);
		imagedestroy($dest_img);
		
		return true;
	}
	
	function resizeImageMagick($source, $dest, $width_new, $height_new) {
	
		$cmd = 'convert';
		@exec('which convert', $res, $ret);
		if (!$ret) $cmd = $res[0];
		
		$img_info = getimagesize($source);
		list($width_orig, $height_orig, $img_type, $attr) = $img_info;
		
		// determine if the image is landscape or portrait and if we are making a square image
		$landscape = ($width_orig > $height_orig) ? true : false;
		$portrait = ($width_orig < $height_orig) ? true : false;
		$square = ($width_new == $height_new) ? true : false;
		
		// offsets - only needed for square images
		$x = 0;
		$y = 0;

		// If height_new = 0, then we use the width instead. This is in the case we have only one parameter for size, i.e "the wider size" 
		if (!(int)$height_new) $height_new = $width_new;
		
		// if the original image is smaller than or equal to the dimensions we are resizing to, then we don't resize
		if (($landscape && ($width_new >= $width_orig)) || ($portrait && ($height_new >= $height_orig)) || ($square && ($width_new >= $width_orig))) {
			return -2;
		}
		
		// we have to make different calculations and commands for a square image
		if ($square) {
		
			// if the original image is not square we have to cut from it :
			// from left and right if landscape image and from top and bottom if portrait image
			if($width_orig > $height_orig) {
				$x = ceil(($width_orig - $height_orig)/2);
				$width_orig = $height_orig;
			} elseif($height_orig > $width_orig) {
				$y = ceil(($height_orig - $width_orig)/2);
				$height_orig = $width_orig;
			}
			$cmdline = $cmd . '  -crop ' . escapeshellarg($width_orig . 'x' . $height_orig . '+' . $x . '+' . $y) . ' -thumbnail ' . escapeshellarg($width_new . 'x>') . ' ' . escapeshellarg($source) . ' ' . escapeshellarg($dest) . ' ';
			
		} else {
			// rectangular image
			$cmdline = $cmd . ' -thumbnail ' . escapeshellarg($width_new . 'x>') . ' ' . escapeshellarg($source) . ' ' . escapeshellarg($dest) . ' ';
		}
		
		exec($cmdline, $results, $return);
		
		if( $return > 0 ) {
			return false;
		} else { 
			return true;
		}

	}
	
	function resizeImagick($source, $dest, $width_new, $height_new) {
		
		$image = new Imagick();
		$image->readImage($source);
		
		// get image properties
		$width_orig = $image->getImageWidth();
		$height_orig = $image->getImageHeight();
		
		// determine if the image is landscape or portrait and if we are making a square image
		$landscape = ($width_orig > $height_orig) ? true : false;
		$portrait = ($width_orig < $height_orig) ? true : false;
		$square = ($width_new == $height_new) ? true : false;
		
		// if the original image is smaller than or equal to the dimensions we are resizing to, then we don't resize
		if (($landscape && ($width_new >= $width_orig)) || ($portrait && ($height_new >= $height_orig)) || ($square && ($width_new >= $width_orig))) {
			return -2;
		}
		
		if ($square) {
			// we use a different method for making square images
			$ret = $image->cropThumbnailImage($width_new, $height_new);			
		} else {
			$ret = $image->resizeImage($width_new, $height_new, null, 1);
		}
		
		$image->writeImage($dest);
		$image->clear();
		$image->destroy();
		if($ret) {
			return true;
		} else { 
			return false;
		}

	}
	
	function getLib() {
		static $mod_frontpage_image_lib;
		if (!$mod_frontpage_image_lib) {
			$mod_frontpage_image_lib = '';
			$libs = array('imagick', 'imagemagick', 'gd');
			foreach ($libs as $lib) {
				if (modArticlesFrontpageImage::detectLib($lib) !== false) {
					$mod_frontpage_image_lib = $lib;
					break;
				}
			}
		}
		return $mod_frontpage_image_lib;
	}
	
	function detectLib($lib) {
	
		if ($lib == 'gd') {
			$funcs = get_extension_funcs('gd');
			if (extension_loaded('gd') && is_array($funcs) && in_array('imagegd2', $funcs)) {
				$version = '';				
				ob_start();
				@phpinfo(INFO_MODULES);
				$output = ob_get_contents();
				ob_end_clean();				
				if(preg_match("/GD Version[ \t]*(<[^>]+>[ \t]*)+([^<>]+)/s",$output,$matches)) {
					$version = $matches[2];
				}
				return $version;
			} else {
				return false;
			}
		} elseif ($lib == 'imagemagick') {
			$cmd = 'convert';
			@exec('which convert', $res, $ret);
			if (!$ret) $cmd = $res[0];
			@exec($cmd . ' -version',  $result, $return);
			if(!$return){
				if(preg_match("/imagemagick[ \t]+([0-9\.]+)/i",$result[0],$matches)){
					return $matches[0];
				} else {
					return false;
				}
			} else {
				return false;
			}
		} elseif ($lib == 'imagick') {
			if (extension_loaded('imagick') && class_exists('Imagick')) {
				$version = '';				
				ob_start();
				@phpinfo(INFO_MODULES);
				$output = ob_get_contents();
				ob_end_clean();				
				if(preg_match("/imagick module version [ \t]*(<[^>]+>[ \t]*)+([^<>]+)/s",$output,$matches)){
					$version = $matches[2];
				}
				return $version;
			} else {
				return false;
			}
		}
		
		return false;
		
	}
	
}