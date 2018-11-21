<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 02/02/17
 * Time: 18:13
 */

require_once( 'im_simple_html_dom.php' );

if ( ! defined( "TOOLS_DIR" ) ) {
	define( "TOOLS_DIR", dirname( dirname( __FILE__ ) ) );
}

require_once( ROOT_DIR . '/agla/gui/sql_table.php' );

$local_site_id = - 1;

class MultiSite {
	static function map( $remote_site_id, $local_prod_id, $remote_prod_id ) {
		my_log( __FILE__, __METHOD__ );
		global $conn;
		$sql = "INSERT INTO im_multisite_map (remote_site_id, local_prod_id, remote_prod_id) " .
		       " VALUES (" . $remote_site_id . ", " . $local_prod_id . ", " . $remote_prod_id . ")";

		my_log( $sql );

		$result = mysqli_query( $conn, $sql );
		if ( ! $result ) {
			sql_error( $sql );
			die( 1 );
		}
	}

	static function CopyImage( $id, $remote_id, $remote_site ) {
		$req = "multi-site/secondary-send-pictures.php?ids=" . $id . "," . $remote_id;

		$info = MultiSite::Execute( $req, $remote_site );

		foreach ( preg_split( "/<br\/>/", $info ) as $line ) {
			if ( strlen( $line ) > 2 ) {
				$data = preg_split( "/,/", $line );
				// $img_file = preg_split(",", $line);
				$id   = $data[0];
				$path = $data[1];
				// print "id = " . $id . " img_file = " . $path . "<br/>";

				update_post_meta( $id, 'fifu_image_url', $path );
			}
		}
	}

	static function Execute( $request, $site ) {
		// print "req: " . $request . " site = " . $site . "<br/>";
		// die(1);
		$remote_request = get_site_tools_url( $site ) . '/' . $request;
		// print $remote_request . "<br/>";
		if ( strlen( $remote_request ) < 4 ) {
			print "remote tools not set.<br/>";
			die ( 2 );
		}
		// print $remote_request;

		if ( strstr( $remote_request, "?" ) ) {
			$glue = "&";
		} else {
			$glue = "?";
		}
		$api_key = sql_query_single_scalar( "select api_key from im_multisite where id = $site" );
		if ( $api_key ) {
			// print "key";
			$remote_request .= $glue . "api_key=$api_key";
		} else {
			print "no api key while accessing " . $site . "<br/>";
			die( 1 );
		}
		// print "Execute remote: " . $remote_request . "<br/>";
		// print "XX" . $remote_request . "XX<br/>";
		$html = im_file_get_html( $remote_request );

		// print $html;

		return $html;
	}

	static function RunAll( $func ) {
		print MultiSite::GetAll( $func );
	}

	static function GetAll( $func, $debug = false ) {
		global $conn;

		$sql    = "SELECT id, tools_url, site_name, active FROM im_multisite ORDER BY 1";
		$result = mysqli_query( $conn, $sql );
		if ( ! $result ) {
			sql_error( $sql );
			die ( 1 );
		}
		$first = true;
		$data  = "";

		while ( $site_info = mysqli_fetch_row( $result ) ) {
//			print date( "m:s" ) . "<br/>";
			$id        = $site_info[0];
			$url       = $site_info[1];
			$site_name = $site_info[2];
			$active    = $site_info[3];

			if ( ! $active ) {
				print "site " . $id . " not active <br/>";
				continue;
			}

			if ( $debug ) {
				print_time( "site " . $site_name, true );
			}

			if ( strstr( $func, "?" ) ) {
				$glue = "&";
			} else {
				$glue = "?";
			}
			$file = $url . "/" . $func . $glue . "header=" . ( $first ? "1" : "0" ) . "&key=lasdhflajsdhflasjdhflaksj";
			// print $file . "<br/>";
			$result_text = im_file_get_html( $file );
//			print $result_text;
			$data  .= $result_text;
			$first = false;
		}
		$data .= "</table>";

		return $data;
	}

	static function isMaster() {
		return self::LocalSiteID() == self::getMaster();
	}

	static function LocalSiteID() {
		global $local_site_id;

		if ( $local_site_id < 0 ) {
			$sql           = "SELECT id FROM im_multisite WHERE local = 1";
			$local_site_id = sql_query_single_scalar( $sql );
		}

		return $local_site_id;
	}

	static function getMaster() {
		$count = sql_query_single_scalar( "SELECT count(*) FROM im_multisite WHERE master = 1" );
		if ( $count != 1 ) {
			print "configuration error - site master.<br/>";
			die ( 1 );
		}

		return sql_query_single_scalar( "SELECT id FROM im_multisite WHERE master = 1" );
	}

	static function LocalSiteName() {
		$sql = "SELECT display_name FROM im_multisite WHERE local = 1";

		return sql_query_single_scalar( $sql );
	}

	static function GetSiteName( $id ) {
		$sql = "SELECT display_name FROM im_multisite WHERE id = " . $id;

		return sql_query_single_scalar( $sql );

	}

	static function LocalSiteTools() {
		$sql = "SELECT tools_url FROM im_multisite WHERE local = 1";

		return sql_query_single_scalar( $sql );
	}

	static function SiteTools( $site_id ) {
		if ( ! is_numeric( $site_id ) ) {
			die ( "site id should be numeric" . $site_id );
		}
		$sql = "SELECT tools_url FROM im_multisite WHERE id = " . $site_id;

		return sql_query_single_scalar( $sql );
	}

	static function UpdateFromRemote( $table, $key, $remote = 0, $query = null, $ignore = null ) {
		if ( $remote == 0 ) {
			$remote = self::getMaster();
		}

		$url = "multi-site/sync-data.php?table=$table&operation=get";
		if ( $query ) {
			$url .= "&query=" . urlencode( $query );
		}

		$html = MultiSite::Execute( $url, $remote );

		// print $url . "<br/>";

		// print $html;

		if ( strlen( $html ) > 100 ) {
			// printbr($html);
			MultiSite::UpdateTable( $html, $table, $key, $query, $ignore );
		} else {
			print "short response. Operation aborted <br/>";
			die( 1 );
		}
	}

	static private function UpdateTable( $html, $table, $table_key, $query = null, $ignore_fields = null ) {
		global $conn;

		// 		print header_text( false, true, false );
		$dom = im_str_get_html( $html );

		// print "Table key: X" . $table_key . "X<br/>";

		$headers    = array();
		$fields     = array();
		$first      = true;
		$keys       = array();
		$key_order  = - 1;
		$field_list = null;

		foreach ( $dom->find( 'tr' ) as $row ) {
			// First line - headers.
			if ( $first ) {
				$i = 0;
				foreach ( $row->children() as $html_key ) {
					$key = $html_key->plaintext;
					print "key: " . $key . "<br/>";
					array_push( $headers, $key );

					if ( ! strcmp( $key, $table_key ) ) {
						$key_order = $i;
					}
					$i ++;
					if ( ( $ignore_fields == null ) or ( ! in_array( $key, $ignore_fields ) ) ) {
						$field_list .= $key . ", ";
					}

				}
				$field_list = rtrim( $field_list, ", " );
				// print $field_list . "<br/>";
				if ( $key_order == - 1 ) {
					print "Key $table_key not found<br/>";
					die( 1 );
				}
//				print "key: " . $key_order . "<br/>";
				// unset($headers[0]);
//				$field_list = comma_implode( $headers );
				// print "headers: " . $field_list . "<br/>";
				$first = false;
				continue;
			}
			$update_fields = "";
			$i             = 0;
			$insert        = false;
			$insert_values = "";

			foreach ( $row->children() as $value ) {
				$fields[ $i ] = $value->plaintext;
				$i ++;
			}
			$row_key = $fields[ $key_order ];
			array_push( $keys, $row_key );
			$sql = "SELECT COUNT(*) FROM $table WHERE $table_key=" . quote_text( $row_key );

			if ( sql_query_single_scalar( $sql ) < 1 ) {
				print "<br/>handle " . $row_key . " inserted ";
				$insert = true;
			}
//			else {
//				if (mysqli_affected_rows($conn) > 0)
//					print "<br/>handle " . $row_key . " updated ";
//			}
			for ( $i = 0; $i < count( $headers ); $i ++ ) {
				if ( ( $ignore_fields == null ) or ( ! in_array( $headers[ $i ], $ignore_fields ) ) ) {
					if ( $insert ) {
//						if (strlen($fields[$i] == 0)) $insert_values .= "NULL, ";
//						else // print strlen($fields[$i]) . " " . $fields[$i] . "<br/>";
						$insert_values .= quote_text( mysqli_real_escape_string( $conn, $fields[ $i ] ) ) . ", ";

					} else { // Update
						$update_fields .= $headers[ $i ] . "=" . quote_text( mysqli_real_escape_string( $conn, $fields[ $i ] ) ) . ", ";
					}
				}
			}

			if ( $insert ) {
				$sql = "INSERT INTO $table (" . $field_list . ") VALUES ( " . rtrim( $insert_values, ", " ) . ")";
				// print $sql . "<br/>";
				sql_query( $sql );
//				 die (1);
			} else {
				$sql = "UPDATE $table SET " . rtrim( $update_fields, ", " ) .
				       " WHERE $table_key = " . quote_text( $row_key );
				// print $sql . "<br/>";
				sql_query( $sql );
			}
		}
		if ( $i < 3 ) {
			print "not enough records.<br/>";
			print "Aboring<br/>";
			die( 2 );
		}
		// Delete not received keys.
		$sql = "select $table_key from $table";
		if ( $query ) {
			$sql .= " where " . $query;
		}
		print $sql . "<br/>";
		$for_delete = "";
		foreach ( sql_query_array_scalar( $sql ) as $key ) {
			// print "checking $key...";
			if ( ! in_array( $key, $keys ) ) {
				print "delete key " . $key . "<br/>";
				$for_delete .= quote_text( $key ) . ", ";
			}
//			print "<br/>";
		}

		if ( strlen( $for_delete ) ) {
			print "for delete: " . $for_delete;
			$sql = "DELETE FROM $table WHERE $table_key IN (" . rtrim( $for_delete, ", " ) . ")";
			// if ($query) $sql .= " and " . $query;
			print $sql;

			sql_query( $sql );
		}
	}
}

