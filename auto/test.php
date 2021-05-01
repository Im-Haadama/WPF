<?php

$user = 'info@im-haadama.co.il';
$password = '3986781';
$url = 'https://app.pepperi.com/HomePage';

$curl = curl_init();
// Define which url you want to access
curl_setopt($curl, CURLOPT_URL, $url);

// Add authorization header
curl_setopt($curl, CURLOPT_USERPWD, $user . ':' . $password);

// Allow curl to negotiate auth method (may be required, depending on server)
curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);

// Get response and possible errors
$response = curl_exec($curl);
$error = curl_error($curl);
curl_close($curl);

//print $response;
//print $error;
// Save filse
//$file = fopen('/path/to/file.zip', "w+");
//fputs($file, $reponse);
//fclose($file);