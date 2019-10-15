<?php

require_once("data.php");

$operation = get_param("operation", false, null);

if ($operation){
	require_once(ROOT_DIR . '/im-config.php');
	require_once(ROOT_DIR . '/init.php');

	handle_data_operation($operation);
	return;
}

