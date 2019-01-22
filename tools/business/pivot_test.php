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

require_once( ROOT_DIR . '/niver/PivotTable.php' );

$t = new \Niver\PivotTable( "im_business_info", "EXTRACT(YEAR FROM DATE)",
	"part_id", "EXTRACT(MONTH FROM DATE)", "net_amount" );

print gui_table( $t->Create() );