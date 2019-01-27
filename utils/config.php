<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 27/01/19
 * Time: 18:20
 */

if ( ! defined( "ROOT_DIR" ) ) {
	define( "ROOT_DIR", dirname( dirname( __FILE__ ) ) );
}

require_once( ROOT_DIR . "/niver/fund.php" );
require_once( ROOT_DIR . "/niver/MultiSite.php" );

//const site_id_idx = 0;
//const site_name_idx = 1;
//const site_tools_idx = 2;
//const api_key = 3;

$hosts_to_sync    = array();
$hosts_to_sync[1] = array( 1, "im haadama", "http://store.im-haadama.co.il/tools" );
$hosts_to_sync[3] = array( 3, "fruity", "http://fruity.co.il/tools" );
$hosts_to_sync[4] = array( 4, "super-organi", "http://super-organi.co.il/tools" );

$master = 3;

