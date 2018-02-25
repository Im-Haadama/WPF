<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 24/02/18
 * Time: 23:16
 */

require_once( "../im_tools.php" );

$user = get_user_id();

if ( $user != 1 ) {
	die( 1 );
}

// staff
remove_role( "staff" );
add_role( "staff", "Worker", array( "working_hours_self" => "true" ) );
$role = get_role( "staff" );

// hr
remove_role( "hr" );
add_role( "hr", "Human Resource Manager", array( "working_hours_all" => "true" ) );

// clerk
remove_role( "clerk" );
add_role( "clerk", "Clerk", array( "edit_shop_orders" => "true" ) );

// business
remove_role( "business" );
add_role( "business", "Business owner", array( "set_client_type" => "true", "pay_supply" => "true" ) );

// finance
remove_role( "finance" );
add_role( "finance", "Finance", array( "set_client_type" => "true", "pay_supply" => "true" ) );

print "done";