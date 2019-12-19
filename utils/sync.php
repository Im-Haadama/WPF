<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 07/01/19
 * Time: 11:26
 */


//const site_id_idx = 0;
//const site_name_idx = 1;
//const site_tools_idx = 2;
//const api_key = 3;

$hosts_to_sync = array(
	array( 1, "im haadama", "http://store.im-haadama.co.il/tools" ),
	array( 3, "fruity", "http://fruity.co.il/tools" ),
	array( 4, "super-organi", "http://super-organi.co.il" )
);

$period = get_param( "period" );

if ( ! $period ) {
	die ( "period should be set" );
}

// Working on behalf of master - fruity.
$m = new Core_MultiSite( $hosts_to_sync, 3, 3 );

foreach ( $hosts_to_sync as $host ) {
	$m->Execute( "fresh/auto/" . $period . ".php", "" );
}