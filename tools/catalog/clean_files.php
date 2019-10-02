<?php
return;
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

define('ROOT_DIR', dirname(dirname(dirname(__FILE__))));
require_once("../../niver/gui/inputs.php");

$result = array();
$dir = '/home/fruityco/public_html/wp-content/uploads/2018';
$target = '/home/fruityco/public_html/wp-content/uploads/non8';
//$dir = '/home/fruityco/public-html';

if (! file_exists($dir))
{
	die ("dir not found");
}

getDirContents($dir, $result);

$handle = "<table>";
$not_handle = "<table>";
foreach ($result as $file)
{
	$tresh = strtotime("1-8-2019"); // 1561856309;
	$date = filemtime ($file);
	if ($date < $tresh) {
		$handle .= "<tr>";
		$handle .= "<td>$file</td><td> " . date ("F d Y H:i:s.", $date) . "</td>";
		$handle .= "</tr>";
		$dest_file = $target . substr($file, strlen($dir));
//		$dest_dir = dirname($dest_file);
//		print "target: $dest_dir<br/>";
		if (!is_dir(dirname($dest_file))) mkdir (dirname($dest_file), 0777, true);
		rename ($file, $dest_file);
		//print "moving $file to $target". "$file_name<br/>";

	} else {
		$not_handle .= "<tr>";
		$not_handle .= "<td>$file</td><td> " . date ("F d Y H:i:s.", $date) . "</td>";
		$not_handle .= "</tr>";
	}
}
$handle .= "</table>";
$not_handle .= "</table>";

print gui_header(1, "handle");
print $handle;

print gui_header(1, "not handle");
print $not_handle;

function getDirContents($dir, &$results = array()){
	$files = scandir($dir);

	foreach($files as $key => $value){
		$path = realpath($dir.DIRECTORY_SEPARATOR.$value);
		if(!is_dir($path)) {
			$results[] = $path;
		} else if($value != "." && $value != "..") {
			getDirContents($path, $results);
			$results[] = $path;
		}
	}

	return $results;
}