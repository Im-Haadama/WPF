<?php
if ( isset( $display_logo ) ) {
	$display_logo = true;
}
// if (isset($_GET["no_logo"])) $display_logo = false;
require_once( 'tools.php' );
print_page_header( $display_logo );
require_once "tools_wp_login.php";
// echo 'ברוך הבא ' . get_current_user_name() . '!';


