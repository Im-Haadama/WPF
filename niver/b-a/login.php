<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 24/01/19
 * Time: 17:05
 */

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );
}

require_once( ROOT_DIR . "/im-config.php" );
require_once( ROOT_DIR . "/niver/data/sql.php" );

require_once( ROOT_DIR . "/niver/User.class.php" );

// Start session
if ( ! session_id() ) {
	session_start();
}

// Include Google API client library
require_once 'google-api-php-client/Google_Client.php';
require_once 'google-api-php-client/contrib/Google_Oauth2Service.php';

// Call Google API
$gClient = new Google_Client();
$gClient->setApplicationName( 'Login to CodexWorld.com' );
$gClient->setClientId( GOOGLE_CLIENT_ID );
$gClient->setClientSecret( GOOGLE_CLIENT_SECRET );
$gClient->setRedirectUri( GOOGLE_REDIRECT_URL );

$google_oauthV2 = new Google_Oauth2Service( $gClient );