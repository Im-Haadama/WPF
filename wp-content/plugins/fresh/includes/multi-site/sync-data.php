<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 02/08/17
 * Time: 20:58
 */

require_once( '../r-multisite.php' );
require_once( FRESH_INCLUDES . '/core/gui/sql_table.php' );
require_once( "../multi-site/imMulti-site.php" );
require_once( FRESH_INCLUDES . "/init.php" );


$operation = $_GET["operation"];
if ( ! isset( $_GET["table"] ) ) {
	print "bad usage";
	die( 1 );
}
$table = $_GET["table"];
switch ( $table ) {
	case "wp_woocommerce_shipping_zones":
		$key = "zone_id";
		break;
	case "wp_woocommerce_shipping_zone_locations":
//		print "zone_locations<br/>";
		$key = "location_id";
		break;
	case "wp_woocommerce_shipping_zone_methods":
		$key = "instance_id";
		break;
	case "im_missions":
	case "im_baskets":
	case 'im_mission_methods':
		$key = "id";
		break;
	case 'wp_options':
		$key = "options_name";
		if ( ! isset( $_GET["query"] ) ) {
			print "wp_options must be used with query<br/>";
			die( 1 );
		}
		break;

	default:
		print "bad usage";
		die( 2 );
}

// print header_text(false);
switch ( $operation ) {
	case "get":
		print header_text( false );
		$sql = "SELECT * FROM $table";
		if ( isset ( $_GET["query"] ) ) {
			$sql .= " where " . stripcslashes( $_GET["query"] );
		}
		$args["textdomain"] = 'none';
		switch ($table)
		{
			case "wp_woocommerce_shipping_zone_locations":
				$args["id_field"] = "location_id";
				break;
			case "wp_woocommerce_shipping_zone_methods":
			case "wp_woocommerce_shipping_zones":
				$args["id_field"] = "zone_id";
				break;
			case "im_missions":
				break;
			case "wp_options":
				$args["id_field"] = "option_id";
				break;
			default:
				print "table $table not exportable.<br/>";
				die (1);
		}
		disable_translate();
		print GuiTableContent( "table", $sql, $args );
		break;

	case "update":
		if ( ! isset( $_GET["source"] ) ) {
			print "must define source <br/>";
			die ( 1 );
		}
		$source = $_GET["source"];

		ImMultiSite::UpdateFromRemote( $table, $key, $source );
}

function get_decorated_diff( $old, $new ) {
	$from_start = strspn( $old ^ $new, "\0" );
	$from_end   = strspn( strrev( $old ) ^ strrev( $new ), "\0" );

	$old_end = strlen( $old ) - $from_end;
	$new_end = strlen( $new ) - $from_end;

	$start    = substr( $new, 0, $from_start );
	$end      = substr( $new, $new_end );
	$new_diff = substr( $new, $from_start, $new_end - $from_start );
	$old_diff = substr( $old, $from_start, $old_end - $from_start );

	$new = "$start<ins style='background-color:#ccffcc'>$new_diff</ins>$end";
	$old = "$start<del style='background-color:#ffcccc'>$old_diff</del>$end";

	return array( "old" => $old, "new" => $new );
}