<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 18/08/17
 * Time: 05:05
 *
 * @param $subject
 * @param $to
 * @param $message
 *
 * @return bool
 */

function send_mail( $subject, $to, $message ) {
	global $mail_sender;
	global $support_email;

	$headers   = array();
	$headers[] = "MIME-Version: 1.0";
	$headers[] = "Content-type: text/html; charset=UTF-8";
	$headers[] .= 'To: ' . $to;
	$headers[] = "From: " . $mail_sender;
	$headers[] = "Reply-To: " . $support_email;
	// $headers[] = "Subject: {$subject}";
	$headers[] = "X-Mailer: PHP/" . phpversion();

	print "sending from " . $support_email . " to: " . $to . '<br/>';

	$base64_subject = '=?UTF-8?B?'.base64_encode($subject).'?=';

	return mail( $to, $base64_subject, $message, implode( "\r\n", $headers ) );
}
