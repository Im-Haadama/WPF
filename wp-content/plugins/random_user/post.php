<?php
/*
 * Copyright (c) 2021. Lorem ipsum dolor sit amet, consectetur adipiscing elit.
 * Morbi non lorem porttitor neque feugiat blandit. Ut vitae ipsum eget quam lacinia accumsan.
 * Etiam sed turpis ac ipsum condimentum fringilla. Maecenas magna.
 * Proin dapibus sapien vel ante. Aliquam erat volutpat. Pellentesque sagittis ligula eget metus.
 * Vestibulum commodo. Ut rhoncus gravida arcu.
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ( ! defined( "ABSPATH" ) ) {
	define( 'ABSPATH', dirname(dirname(dirname( dirname( $_SERVER["SCRIPT_FILENAME"])))). '/');
}

require_once(ABSPATH . 'wp-config.php');

function save_result($email, $name, $question)
{
	$title = 'random_user_results';
	$conn =	new mysqli( DB_HOST, DB_USER, DB_PASSWORD, DB_NAME );
	$sql = "select ID from wp_posts where post_title = '" . $title . "'";

	$result = mysqli_query( $conn, $sql );
	if (! $result) {
		print "failed: no connected to db";
		return;
	}
	$row = mysqli_fetch_row( $result );

	if (! $row)
	{
		$my_post = array(
			'post_title' => $title,
			'post_content' => 'Submit results:',
			'post_status' => 'publish',
			'post_author' => 1
		);

		$id = wp_insert_post($my_post);
	} else {
		$id = $row[0];
	}

	if (! $id) {
		print "failed: no result post";
		return;
	}

	$sql = "select post_content from wp_posts where ID = $id";
	$result = mysqli_query( $conn, $sql );
	$row = mysqli_fetch_row( $result );
	$content = $row[0];

	$content .= "<br/>$email $name $question";
	$sql = "update wp_posts set post_content = '" . mysqli_real_escape_string($conn, $content) . "' where ID = $id";
	$result = mysqli_query($conn, $sql);
	if (! $result)
	{
		print "failed: update result post";
	}
}

function GetParam( $key, $mandatory = false, $default = null, $uset = false ) {
	if ( isset( $_GET[ $key ] ) ) {
		$v = $_GET[$key];
		if ($uset) unset($_GET[$key]);
		return $v;
	}

	if ( $mandatory ) {
		die ( "failed: " . debug_backtrace()[1]['function']  . "() key " . $key . " not supplied" );
	} else {
		return $default;
	}
}

if (isset($_GET["operation"]))
{
	$operation = $_GET["operation"];
	if ($operation == "save_random") {
		$email = GetParam("email");
		$name = GetParam("name");
		$question = GetParam("question");
		save_result($email, $name, $question);
		return;
	}
	print "failed: operation $operation not implemented";
}
