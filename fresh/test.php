<?php

require (dirname(dirname(__FILE__)) . '/wp-config.php');
//$staging = 'https://preprod.paymeservice.com/api/';
//$production = 'https://ng.paymeservice.com/api/';
//
//$req_fields;
//
//$reg = $staging . json_encode($req_fields);
//$data = json_decode(file_get_contents($reg), true);
//
//var_dump($data);

$i = new Finance_Invoice4u('yaakov@im-haadama.co.il', 'fruitstoall');
print $i->GetInvoiceUserId(774, 'ramabarouch@gmail.com');