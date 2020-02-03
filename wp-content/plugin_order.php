<?php

	error_reporting( E_ALL );
	ini_set( 'display_errors', 'on' );

	define('ABSPATH', dirname(__FILE__) . '/');
require_oncE("plugins/flavor/includes/core/fund.php");
require_once("plugins/flavor/includes/core/data/sql.php");
require_once("plugins/flavor/includes/core/core-functions.php");

require_once("../im-config.php");

get_sql_conn(ReconnectDb());
$plugins_s = sql_query_single_scalar("select option_value from wp_options where option_name='active_plugins'");
$plugins = unserialize($plugins_s);
show_order($plugins);
if (! isset($_GET["change"])) {
	print '<a href="' . AddToUrl("change", 1) . '">Change</a>';
	return;
}
$c = in_array("flavor/flavor.php", $plugins);
if ($c) {
	print "found. changing order<br/>";
	unset( $plugins[ $c ] );
	array_unshift( $plugins, "flavor/flavor.php" );
	$sql = "update wp_options set option_value = " .
	       QuoteText(serialize($plugins)) . " where option_name='active_plugins'";
//	print $sql;
	sql_query($sql);
	print "done";
	show_order($plugins);
}

function show_order($plugins)
{
	print "current order:<br/>";
	foreach ($plugins as $plugin) print $plugin . "<br/>";
	return;
}