<?php
/*
Simply takes a URL and spits out the image.
This is needed by WebGL because it won't load remote images as textures.
https://developer.mozilla.org/en/WebGL/Cross-Domain_Textures
- Jeff Sept 8 2011
*/

$filename = $_GET['url'];
$ext = pathinfo($filename, PATHINFO_EXTENSION);
 
switch ($ext) {
	case "jpg":
		header('Content-Type: image/jpeg');
		readfile($filename);
		break;
	case "gif":
		header('Content-Type: image/gif');
		readfile($filename);
		break;
	case "png":
		header('Content-Type: image/png');
		readfile($filename);
		break;
	default:
		header('Content-Type: text/xml');
		readfile($filename);
		break;
	}
?>