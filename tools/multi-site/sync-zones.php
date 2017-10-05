<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 02/08/17
 * Time: 20:58
 */

require_once( '../tools_wp_login.php' );
require_once( "../gui/sql_table.php" );
require_once( "../multi-site/multi-site.php" );

$operation = $_GET["operation"];

// print header_text(false);
switch ( $operation ) {
	case "get":
		print table_content( "SELECT zone_id, zone_name, zone_order, min_order, zone_delivery_order, delivery_days FROM wp_woocommerce_shipping_zones" );
		break;

	case "update":
		$source = $_GET["source"];
		$html   = MultiSite::Execute( "multi-site/sync-zones.php?operation=get", $source );

		// print $html;
		update_zone_table( $html );
}

function update_zone_table( $table ) {
	global $conn;
	$dom = str_get_html( $table );
	$row = $dom->find( 'tr' );
	print $row->plaintext;

	$headers = array();

	$fields = array();
	$first  = true;
	foreach ( $dom->find( 'tr' ) as $row ) {
		if ( $first ) {
			foreach ( $row->children() as $key ) {
				array_push( $headers, $key );
			}
			$first = false;
			continue;
		}
		$first_key     = true;
		$insert_fields = "(";
		$insert_values = "VALUES (";
		$update_fields = "";
		$i             = 0;
		foreach ( $row->children() as $value ) {
			if ( $first_key ) {
				$row_key = intval( $value );
				print $value . " " . $row_key . "<br/>";
				$sql = "SELECT COUNT(*) FROM wp_woocommerce_shipping_zones WHERE zone_id=" . $row_key;

				if ( sql_query_single_scalar( $sql ) < 1 ) {
					$insert = true;
				} else {
					$insert = false;
				}
				$first_key = false;
				continue;
			}
			if ( $insert ) {
				if ( is_numeric( $value ) ) {
					$insert_values .= $value . ", ";
				} else {
					$insert_values .= "'" . $value . "', ";
				}
			} else { // Update
				if ( is_numeric( $value ) ) {
					$update_fields .= $headers[ $i ] . "=" . $fields[ $i ] . ", ";
				} else {
					$update_fields .= $headers[ $i ] . "='" . $fields[ $i ] . "', ";
				}
			}
			$i ++;

		}

		print "<br/>handle " . $row_key;

		if ( $insert ) {
			print " insert ";
			$sql = "INSERT in wp_woocommerce_shipping_zones " . rtrim( $insert_fields, ", " ) . rtrim( $insert_values, ", " );
			print $sql;
//			mysqli_query($conn, $sql);

//			if (mysqli_affected_rows($conn) < 1){
//				print "Error! " . $sql;
//				die(1);
//		 }
		} else {
			print " update ";
			$sql = "UPDATE wp_woocommerce_shipping_zones SET " . rtrim( $update_fields, ", " ) .
			       " WHERE zone_id = " . $row_key;
			print $sql . "<br/>";
//			if (! mysqli_query($conn, $sql)) sql_error($sql);
//			if (mysqli_affected_rows($conn) < 1) {
//				print "Error! " . $sql;
//				die( 1 );
//			}
		}
	}
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