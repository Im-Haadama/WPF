<?php

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 07/01/19
 * Time: 11:42
 */
abstract class FieldIdx {
	const site_id_idx = 0;
	const site_name_idx = 1;
	const site_tools_idx = 2;
	const api_key = 3;
}

;

require_once( "data/im_simple_html_dom.php" );

class MultiSite {
	private $sites_array;
	private $master_id;
	private $local_site_id;

	/**
	 * MultiSite constructor.
	 *
	 * @param $sites_array
	 */
	public function __construct( $sites_array, $master_id, $local_site_id ) {
		$this->sites_array   = $sites_array;
		$this->master_id     = $master_id;
		$this->local_site_id = $local_site_id;
	}

	static function RunAll( $func ) {
		print MultiSite::GetAll( $func );
	}

	function GetAll( $func, $verbose = false, $debug = false, $strip = false ) {
		$output = "";
		if ( $debug ) {
			print "s= " . $strip . "<br/>";
		}
		$first = true;
		$data  = array( array( "site name", "result" ));

		foreach ( $this->sites_array as $site_id => $site ) {
			$result = $this->Run( $func, $site_id, $first, $debug );
			if ( $strip ) {
				$result = strip_tags( $result, "<div><br><p><table><tr><td>" );
			}
			if ( $verbose ) {
				array_push( $data, array( $this->sites_array[ $site_id ][ FieldIdx::site_name_idx ], $result ) );
			} else {
				$output .= $result;
			}
			$first = false;
		}

		if ( $verbose ) {
			return gui_table( $data );
		}

		return $output;
	}

	function getAllServers()
	{
		$result = array();
		foreach ( $this->sites_array as $site_id => $site ) {
			$r = parse_url($this->sites_array[$site_id][FieldIdx::site_tools_idx]);
			// var_dump( $r ). "<br/>";

			array_push($result, $r['host']);
		}
		return $result;
	}

	function Run( $func, $site_id, $first = false, $debug = false ) {
		$url = $this->getSiteToolsURL( $site_id );

		$site_name = $this->getSiteName( $site_id );

		if ( strstr( $func, "?" ) ) {
			$glue = "&";
		} else {
			$glue = "?";
		}
		$file = $url . "/" . $func . $glue . "header=" . ( $first ? "1" : "0" ) . "&key=lasdhflajsdhflasjdhflaksj";

		if ( $debug ) {
			print "Getting $file...<br/>";
		}

		$result_text = im_file_get_html( $file );

		if ( $debug ) {
			print "result from " . $site_name . "<br/>";
			print $result_text . "<br/>";
		}

		// print "id=" . $id . " " . "result: " . $result_text;
		return $result_text;
	}

	public function getSiteToolsURL( $site_id ) {
		if ( isset( $this->sites_array[ $site_id ] ) ) {
			return $this->sites_array[ $site_id ][ FieldIdx::site_tools_idx ];
		} else {
			print "site ";
			var_dump( $site_id );
			print " not defined!";

			return null;
		}
	}

	public function getSiteName( $site_id ) {
		return $this->sites_array[ $site_id ][ FieldIdx::site_name_idx ];
	}

	function Execute( $request, $site, $debug = false ) {
		$remote_request = $this->getSiteToolsURL( $site ) . '/' . $request;

//		 print $remote_request . "<br/>";
		if ( strlen( $remote_request ) < 4 ) {
			print "remote tools not set.<br/>";
			die ( 2 );
		}

		if ( strstr( $remote_request, "?" ) ) {
			$glue = "&";
		} else {
			$glue = "?";
		}
		// $api_key = sql_query_single_scalar( "select api_key from im_multisite where id = $site" );
		$api_key = $this->getApiKey( $site );

		if ( $api_key ) {
			// print "key";
			$remote_request .= $glue . "api_key=$api_key";
		} else {
			print "no api key while accessing " . $site . "<br/>";
			die( 1 );
		}
		//  print "Execute remote: " . $remote_request . "<br/>";
		// print "XX" . $remote_request . "XX<br/>";

		if ( $debug ) {
			print "request = " . $remote_request . "<br/>";
		}
		$html = im_file_get_html( $remote_request );

		// print $html;

		return $html;
	}

	public function getApiKey( $site_id ) {
		return $this->sites_array[ $site_id ][ FieldIdx::api_key ];
	}

	function getMaster() {
		return $this->master_id;
	}

	function isMaster() {
		return $this->master_id == $this->local_site_id;
	}

	function getLocalSiteName() {
		return $this->getSiteName( $this->getLocalSiteID() );
	}

	function getLocalSiteID() {
		return $this->local_site_id;
	}

	function getLocalSiteTools() {
		return $this->getSiteToolsURL( $this->local_site_id );
	}
}