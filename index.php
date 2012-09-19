<?php

$SUPPORTED_IMAGE_FORMAT = array(
	"jpeg"
);
$SUPPORTED_IMAGE_EXTENSION = array( 
	"jpeg" => "jpeg",
	"jpg" => "jpeg"
);

function http_var_encode($query_string)
{
        return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, hash("SHA256", "55n9*EWTy-98bat-98BW9tWE*byet98bypbvtwe8betBPW", true), $query_string, MCRYPT_MODE_CBC, md5(md5("VQb0Qqq98wrvbyW7qwQ*&Wrbr33#*R#8byr"))));
}

function http_var_decode($enc_query_string)
{
        return rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, hash("SHA256", "55n9*EWTy-98bat-98BW9tWE*byet98bypbvtwe8betBPW", true), base64_decode($enc_query_string), MCRYPT_MODE_CBC, md5(md5("VQb0Qqq98wrvbyW7qwQ*&Wrbr33#*R#8byr"))), "\0");
}

//$test = "This is a test.";
//
//echo $test."<BR>";
//echo http_var_encode($test)."<BR>";
//echo http_var_decode(http_var_encode($test))."<BR>";

function clean($elem)
{
	if(!is_array($elem))
	{
		$elem = htmlentities($elem,ENT_QUOTES,"UTF-8");
	}
	else
	{
		foreach ($elem as $key => $value)
		{
			$elem[$key] = clean($value);
		}
	}
	return $elem;
} 

function validate_query_string($query_string)
{
	$validated_query = array();

	$elem = split("&", $query_string);

	foreach ($elem as $next_elem) {
		$key_value = clean(split("=", $next_elem));
		$validated_query[$key_value[0]] = $key_value[1];
	}

	unset($next_elem);
	unset($elem);

	return $validated_query;
}

/**
 * Returns TRUE if $path leads to an image file in a supported format,
 *  FALSE otherwise 
 */
function is_image($path)
{
	global $SUPPORTED_IMAGE_FORMAT, $SUPPORTED_IMAGE_EXTENSION;

	$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
	foreach ($SUPPORTED_IMAGE_EXTENSION as $img_ext => $img_type)
	{
		if (strcmp($ext, $img_ext) == 0)
		{
			unset ($img_ext);
			return TRUE;
		}
	}
	unset ($img_ext);
	return FALSE;
}

/**
 * $dirname is a relative path
 * Returns TRUE if at least one image file is found, FALSE otherwise
  */
function get_image_files($dirname)
{
	$image = array();
	$contents = glob($dirname."/*");
	foreach ($contents as $filename)
	{
		if (is_image($filename))
		{
			$image[] = $filename;
		}
	}
	unset($filename);
	return $image;
}

/**
 * $dirname is a relative path
 * Returns TRUE if at least one image directory is found, FALSE otherwise
  */
function get_image_directories($dirname)
{
	$image_dir = array();
	$contents = glob($dirname."/*");
	foreach ($contents as $filename)
	{
		if (is_dir($filename))
		{
			if (count(get_image_files($filename)) > 0)
			{
				$image_dir[] = $filename;
			}
			else if (get_image_directories($dirname."/".basename($filename)))
			{
				$image_dir[] = $filename;
			}				
		}
	}
	unset($filename);
	return $image_dir;
}

function display_thumbnail($path)
{
	global $IMAGE_LIBRARY_PATH, $THUMBNAIL_WIDTH, $THUMBNAIL_HEIGHT;

	if (is_dir($path))
	{
		// Display a link to this directory

		if (strcmp($path, $IMAGE_LIBRARY_PATH) != 0)
		{
			// Encrypt page query string
			$query_string = "display=page&path=".$path;
			$query = array( "q" => http_var_encode($query_string) );
			$enc_query_string = http_build_query($query);
		}

		// Create link
		echo "\t\t<a href=\"index.php?".$enc_query_string."\">".basename($path)."</a>\n";
	}
	else if (is_image($path))
	{
		// Display a thumbnail of this image

		// Encrypt thumbnail query string
		$query_string = "display=image&path=".$path;
		$query = array( "q" => http_var_encode($query_string) );
		$enc_image_query_string = http_build_query($query);

		// Encrypt image query string
		$query_string = "display=thumbnail&path=".$path;
		$query = array( "q" => http_var_encode($query_string) );
		$enc_thumb_query_string = http_build_query($query);

		// Create link
		echo "\t\t<a href=\"index.php?".$enc_image_query_string."\">\n".
			"\t\t\t<img src=\"index.php?".$enc_thumb_query_string."\" alt=?\"".basename($path)."\">\n".
			"\t\t</a>\n";
	}
	else
	{
		echo "Error: Invalid path or file type! (".$path.")";;
	}
}

function generate_thumbnail($path, $max_width, $max_height)
{
	// Assume JPEG for now...
	
	// Create a copy of the image in memory
	$img = @imagecreatefromjpeg($path) or die("Cannot create new JPEG image");

	// Determine the scale based on the required maximum dimensions
	$img_width = imagesx($img);
	$img_height = imagesy($img);
	$thumb_scale = min($max_width / $img_width, $max_height / $img_height);

	// Shrink the image
	if ($thumb_scale < 1)
	{
		$thumb_width = floor($thumb_scale * $img_width);
		$thumb_height = floor($thumb_scale * $img_height);

		$temp_img = imagecreatetruecolor($thumb_width, $thumb_height);

		imagecopyresampled($temp_img, $img, 0, 0, 0, 0, $thumb_width, $thumb_height, $img_width, $img_height);

		// Free up memory
		imagedestroy($img);
		$img = $temp_img;	
	}

	// Output image data to the browser
	header("Content-type: image/jpeg");
	imagejpeg($img);

	// Free up memory
	imagedestroy($img);
}

function display_image($path)
{
	// Assume JPEG for now...

	// Output image data to the browser
	header("Content-type: image/jpeg");
	readfile($path);
}

function print_directory_list_header() {
	echo "\t<div id=\"directory_list\">\n"; 
}

function print_directory_list_footer() {
	echo "\t</div>\n";
}

function print_image_list_header() {
	echo "\t<div id=\"image_list\">\n";
}

function print_image_list_footer() {
	echo "\t</div>\n";
}

//// EXECUTION BEGINS HERE

// Thumbnailer globals
$IMAGE_LIBRARY_PATH = "Latin America (2010-2012)";
$THUMBNAIL_WIDTH = 100;
$THUMBNAIL_HEIGHT = 100;

// Check query string
$VALIDATED_GET = array();
if (isset($_GET["q"])) 
{
	$VALIDATED_GET = validate_query_string(http_var_decode($_GET["q"]));
}

if (strcmp($VALIDATED_GET["display"], "thumbnail") == 0)
{
	// DISPLAY A THUMBNAIL OF THE REQUIRED IMAGE
	if (is_image($VALIDATED_GET["path"]))
	{
		generate_thumbnail($VALIDATED_GET["path"], $THUMBNAIL_WIDTH, $THUMBNAIL_HEIGHT);
		exit;
	}
}
else if (strcmp($VALIDATED_GET["display"], "image") == 0)
{
	// DISPLAY THE REQUIRED IMAGE
	if (is_image($VALIDATED_GET["path"]))
	{
		display_image($VALIDATED_GET["path"]);
		exit;
	}
}

//// HTML BEGINS HERE
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	<title>Thumbnail browser</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="robots" content="noindex">
	<link rel="SHORTCUT ICON" href="/faviconT.ico">
	<style type="text/css">

		body {
			background-color: #963;
			color: #030;
		}

		a {
			font-weight: bold;
			text-decoration: none;
		}
		a:link    { color: #9C9; }
		a:visited { color: #9C9; }
		a:hover   { color: #CFC; }
		a:focus   { color: #CFC; }
		a:active  { color: #CFC; }

		#directory_list {
			float: right;
			width: 20%;
			padding: 5px;
		}

		#image_list {
			width: 75%;
			padding: 5px;
		}

		#image_list img {
			padding: 0px;
			border: 1px solid #CFC;
		}

	</style>
</head>
<body>
<?php

if (!isset($_GET["q"]) || strcmp($VALIDATED_GET["display"], "page") == 0)
{
	print_directory_list_header();

	// Default search at top-level directory of image library
	$search_dir = $IMAGE_LIBRARY_PATH;

	if (isset($VALIDATED_GET["path"]))
	{
		$search_dir = $VALIDATED_GET["path"];
		display_thumbnail(dirname($search_dir));
	}

	$image_dir = get_image_directories($search_dir);
	foreach ($image_dir as $next_image_dir)
	{
		display_thumbnail($next_image_dir);
	}

	print_directory_list_footer();
	print_image_list_header();

	$image = get_image_files($search_dir);
	foreach ($image as $next_image)
	{
		display_thumbnail($next_image);
	}

	print_image_list_footer();
}

?>
</body>
</html>

