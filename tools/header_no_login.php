<?php
if ( ! defined( STORE_DIR ) ) {
	define( 'STORE_DIR', dirname( __FILE__ ) );
}
require_once( STORE_DIR . '/im-config.php' );
?>
<html dir="rtl">
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <title><?php print $business_name; ?></title>
    <center><img src="<?php print $logo_url; ?> "></center>
</head>
<?php
// require_once "r-shop_manager.php";
// echo 'ברוך הבא ' . get_current_user_name() . '!';
?>