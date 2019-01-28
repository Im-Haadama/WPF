<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 08/01/19
 * Time: 09:05
 */

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );
if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( __FILE__ ) ) );
}

require_once( ROOT_DIR . "/niver/data/im_simple_html_dom.php" );
require_once( ROOT_DIR . "/niver/gui/inputs.php" );

// $actual_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

$path = "http://" . $_SERVER['HTTP_HOST'];

$c = array();
array_push( $c, "missions" );
array_push( $c, "tasklist", "task_templates" );
array_push( $c, "suppliers", "business_info" );
array_push( $c, "supplies" );
array_push( $c, "projects" );

foreach ( $c as $obj ) {
	print gui_header( 1, $obj ) . "<br/>";
	print "<B>create one</B><br/>";
	$file = $path . "/niver/coder/create-one.php?config_file=" . ROOT_DIR .
	        "/tools/coder-config/coder-config-" . $obj . ".php";
	print $file . "<br/>";
	$result_text = im_file_get_html( $file );
	print $result_text;

	print "<br/>";
	print "<B>create get all</B><br/>";

	$file = $path . "/niver/coder/create-get-all.php?config_file=" . ROOT_DIR .
	        "/tools/coder-config/coder-config-" . $obj . ".php" .
	        "&obj_name=" . $obj;
	print $file . "<br/>";
	$result_text = im_file_get_html( $file );
	print $result_text;
}
