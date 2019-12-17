<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 26/05/17
 * Time: 13:07
 */

if ( ! defined( 'FRESH_INCLUDES' ) ) {
	define( 'FRESH_INCLUDES', dirname(dirname( dirname( __FILE__ ) ) ));
}

require_once(FRESH_INCLUDES . '/im-config.php');
require_once( FRESH_INCLUDES . '/core/gui/inputs.php' );
$edit = get_param("edit", false, false);

print header_text(true, true, true, array("/org/people/people.js", "/core/gui/client_tools.js"));
$user_id = get_user_id(true);

if (! user_can($user_id, 'working_hours_all')) {
    print im_translate("No permissions");
	return;
}

$month = get_param("month", false, date( 'Y-m', strtotime( 'last month' ) ));

$args["show_salary"] = true;
$args["edit_lines"] = $edit;
print show_all($month,$args);
print GuiHyperlink("Previous month", add_to_url("month", date('Y-m', strtotime($month . '-1 -1 month'))));
print footer_text();
