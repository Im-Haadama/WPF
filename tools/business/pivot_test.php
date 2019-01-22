<?php

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 22/01/19
 * Time: 16:44
 */

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );
}

error_reporting( E_ALL );
ini_set( 'display_errors', 1 );

require_once( ROOT_DIR . '/niver/PivotTable.php' );
require_once( ROOT_DIR . '/niver/gui/inputs.php' );
require_once( ROOT_DIR . '/tools/im_tools_light.php' );

$t = new \Niver\PivotTable( "im_business_info", "EXTRACT(YEAR FROM DATE) = " . 2019 . " and document_type = 4",
	"client_displayname(part_id)", "EXTRACT(MONTH FROM DATE)", "net_total" );

print gui_table( $t->Create() );