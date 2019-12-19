<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 10/01/19
 * Time: 01:04
 */

if ( ! defined( "ROOT_DIR" ) ) {
	define( "ROOT_DIR", dirname( dirname( __FILE__ ) ) );
}

require_once( ROOT_DIR . "/agla/fund.php" );
require_once( ROOT_DIR . "/agla/MultiSite.php" );

//const site_id_idx = 0;
//const site_name_idx = 1;
//const site_tools_idx = 2;
//const api_key = 3;

$freq = get_param( "freq" );

$hosts_to_sync = array();

$hosts_to_sync[1] = array(
	1,
	"im haadama",
	"http://store.im-haadama.co.il/tools",
	"7919d78f-61fd-4248-ad69-0d17036f1e65"
);
$hosts_to_sync[3] = array( 3, "fruity", "http://fruity.co.il/tools", "XX", "YY" );
$hosts_to_sync[4] = array( 4, "super-organi", "http://super-organi.co.il" );


// Working on behalf of master - fruity.
$m = new Core_MultiSite( $hosts_to_sync, 3, 3 );

$m->Execute( "tasklist/create.php?freq=" . $freq, 1 );
