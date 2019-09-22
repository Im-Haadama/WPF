<?php

if (! defined('TASKS_DIR'))
	define ('TASKS_DIR', dirname(__FILE__));

if (! defined('STORE_DIR'))
	define ('STORE_DIR', dirname(TASKS_DIR));

if (! function_exists("wp_get_current_user"))
	require_once( STORE_DIR . "/wp-load.php" );
