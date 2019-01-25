<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 24/01/19
 * Time: 17:26
 */

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( __FILE__ ) ) );
}

require_once( ROOT_DIR . "/im-config.php" );
require_once( ROOT_DIR . "/niver/data/sql.php" );
$conn = new mysqli( IM_DB_HOST, IM_DB_NAME, IM_DB_PASSWORD, IM_DB_NAME );
mysqli_set_charset( $conn, 'utf8' );


//sql_query("
//CREATE TABLE `niv_users` (
//`id` int(11) NOT NULL AUTO_INCREMENT,
// `oauth_provider` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
// `oauth_uid` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
// `first_name` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
// `last_name` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
// `email` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
// `gender` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
// `locale` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
// `picture` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
// `link` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
// `created` datetime NOT NULL,
// `modified` datetime NOT NULL,
// PRIMARY KEY (`id`)
//) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");

print "done";
