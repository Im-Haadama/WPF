<?php

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( __FILE__ ) ) ) ;
}

require_once(ROOT_DIR . '/im-config.php');
require_once( ROOT_DIR . "/init.php" );
require_once(ROOT_DIR . "/focus/gui.php");
require_once(ROOT_DIR . "/org/gui.php");


$user_id = get_user_id();

print comma_implode(team_all_teams($user_id));
die (1);

$teams_string = get_usermeta($user_id, 'teams');
print "ts= " . $teams_string . "<br/>";

if (! $teams_string) return null;
$teams_string = str_replace("::", ":", $teams_string);
print "ts= " . $teams_string . "<br/>";
$teams = array();
while(strlen($teams_string) > 1) {
//		print $teams_string . "<br/>";
	$p = strpos($teams_string, ":", 1);
	$team = substr($teams_string, 1, $p - 1);
	$t[] = $team;
//		print "p=$p<br/>";
	if ($team > 0) array_push($teams, $team);
	$teams_string = substr($teams_string, $p);
}

var_dump($teams);
return $teams;
