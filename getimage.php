<?php 
/**
* Detailed copyright and licensing information can be found
* in the gpl-3.0.txt file which should be included in the distribution.
* 
* @version		1.0 2012-10-02 Iskar Enev
* @copyright	2016 Iskar Enev
* @license		GPLv3 Open Source
* @since		File available since initial release
*/

// no direct access
define('_JEXEC', 1);

include 'image.php';
$doc_root = str_replace(DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'mod_articles_frontpage', '', dirname(__FILE__));

$f = explode(':',base64_decode($_GET['f']));
if (!is_array($f) || count($f) < 2) exit;
list($name, $image_dims) = $f;
// TODO - make these module parameters
if (!in_array($image_dims, array('150x', '100x', '75x', '50px'))) exit;

$file = '';
$basename = basename($name);
$ext = substr($name, (strrpos($name, '.') + 1));
$basefolder = str_replace($basename, '', $name);

$original_file = $doc_root . DIRECTORY_SEPARATOR . $name;
$file = $doc_root . DIRECTORY_SEPARATOR . $basefolder . $image_dims . '_' . $basename;

if (strpos($image_dims, 'x') !== false) {
	$dim_arr = explode('x', $image_dims);
	$image_width = (int)$dim_arr[0];
	$image_height = (int)$dim_arr[1];
} else {
	$image_width = (int)$image_dims;
	$image_height = 0;
}
if (!is_file($file)) {
	$res = modArticlesFrontpageImage::resize($original_file, $file, $image_width, $image_height);
	if ((int)$res == -2) {
		$file = $original_file;
	}
}

if ($file && is_file($file)) {
	if ($ext == 'png') header("Content-type: image/png");
	elseif ($ext == 'gif') header("Content-type: image/gif");
	else header("Content-type: image/jpeg");
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	header('Content-Length: ' . filesize($file));
	@ob_clean();
	flush();
	readfile($file);
}
exit;