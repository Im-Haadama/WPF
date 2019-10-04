<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 02/08/17
 * Time: 20:58
 */

require_once( '../r-multisite.php' );
require_once( ROOT_DIR . '/niver/gui/sql_table.php' );
require_once( "../multi-site/imMulti-site.php" );

$operation = $_GET["operation"];

// print header_text(false);
switch ( $operation ) {
	case "get":
		print header_text( false );
		print table_content( "table", "SELECT zone_id, zone_name, zone_order, min_order, zone_delivery_order, delivery_days FROM wp_woocommerce_shipping_zones" );
		break;

	case "update":
		if ( ! isset( $_GET["source"] ) ) {
			print "must define source <br/>";
			die ( 1 );
		}
		$source = $_GET["source"];
		$html   = ImMultiSite::sExecute( "multi-site/sync-zones.php?operation=get", $source );

		print $html;
		update_zone_table( $html );
		break;

	case "get_shipping":
		print header_text( false );
		print table_content( "table", "SELECT zone_id, instance_id, method_id, method_order, is_enabled FROM wp_woocommerce_shipping_zone_methods" );
		break;

}

function update_zone_table( $table ) {
	print header_text( false, true, false );

	$dom = im_str_get_html( $table );
	$row = $dom->find( 'tr' );
	print $row->plaintext;

	$headers = array();

	$fields = array();
	$first  = true;
	$keys   = array();

	$row_key = - 1;
	foreach ( $dom->find( 'tr' ) as $row ) {
		// First line - headers.
		if ( $first ) {
			foreach ( $row->children() as $key ) {
				array_push( $headers, $key->plaintext );
			}
			// unset($headers[0]);
			$field_list = comma_implode( $headers );
			print "headers: " . $field_list . "<br/>";
			$first = false;
			continue;
		}
		$first_key     = true;
		$update_fields = "";
		$i             = 0;
		$insert        = false;

		foreach ( $row->children() as $value ) {
			$fields[ $i ] = $value->plaintext;

			// First key: id
			if ( $first_key ) {
				$row_key          = intval( $fields[0] );
				$keys[ $row_key ] = 1;
				$insert_values    = "";

				print "<br/>handle " . $row_key . " ";

				$sql = "SELECT COUNT(*) FROM wp_woocommerce_shipping_zones WHERE zone_id=" . $row_key;

				if ( sql_query_single_scalar( $sql ) < 1 ) {
					print " insert ";
					$insert = true;
				} else {
					print " update ";
				}
				$first_key = false;
				$i ++;
				continue;
			}
			if ( $insert ) {
				$insert_values .= quote_text( $fields[ $i ] ) . ", ";
			} else { // Update
				$update_fields .= $headers[ $i ] . "=" . quote_text( $fields[ $i ] ) . ", ";
			}
			$i ++;
		}

		if ( $insert ) {
			$sql = "INSERT INTO wp_woocommerce_shipping_zones (" . $field_list . ") VALUES ( " . $row_key . ", " . rtrim( $insert_values, ", " ) . ")";
			// print $sql . "<br/>";
			sql_query( $sql );
		} else {
			$sql = "UPDATE wp_woocommerce_shipping_zones SET " . rtrim( $update_fields, ", " ) .
			       " WHERE zone_id = " . $row_key;
			// print $sql . "<br/>";
			sql_query( $sql );
		}
	}
	// Delete not recieved keys.
	$min        = sql_query_single_scalar( "SELECT min(zone_id) FROM wp_woocommerce_shipping_zones" );
	$max        = sql_query_single_scalar( "SELECT max(zone_id) FROM wp_woocommerce_shipping_zones" );
	$ids        = sql_query_array_scalar( "select zone_id from wp_woocommerce_shipping_zones" );
	$for_delete = "";

	for ( $i = $min; $i <= $max; $i ++ ) {
		if ( ! $keys[ $i ] and in_array( $i, $ids ) ) {
			$for_delete .= $i . ", ";
		}
	}
	if ( strlen( $for_delete ) ) {
		$sql = "DELETE FROM wp_woocommerce_shipping_zones WHERE zone_id IN (" . rtrim( $for_delete, ", " ) . ")";
		print $sql;

		sql_query( $sql );
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