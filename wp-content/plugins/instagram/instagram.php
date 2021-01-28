<?php
/**
 * Plugin Name: instagram
 * Plugin URI: https://e-fresh.co.il
 * Description: Instagram crawler
 * Version: 1.0
 * Author: agla
 * Author URI: http://e-fresh.co.il
 * Text Domain: e-fresh
 *
 * @package Instagram
 */

//require_once (ABSPATH . '/vendor/Instagram.php');
//
//use MetzWeb\Instagram\Instagram;
//
//$instagram = new Instagram(array(
//	'apiKey' => 'YOUR_APP_KEY',
//	'apiSecret' => 'YOUR_APP_SECRET',
//	'apiCallback' => 'YOUR_APP_CALLBACK' // must point to success.php
//));


$AppID = "1360571697612487";
$secret = "095913da417ebb02b1516724c5c172ed";

$token = file_get_contents("https://graph.facebook.com/oauth/access_token?client_id=$AppID&client_secret=$secret&grant_type=client_credentials");

"https://developers.facebook.com/docs/instagram-api/guides/hashtag-search/"
//var_dump($token);
