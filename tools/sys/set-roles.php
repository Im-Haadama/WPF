<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 24/02/18
 * Time: 23:16
 */
//ini_set( 'display_errors', 'on' );
require_once( "../im_tools.php" );
require_once( ROOT_DIR . '/wp-includes/capabilities.php' );
//https://www.cloudways.com/blog/add-custom-user-roles-in-wordpress/

$user = get_user_id();

if ( $user != 1 ) {
	print "EXIT! User id: " . $user . "<br/>";
	die( 1 );
}

handle_role( "business", array(
	"set_client_type",
	"pay_supply",
	"show_pricelist",
	"edit_pricelist",
	"edit_suppliers",
	"show_business_info",
	"show_bank",
	"show_salary"
) );

die ( 0 );
global $wp_roles;

// staff
//	remove_role( "staff" );
$wp_roles->add_role( "staff", "Worker", array( "working_hours_self" => "true" ) );
	$role = get_role( "staff" );

	// hr
//	remove_role( "hr" );
$wp_roles->add_role( "hr", "Human Resource Manager", array( "working_hours_all" => "true" ) );

	// clerk
//	remove_role( "clerk" );
$wp_roles->add_role( "clerk", "Clerk", array( "edit_shop_orders" => "true", "show_supplies" => "true" ) );

	// business
//	remove_role( "business" );
$result = $wp_roles->add_role( "business", "Business owner", array(
	"set_client_type"    => "true",
	"pay_supply"         => "true",
	"show_pricelist"     => "true",
	"edit_pricelist"     => "true",
	"edit_suppliers"     => "true",
	"show_business_info" => "true",
	"show_bank"          => "true"
	) );

var_dump( $result );

	// finance
//	remove_role( "finance" );
$wp_roles->add_role( "finance", "Finance", array( "set_client_type" => "true", "pay_supply" => "true" ) );

$wp_roles->add_role( "logistics", "Logistics Manager", array( "edit_missions" => "true" ) );

$wp_roles->add_role( "catalog_editor", "Catalog Editor", array(
		"show_pricelist" => "true",
		"edit_pricelist" => "true"
	) );
$wp_roles->add_role( "logistics", "Logistics Manager", array( "edit_missions" => "true" ) );

$wp_roles->add_role( "packing_manager", "Packing Manager", array( "manage_packing" => "true" ) );

//var_dump($wp_roles);

$role = $wp_roles->get_role( "business" );
var_dump( $role );

// print "XX";


//$wp_roles->add_cap("logistics", "edit_missions");
//$wp_roles->add_cap( "business", "show_pricelist" );
//$wp_roles->add_cap( "business", "edit_pricelist" );
$wp_roles->add_cap( "business", "edit_suppliers" );

$wp_roles->add_cap( "packing_manager", "manage_packing" );

print "done";

function handle_role( $role, $capabilities ) {
	global $wp_roles;

	$wp_roles or die ( "no wp_roles" );

	$wp_role = $wp_roles->get_role( $role );

	if ( ! $wp_role ) {
		print "role $role is new. Adding";
		die ( "not implemented" );
	} else {
		print "role $role exists. Checking capabilies";
	}
	foreach ( $capabilities as $cap ) {
		print $cap . $wp_roles->add_cap( $role, $cap ) . "<br/>";
	}
	$wp_role = $wp_roles->get_role( $role );
	var_dump( $wp_role );
}