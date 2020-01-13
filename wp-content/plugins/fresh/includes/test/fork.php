<?php

$pid = pcntl_fork();

if ($pid == -1) {
	MyLog("couldn't not fork", __FILE__);
} else if (! $pid) {
	MyLog("Backgroud started");
	// Child process.
	pcntl_wait($status);
	MyLog("Backgroud ended");

	exit;
}

