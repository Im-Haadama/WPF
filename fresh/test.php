<?php

//$staging = 'https://preprod.paymeservice.com/api/';
//$production = 'https://ng.paymeservice.com/api/';
//
//$req_fields;
//
//$reg = $staging . json_encode($req_fields);
//$data = json_decode(file_get_contents($reg), true);
//
//var_dump($data);

$stage = 'https://preprod.paymeservice.com/api';

$seller_type = '1104';
$seller_social_id = '9999999999';
$seller_email = 'random@paymeservice.com';
$seller_bank_code = '54';
$seller_bank_brach = '333';
$seller_bank_account_number = '534124';
$first_name = 'יעקב';
$last_name = 'אגלמז ערב';

$client_key = 'XXXXXXXX';

$request = array('payme_client_key' => $client_key,
	'seller_type' => $seller_type,
	'seller_first_name' => $first_name,
	'seller_last_name' => $last_name,
	'seller_social_id' => $seller_social_id,
	'seller_birthdate' => '04/04/1970',
	'seller_social_id_issued' => '02/11/2011',
	'seller_gender' => 0,
	'seller_email'=> $seller_email,
	'seller_phone' => '054-3986781',
	'seller_bank_code' => 14,
	'seller_bank_brach' => 382,
	'seller_bank_account_number' => '123456',
	'seller_description' => 'Test seller',
	'seller_site_url' => 'fruity.co.il',
	'seller_person_business_type' => $seller_type

	);

$ch = curl_init($stage . '/create-seller');

$request_payload = json_encode($request);

// Attach encoded JSON string to the POST fields
curl_setopt($ch, CURLOPT_POSTFIELDS, $request_payload);

// Set the content type to application/json
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

// Return response instead of outputting
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Execute the POST request
$result = curl_exec($ch);

// Close cURL resource
curl_close($ch);

print $result;