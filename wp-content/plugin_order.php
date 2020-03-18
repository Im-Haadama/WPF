<?php

	error_reporting( E_ALL );
	ini_set( 'display_errors', 'on' );

	define('ABSPATH', dirname(__FILE__) . '/');
require_once("../im-config.php");
require_once("plugins/flavor/includes/core/fund.php");
require_once("plugins/flavor/includes/core/data/sql.php");
require_once("plugins/flavor/includes/core/core-functions.php");

get_sql_conn(ReconnectDb());
$plugins_s = sql_query_single_scalar("select option_value from wp_options where option_name='active_plugins'");
$plugins = unserialize($plugins_s);
show_order($plugins);
if (! isset($_GET["change"])) {
	print '<a href="' . AddToUrl("change", 1) . '">Change</a>';
	return;
}

$p_i = array();
foreach ($plugins as $plugin)
{
	$pri = 5;
//	print substr($plugin, 0, 5) . "<br/>";
	switch (substr($plugin, 0, 5))
	{
		case "wooco":
			$pri = 1;
			break;
		case "flavo":
			$pri = 2;
			break;
	}
	array_push($p_i, array($pri, $plugin));
}
usort($p_i, function($a, $b) { return $a[0] > $b[0]; });
$plugins = [];
foreach ($p_i as $p)
	if (! in_array($p[1], $plugins))
		array_push($plugins, $p[1]);
print "changing order<br/>";
$sql = "update wp_options set option_value = " .
       QuoteText(serialize($plugins)) . " where option_name='active_plugins'";
//	print $sql;
sql_query($sql);
print "done";
show_order($plugins);

function show_order($plugins)
{
	print "current order:<br/>";
	foreach ($plugins as $plugin) print $plugin . "<br/>";
	return;
}