<?php

$to = 'info@im-haadama.co.il';
$from = 'yaakov.aglamaz@gmail.com';

$headers[] = "MIME-Version: 1.0";
$headers[] = "Content-type: text/html; charset=UTF-8";
$headers[] .= 'To: ' . $to;
$headers[] = "From: " . $from;
$headers[] = "Reply-To: " . $from;
// $headers[] = "Subject: {$subject}";
$headers[] = "X-Mailer: PHP/" . phpversion();
$subject = 'TEST ' . date('y-m-d h:s');
print "subject $subject<br/>";
print "from $from to $to<br/>";
$rc = mail($to, $subject, 'This is a test message', $headers);
var_dump($rc);

