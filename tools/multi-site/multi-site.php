<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 02/02/17
 * Time: 18:13
 */

require_once( 'simple_html_dom.php' );

if ( ! defined( "TOOLS_DIR" ) ) {
	define( "TOOLS_DIR", dirname( dirname( __FILE__ ) ) );
}

require_once( TOOLS_DIR . '/gui/sql_table.php' );

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
		print $remote_request . "<br/>";
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
//			$remote_request = $remote_request . "&multisite";
//		} else {
//			$remote_request = $remote_request . "?multisite";
//		}

		$api_key = sql_query_single_scalar( "select api_key from im_multisite where id = $site" );
		if ( $api_key ) {
			$remote_request .= $glue . "api_key=$api_key";
		} else {
			print "no api key<br/>";
		}
		// print "Execute remote: " . $remote_request . "<br/>";
		$html = file_get_html( $remote_request );

		// print $html;

		return $html;
	}

	static function RunAll( $func ) {
		print MultiSite::GetAll( $func );
	}

	static function GetAll( $func ) {
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
			$id        = $site_info[0];
			$url       = $site_info[1];
			$site_name = $site_info[2];
			$active    = $site_info[3];

			if ( ! $active ) {
				print "site " . $id . " not active <br/>";
				continue;
			}

			if ( strstr( $func, "?" ) ) {
				$glue = "&";
			} else {
				$glue = "?";
			}
			$file = $url . "/" . $func . $glue . "header=" . ( $first ? "1" : "0" ) . "&key=lasdhflajsdhflasjdhflaksj";
			// print $file . "<br/>";
			$result_text = file_get_html( $file );
//			print $result_text;
			$data  .= $result_text;
			$first = false;
		}
		$data .= "</table>";

		return $data;
	}

	static function LocalSiteName() {
		$sql = "SELECT display_name FROM im_multisite WHERE local = 1";

		return sql_query_single_scalar( $sql );
	}

	static function GetSiteName( $id ) {
		$sql = "SELECT display_name FROM im_multisite WHERE id = " . $id;

		return sql_query_single_scalar( $sql );

	}

	static function LocalSiteID() {
		global $local_site_id;

		if ( $local_site_id < 0 ) {
			$sql           = "SELECT id FROM im_multisite WHERE local = 1";
			$local_site_id = sql_query_single_scalar( $sql );
		}

		return $local_site_id;
	}

	static function LocalSiteTools() {
		$sql = "SELECT tools_url FROM im_multisite WHERE local = 1";

		return sql_query_single_scalar( $sql );
	}

	static function SiteTools( $site_id ) {
		$sql = "SELECT tools_url FROM im_multisite WHERE id = " . $site_id;

		return sql_query_single_scalar( $sql );
	}

}