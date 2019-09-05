<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 02/02/17
 * Time: 18:13
 */

// require_once( 'im_simple_html_dom.php' );
if ( ! defined( "TOOLS_DIR" ) ) {
	define( "TOOLS_DIR", dirname( dirname( __FILE__ ) ) );
}

require_once( ROOT_DIR . '/niver/gui/sql_table.php' );

require_once( ROOT_DIR . '/niver/MultiSite.php' );

$local_site_id = - 1;

class ImMultiSite extends MultiSite {

	public function __construct() {
		if ( ! table_exists( "im_multisite" ) ) {
			return;
		}
		$sql           = "select id, site_name, tools_url, local, display_name, active, api_key, master " .
		                 " from im_multisite";
		$results       = sql_query( $sql );
		$sites_array   = array();
		$master_id     = - 1;
		$local_site_id = - 1;
		while ( $row = sql_fetch_row( $results ) ) {
			$id = $row[0];
			if ( $row[3] ) {
				$local_site_id = $row[0];
			} // local
			if ( $row[7] ) {
				$master_id = $row[0];
			}     // master
			$line               = array( $id, $row[1], $row[2], $row[6] );
			$sites_array[ $id ] = $line;
			// array_push($sites_array, $line);
		}
//		const site_id_idx = 0;
//		const site_name_idx = 1;
//		const site_tools_idx = 2;
//		const api_key = 3;

		MultiSite::__construct( $sites_array, $master_id, $local_site_id );

	}


	static public function LocalSiteId() {
		return self::getInstance()->getLocalSiteId();
	}

	/**
	 * @return mixed
	 */

	// Static getters from singleton.
	static function getInstance() {
		static $instance;

		if ( ! $instance ) {
			// print "creating instance<br/>";
			$instance = new ImMultiSite();
		}

		return $instance;
	}

	static function LocalSiteTools() {
		return self::getInstance()->getLocalSiteTools();
	}

	static function LocalSiteName() {
		return self::getInstance()->getLocalSiteName();
	}


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

		$info = ImMultiSite::Execute( $req, $remote_site );

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

	static function getPickupAddress( $id ) {
		return sql_query_single_scalar( "SELECT pickup_address FROM im_multisite WHERE id = " . $id );
	}

//	static function LocalSiteTools() {
//		$sql = "SELECT tools_url FROM im_multisite WHERE local = 1";
//
//		return sql_query_single_scalar( $sql );
//	}

	static function SiteTools( $site_id ) {
		if ( ! is_numeric( $site_id ) ) {
			die ( "site id should be numeric" . $site_id );
		}
		$sql = "SELECT tools_url FROM im_multisite WHERE id = " . $site_id;

		return sql_query_single_scalar( $sql );
	}

	static function CORS() {
		if (! table_exists("im_multisite")) return "";
		$sql    = "SELECT tools_url FROM im_multisite WHERE master = 1";
		$row    = sql_query_single_scalar( $sql );
		$result = "Access-Control-Allow-Origin: http://";
		$result .= parse_url( $row, PHP_URL_HOST );

		return $result;
	}

	static function sExecute( $request, $site, $debug = false ) {
		$i = self::getInstance();
		//	var_dump ($i);
		$r = $i->Execute( $request, $site, $debug );
		if ( $debug ) {
			print "<br/>Result: " . $r;
		}

		return $r;
	}

	static function SiteName() {
		self::getInstance()->getLocalSiteName();
	}

	function UpdateFromRemote( $table, $key, $remote = 0, $query = null, $ignore = null, $debug = false ) {
		$debug = false;
		if ( $remote == 0 ) {
			$remote = self::getMaster();
		}

		$url = "multi-site/sync-data.php?table=$table&operation=get";
		if ( $query ) {
			$url .= "&query=" . urlencode( $query );
		}

		// print $url . "<br/>";
		if ($debug)
			print $url;

		$html = ImMultiSite::Execute( $url, $remote );

		if ($debug)
			print $html;

		if ( strlen( $html ) > 100 ) {
			//printbr($html);
			ImMultiSite::UpdateTable( $html, $table, $key, $query, $ignore, $debug );
		} else {
			print "short response. Operation aborted <br/>";

			return;
		}
	}

	static private function UpdateTable( $html, $table, $table_key, $query = null, $ignore_fields = null, $debug = false ) {
		global $conn;

		if ($debug)
			print "start<br/>";

		// 		print header_text( false, true, false );
		$dom = im_str_get_html( $html );

		// print "Table key: X" . $table_key . "X<br/>";

		$headers      = array();
		$fields       = array();
		$first        = true;
		$keys         = array();
		$key_order    = - 1;
		$field_list   = null;
		$insert_count = 0;
		$update_count = 0;

		foreach ( $dom->find( 'tr' ) as $row ) {
			// First line - headers.
			if ( $first ) {
				$i = 0;
				foreach ( $row->children() as $html_key ) {
					$key = $html_key->plaintext;
					// print "key: " . $key . "<br/>";
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
			if (strlen($row_key) < 1)
				die ("invalid key");

			array_push( $keys, $row_key );
			$sql = "SELECT COUNT(*) FROM $table WHERE $table_key=" . quote_text( $row_key );

			$found = sql_query_single_scalar( $sql ) >= 1;

			if ($debug)
				print "Checking " . $sql . " found= " . $found . "<br/>";

			if ( ! $found ) {
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
						$insert_count ++;

					} else { // Update
						$update_fields .= $headers[ $i ] . "=" . quote_text( mysqli_real_escape_string( $conn, $fields[ $i ] ) ) . ", ";
						$update_count ++;
					}
				}
			}

			if ( $insert ) {
				$sql = "INSERT INTO $table (" . $field_list . ") VALUES ( " . rtrim( $insert_values, ", " ) . ")";
				if ($debug)
					print $sql . "<br/>";
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
		print "Update count: " . $update_count . "<br/>";
		print "Insert count: " . $insert_count . "<br/>";

		// Delete not received keys.
		$sql = "select $table_key from $table";
		if ( $query ) {
			$sql .= " where " . $query;
		}
//		print $sql . "<br/>";
		$for_delete = "";
		foreach ( sql_query_array_scalar( $sql ) as $key ) {
			// print "checking $key...";
			if ( ! in_array( $key, $keys ) ) {
//				print "delete key " . $key . "<br/>";
				$for_delete .= quote_text( $key ) . ", ";
			}
//			print "<br/>";
		}

		if ( strlen( $for_delete ) ) {
			print "for delete: " . $for_delete;
			$sql = "DELETE FROM $table WHERE $table_key IN (" . rtrim( $for_delete, ", " ) . ")";
			// if ($query) $sql .= " and " . $query;
//			print $sql;

			sql_query( $sql );
		}
	}
}
