<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 24/01/19
 * Time: 17:05
 */

if ( ! defined( "FRESH_INCLUDES" ) ) {
	define( 'FRESH_INCLUDES', dirname( dirname( dirname( __FILE__ ) ) ) );
}

require_once( FRESH_INCLUDES . "/im-config.php" );
require_once( FRESH_INCLUDES . "/core/data/sql.php" );

require_once( FRESH_INCLUDES . "/core/User.class.php" );

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