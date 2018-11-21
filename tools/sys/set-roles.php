<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 24/02/18
 * Time: 23:16
 */
//ini_set( 'display_errors', 'on' );
require_once( "../im_tools.php" );
//https://www.cloudways.com/blog/add-custom-user-roles-in-wordpress/

$user = get_user_id();

if ( $user != 1 ) {
	print "EXIT! User id: " . $user . "<br/>";
	die( 1 );
}

if ( 1 ) {
// staff
//	remove_role( "staff" );
	add_role( "staff", "Worker", array( "working_hours_self" => "true" ) );
	$role = get_role( "staff" );

	// hr
//	remove_role( "hr" );
	add_role( "hr", "Human Resource Manager", array( "working_hours_all" => "true" ) );

	// clerk
//	remove_role( "clerk" );
	add_role( "clerk", "Clerk", array( "edit_shop_orders" => "true", "show_supplies" => "true" ) );

	// business
//	remove_role( "business" );
	add_role( "business", "Business owner", array(
		"set_client_type"    => "true",
		"pay_supply"         => "true",
		"show_pricelist"     => "true",
		"edit_pricelist"     => "true",
		"edit_suppliers"     => "true",
		"show_business_info" => "true"
	) );

	// finance
//	remove_role( "finance" );
	add_role( "finance", "Finance", array( "set_client_type" => "true", "pay_supply" => "true" ) );

	add_role( "logistics", "Logistics Manager", array( "edit_missions" => "true" ) );

	add_role( "catalog_editor", "Catalog Editor", array(
		"show_pricelist" => "true",
		"edit_pricelist" => "true"
	) );
	add_role( "logistics", "Logistics Manager", array( "edit_missions" => "true" ) );

}


global $wp_roles;
//$wp_roles->add_cap("logistics", "edit_missions");
//$wp_roles->add_cap( "business", "show_pricelist" );
//$wp_roles->add_cap( "business", "edit_pricelist" );
$wp_roles->add_cap( "business", "edit_suppliers" );
print "done";