<?php

die (1); // use flavor::getPost();

if ( ! defined( "ABSPATH" ) ) {
	define( 'ABSPATH', dirname(dirname(dirname( dirname( __FILE__ ) ) )) . '/');
}

require_once(ABSPATH . 'wp-config.php');

$operation = GetParam('operation', true);
$focus = Focus::instance();

if ( ! get_user_id(true) ) die('Not connected');

$rc = $focus->handle_operation($operation);

if ($rc === false) { print "failed"; return; }
if ($rc === true) { print "done"; return; }

print $rc;