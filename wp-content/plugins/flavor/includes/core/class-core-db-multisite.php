<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 02/02/17
 * Time: 18:13
 */

$local_site_id = - 1;

/**
 * Class Core_Db_MultiSite
 */
class Core_Db_MultiSite extends Core_MultiSite {

	/**
	 * Core_Db_MultiSite constructor.
	 */
	public function __construct() {
		if ( TableExists( "multisite" ) ) {
			$sql           = "select id, site_name, tools_url, local, display_name, active, master, user, password " .
			                 " from im_multisite";
			$results       = SqlQuery( $sql );
			$sites_array   = array();
			$master_id     = - 1;
			$local_site_id = - 1;
			while ( $row = SqlFetchRow( $results ) ) {
				$id = $row[0];
				if ( $row[3] ) {
					$local_site_id = $row[0];
				} // local
				if ( $row[6] ) {
					$master_id = $row[0];
				}     // master
				$line               = array( $id, $row[1], $row[2], $row[7], $row[8] );
				$sites_array[ $id ] = $line;
				// array_push($sites_array, $line);
			}
		} else {
			// Single site
			$sites_array   = array();
			$master_id     = 1;
			$local_site_id = 1;
			$sites_array[1] = array(1, 'local', $_SERVER['REQUEST_SCHEME'] . '://127.0.0.1', 1, '', '');
		}
//		const site_id_idx = 0;
//		const site_name_idx = 1;
//		const site_tools_idx = 2;
//		const api_key = 3;

		Core_MultiSite::__construct( $sites_array, $master_id, $local_site_id );
	}

	/**
	 * @return mixed
	 */
	static public function LocalSiteId() {
		return self::getInstance()->getLocalSiteId();
	}

	/**
	 * @return mixed
	 */

	// Static getters from singleton.
	/**
	 * @return Core_Db_MultiSite
	 */
	static function getInstance() {
		static $instance;

		if ( ! $instance ) {
			// print "creating instance<br/>";
			$instance = new Core_Db_MultiSite();
			if (! $instance)
				print 1/0;
		}

		return $instance;
	}

	/**
	 * @return |null
	 */
	static function LocalSiteUrl() {
		return self::getInstance()->getLocalSiteUrl();
	}

	/**
	 * @return mixed
	 */
	static function LocalSiteName() {
		return self::getInstance()->getLocalSiteName();
	}

	/**
	 * @param $remote_site_id
	 * @param $local_prod_id
	 * @param $remote_prod_id
	 */
	static function map( $remote_site_id, $local_prod_id, $remote_prod_id ) {
		MyLog( __FILE__, __METHOD__ );
		$sql = "INSERT INTO im_multisite_map (remote_site_id, local_prod_id, remote_prod_id) " .
		       " VALUES (" . $remote_site_id . ", " . $local_prod_id . ", " . $remote_prod_id . ")";

		MyLog( $sql );

		SqlQuery( $sql );
	}

	/**
	 * @param $id
	 * @param $remote_id
	 * @param $remote_site
	 */
	static function CopyImage( $id, $remote_id, $remote_site ) {
		$req = "multi-site/secondary-send-pictures.php?ids=" . $id . "," . $remote_id;

		$info = Core_Db_MultiSite::Execute( $req, $remote_site );

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

	/**
	 * @param $id
	 *
	 * @return string|null
	 */
	static function getPickupAddress( $id ) {
		if (! ($id > 0))
			return "unknown site";
		if (TableExists("multisite"))
			return str_replace('-', ' ', SqlQuerySingleScalar( "SELECT pickup_address FROM im_multisite WHERE id = " . $id ));
		return "";
	}

	/**
	 * @param $request
	 * @param $site
	 * @param bool $debug
	 *
	 * @return bool|string
	 * @deprecated
	 */
	static function sExecute( $request, $site, $debug = false ) {
		$i = self::getInstance();
		$r = $i->Execute( $request, $site, $debug );
		if ( $debug ) {
			print "<br/>Result: " . $r;
		}

		return $r;
	}

	/**
	 * @param $table
	 * @param $key
	 * @param int $remote
	 * @param null $query
	 * @param null $ignore
	 * @param bool $debug
	 *
	 * @return bool|void
	 */
	function UpdateFromRemote( $table, $key, $remote = 0, $query = null, $ignore = null, $debug = false )
	{
		if ( $remote == 0 ) $remote = self::getMaster();

		if ($this->isMaster()) return true;

		$url = Freight::getPost() . "?operation=sync_data_$table";
		if ( $query ) $url .= "&query=" . urlencode( $query );

		$html = Core_Db_MultiSite::Execute( $url, $remote, $debug );

		if (! $html) {
			print "Can't get data from " . Core_Db_MultiSite::getInstance()->getSiteToolsURL( $remote ) . "/" . $url;
			return false;
		}

		if ($debug)
			print $html;

		if ( strlen( $html ) > 100 ) {
			//printbr($html);
			if (! Core_Db_MultiSite::UpdateTable( $html, $table, $key, $query, $ignore, $debug ))
			{
				print "check $url<br/>";
				die(1);
			}
		} else {
			print "short response. Operation aborted<br/>";
			print "url = " . $this->getSiteURL($remote) . $url;

			print $html;

			return;
		}
	}

	/**
	 * @param $html
	 * @param $table
	 * @param $table_key
	 * @param null $query
	 * @param null $ignore_fields
	 * @param bool $verbose
	 *
	 * @return bool
	 * @throws Exception
	 */
	static function UpdateTable( $html, $table, $table_key, $query = null, $ignore_fields = null, $verbose = false )
	{
		$dom = Core_Get_File::str_get_html( $html );

		$db_prefix = GetTablePrefix($table);

		$headers      = array();
		$fields       = array();
		$first        = true;
		$keys         = array();
		$key_order    = - 1;
		$field_list   = null;
		$insert_count = 0;
		$update_count = 0;
		$i = 0;

		foreach ( $dom->find( 'tr' ) as $row ) {
			// First line - headers.
			if ( $first ) {
				$i = 0;
				foreach ( $row->children() as $html_key ) {
					$key = $html_key->plaintext;
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
				if ( $key_order == - 1 ) {
					if ($verbose) {
						print "Key $table_key not found<br/>";
						print "data from server:<br/>";
						print $html;
					}
					die( 1 );
				}
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
			$sql = "SELECT COUNT(*) FROM ${db_prefix}$table WHERE $table_key=" . QuoteText( $row_key );

			$found = SqlQuerySingleScalar( $sql ) >= 1;

			if ( ! $found ) {
//				print "<br/>handle " . $row_key . " inserted ";
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
						$insert_values .= QuoteText( EscapeString( $fields[ $i ] ) ) . ", ";
						$insert_count ++;

					} else { // Update
						$update_fields .= $headers[ $i ] . "=" . QuoteText( EscapeString( $fields[ $i ] ) ) . ", ";
						$update_count ++;
					}
				}
			}

			if ( $insert ) {
				$sql = "INSERT INTO ${db_prefix}$table (" . $field_list . ") VALUES ( " . rtrim( $insert_values, ", " ) . ")";
				SqlQuery( $sql );
			} else {
				$sql = "UPDATE ${db_prefix}$table SET " . rtrim( $update_fields, ", " ) .
				       " WHERE $table_key = " . QuoteText( $row_key );
				SqlQuery( $sql );
			}
		}
		if ( $i < 3 ) {
			if ($verbose) {
				print "not enough records.<br/>";
				print $html . "<br/>";
				print "Aboring<br/>";
			}
			return false;
		}
		if ($verbose) {
			 print "Update count: " . $update_count . "<br/>";
			 print "Insert count: " . $insert_count . "<br/>";
		}

		// Delete not received keys.
		$sql = "select $table_key from ${db_prefix}$table";
		if ( $query ) {
			$sql .= " where " . $query;
		}
		$for_delete = "";
		foreach ( SqlQueryArrayScalar( $sql ) as $key ) {
			if ( ! in_array( $key, $keys ) ) {
				$for_delete .= QuoteText( $key ) . ", ";
			}
		}

		if ( strlen( $for_delete ) ) {
			print "for delete: " . $for_delete;
			$sql = "DELETE FROM ${db_prefix}$table WHERE $table_key IN (" . rtrim( $for_delete, ", " ) . ")";

			SqlQuery( $sql );
		}
		return true;
	}
}
