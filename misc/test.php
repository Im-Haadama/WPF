<?php

require_once("wp-config.php");

//wp_clear_scheduled_hook('fresh_cron_hook');

// require "wp-content/plugins/finance/includes/core/gui/gem.php";

//wp_
 if ( ! wp_next_scheduled( 'fresh_cron_hook' ) ) {
	print "adding";
	$cron_args = [];
//	print strtotime('16:00');
	$result = wp_schedule_event( strtotime('16:00'), 'daily', 'fresh_cron_hook', $cron_args );
//	var_dump($result);
 }

function schedule_event( $timestamp, $recurrence, $hook, $args = array())
{
	if ( ! is_numeric( $timestamp ) || $timestamp <= 0 ) {
		die("bad timestamp " . $timestamp);
	}

	$schedules = wp_get_schedules();

	if ( ! isset( $schedules[ $recurrence ] ) ) {
		return false;
	}

	$event = (object) array(
		'hook'      => $hook,
		'timestamp' => $timestamp,
		'schedule'  => $recurrence,
		'args'      => $args,
		'interval'  => $schedules[ $recurrence ]['interval'],
	);

	/** This filter is documented in wp-includes/cron.php */
	$pre = apply_filters( 'pre_schedule_event', null, $event );
	if ( null !== $pre ) {
		die ("pre");
		return $pre;
	}

	/** This filter is documented in wp-includes/cron.php */
	$event = apply_filters( 'schedule_event', $event );

	// A plugin disallowed this event
	if ( ! $event ) {
		die ("event");
		return false;
	}

	$key = md5( serialize( $event->args ) );

	$crons = _get_cron_array();
	$crons[ $event->timestamp ][ $event->hook ][ $key ] = array(
		'schedule' => $event->schedule,
		'args'     => $event->args,
		'interval' => $event->interval,
	);
	uksort( $crons, 'strnatcasecmp' );
	return _set_cron_array( $crons );
}

//$args["query"] = "option_name like '%cron%'";
//$args["id_field"] = "option_id";

$table = sql_query_single_scalar("select option_value from wp_options where option_name = 'cron'");

if ($table) {
	$rows = unserialize($table);
	foreach ($rows as $row) {
		if ( is_array($row) ) {
			foreach ( $row as $key => $jobs ) {
				print $key . " ";

				foreach ( $jobs as $action ) {
					print $action['schedule'] . " " . $action['interval'] . " " . date('y-m-d H:i', wp_next_scheduled($key)) . "<br/>";
				}

			}
		} else {
			var_dump( $row );
		}
	}
	// print GemArray($rows, $args, "cron");
}

