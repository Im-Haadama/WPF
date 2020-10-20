<?php

/**
 * Plugin Name: Earth
 * Description:  earth and spirit plugin
 * Version: 1.0
 * Author: agla
 * Author URI: http://aglamaz.com
 * Text Domain: wpf
 *
 * @package Fresh
 */

add_shortcode("earth_sub", 'earth_sub');

function earth_sub()
{
	if (! is_user_logged_in()) {
		print "יש להתחבר כדי לראות את התוכן";
		wp_die();
	}
	return "aaa";
}
