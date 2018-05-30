<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 18/08/17
 * Time: 05:05
 */

function send_mail( $subject, $to, $message ) {
	global $mail_sender;
	global $support_email;

//    print "start send";
//    print $subject ."<br/>";
//    print $to . "<br/>";
//    print $message . "<br/>";
	$headers   = array();
	$headers[] = "MIME-Version: 1.0";
//	$headers[] = "From: עם האדמה <info@im-haadama.co.il>";
//	$headers[] = "Reply-To: Im Haadama <info@im-haadama.co.il>";
	$headers[] = "From: " . $support_email;
	$headers[] = "Reply-To: " . $support_email;
	$headers[] = "Subject: {$subject}";
	$headers[] = "X-Mailer: PHP/" . phpversion();
	$headers[] = "Content-type: text/html";

	$rc = mail( $to, $subject, $message, implode( "\r\n", $headers ) );
	print "sent. RC = " . $rc . "<br/>";
//    print "to = " . $to. "<br/>";
//    print "subject = " . $subject. "<br/>";
	return $rc;
}
