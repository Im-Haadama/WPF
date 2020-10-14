<?php

/**
 * Plugin Name: Communities
 * Description:  communities
 * Version: 1.0
 * Author: agla
 * Author URI: https://e-fresh.co.il
 * Text Domain:
 *
 * @package communities
 */

if (! class_exists("Flavor"))
	add_action('admin_notices', 'communities_admin_notice');
//	die("Flavor should be active");

function communities_admin_notice(){
	require_once("plugin_order.php");
		echo '<div class="notice notice-warning is-dismissible">
             <p>Flavor should be active before communities.</p> ';
		show_order();
         echo '</div>';
}
