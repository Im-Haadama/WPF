<?php

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname(dirname( dirname( __FILE__ ) ) ));
}
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

require_once( ROOT_DIR . '/im-config.php' );
require_once( ROOT_DIR . "/init.php" );

require_once (ROOT_DIR . '/niver/fund.php');

$id = get_param("id", false, null);

if ($id)
{

}