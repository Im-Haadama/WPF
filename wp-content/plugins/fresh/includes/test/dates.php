<?php

$last_run = '2019-09-01';

$check_date = strtotime($last_run . ' +1 day');
$now = strtotime(date('y-m-d'));

print "now: " .date('y-m-d', $now) . "<br/>";
while ($check_date <= $now){
	print "checking: " . date('y-m-d', $check_date) . "<br/>";
	$check_date += 86400;
}
