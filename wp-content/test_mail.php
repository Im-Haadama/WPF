<?php

define ('ROOT_DIR', dirname(dirname(__FILE__)));
	error_reporting( E_ALL );
	ini_set( 'display_errors', 'on' );

	define('ABSPATH', dirname(dirname(__FILE__)) . '/');

	define ('DEBUG_MAIL', 1);
require_once("../wp-config.php");
require_once ABSPATH . WPINC . '/class-phpmailer.php';
require_once ABSPATH . WPINC . '/class-smtp.php';


global $wp_filter;

print microtime() ."<br/>";
$mail = new PHPMailer( true );
$mail->SMTPDebug = 2;

$mail->Timeout  = 36000;
$mail->Subject = "Registration";
$mail->From = "yaakov.aglamaz@gmail.com";
$mail->FromName = "yaakov";
$mail->AddReplyTo( "yaakov.aglamaz@gmail.com" );
$mail->AddAddress( "yaakov.aglamaz@gmail.com" );
$mail->Body ="lalalalal";
$mail->IsHTML(true);
$mail->Send();
//wp_mail(
//	'yaakov@aglamaz.com',
//	'subject',
//	'Subhject',
//	''
//);
print microtime() ."<br/>";

