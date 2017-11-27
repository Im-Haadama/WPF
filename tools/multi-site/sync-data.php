<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 02/08/17
 * Time: 20:58
 */

require_once( '../r-shop_manager.php' );
require_once( "../gui/sql_table.php" );
require_once( "../multi-site/multi-site.php" );

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
	case "im_missions":
		$key = "id";
		break;
	default:
		print "bad usage";
		die( 2 );
}

// print header_text(false);
switch ( $operation ) {
	case "get":
		print header_text( false );
		print table_content( "SELECT * FROM $table" );
		break;

	case "update":
		if ( ! isset( $_GET["source"] ) ) {
			print "must define source <br/>";
			die ( 1 );
		}
		$source = $_GET["source"];
		$html   = MultiSite::Execute( "multi-site/sync-data.php?table=$table&operation=get", $source );

		print $html;
		update_table( $html, $table, $key );
}

function update_table( $html, $table, $table_key ) {
	print header_text( false, true, false );
	$dom = str_get_html( $html );
	$row = $dom->find( 'tr' );
	print $row->plaintext;

	$headers = array();
	$fields  = array();
	$first   = true;
	$keys    = array();

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

				$sql = "SELECT COUNT(*) FROM $table WHERE $table_key=" . $row_key;

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
			$sql = "INSERT INTO $table (" . $field_list . ") VALUES ( " . $row_key . ", " . rtrim( $insert_values, ", " ) . ")";
			// print $sql . "<br/>";
			sql_query( $sql );
		} else {
			$sql = "UPDATE $table SET " . rtrim( $update_fields, ", " ) .
			       " WHERE $table_key = " . $row_key;
			// print $sql . "<br/>";
			sql_query( $sql );
		}
	}
	// Delete not recieved keys.
	$min        = sql_query_single_scalar( "SELECT min($table_key) FROM $table" );
	$max        = sql_query_single_scalar( "SELECT max($table_key) FROM $table" );
	$ids        = sql_query_array_scalar( "select $table_key from $table" );
	$for_delete = "";

	for ( $i = $min; $i <= $max; $i ++ ) {
		if ( ! $keys[ $i ] and in_array( $i, $ids ) ) {
			$for_delete .= $i . ", ";
		}
	}
	if ( strlen( $for_delete ) ) {
		$sql = "DELETE FROM $table WHERE $table_key IN (" . rtrim( $for_delete, ", " ) . ")";
		print $sql;

		sql_query( $sql );
	}
}

function quote_text( $num_or_text ) {
	// print "x" . $num_or_text . "y";
	if ( is_numeric( $num_or_text ) ) {
// 		print " number, " ;
		return $num_or_text;
	}

// 	print " text, " ;
	return "'" . $num_or_text . "'";
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