<?php
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

define('ABSPATH', dirname(__FILE__) . '/');
require_oncE( "plugins/flavor/includes/core/fund.php" );
require_once( "plugins/flavor/includes/core/data/sql.php" );
require_once( "plugins/flavor/includes/core/core-functions.php" );

//require_once("../im-config.php");

//get_sql_conn(ReconnectDb());

new Focus_Manager();

// print GetContent("https://fruity.co.il/wp-content/plugins/fresh/open_post.php?operation=update_shipping_methods");