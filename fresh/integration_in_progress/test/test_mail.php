<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 26/01/17
 * Time: 16:23
 */



require_once( "../r-shop_manager.php" );
require_once( "../mail.php" );

$subject = random_str(10);
// $to = "yaakov.aglamaz@gmail.com";
$to = "yaakov.aglamaz@gmail.com,info@fruity.co.il";

$headers   = array();
$headers[] = "MIME-Version: 1.0";
//	$headers[] = "From: עם האדמה <info@im-haadama.co.il>";
//	$headers[] = "Reply-To: Im Haadama <info@im-haadama.co.il>";
$headers[] = "From: info@fruity.co.il";
$headers[] = "Subject: " . $subject;
$headers[] = "X-Mailer: PHP/" . phpversion();
$headers[] = "Content-type: text/html";

$message= "abnvd";

$rc = mail( $to, "testing", $message, implode( "\r\n", $headers ) );

print "sent " . "subject= "  .$subject . " " . strlen($message) . " to " . $to . " rc = " . $rc;

// $rc = send_mail( "mail-test", "yaakov.aglamaz@gmail.com", "test message" );

//require_once("delivery.php");
//$d = new delivery(1632);
//$d->send

