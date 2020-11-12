<?php

$to = 'yaakov.aglamaz@gmail.com';
$from = 'info@hb-swimwear.com';

$headers[] = "MIME-Version: 1.0";
$headers[] = "Content-type: text/html; charset=UTF-8";
$headers[] .= 'To: ' . $to;
$headers[] = "From: " . $from;
$headers[] = "Reply-To: " . $from;
// $headers[] = "Subject: {$subject}";
$headers[] = "X-Mailer: PHP/" . phpversion();
$subject = 'TEST ' . date('y-m-d h:s');
print "subject $subject<br/>";
$rc = mail($to, $subject, 'This is a test message', $headers);
var_dump($rc);

