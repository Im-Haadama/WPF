<?php

$pid = pcntl_fork();

if ($pid == -1) {
	my_log("couldn't not fork", __FILE__);
} else if (! $pid) {
	my_log("Backgroud started");
	// Child process.
	pcntl_wait($status);
	my_log("Backgroud ended");

	exit;
}

