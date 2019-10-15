<?php

require_once("focus.php");

$operation = get_param("operation", false, null);

if ($operation){
	focus_init();

	 handle_focus_operation($operation);
	return;
}

