<?php

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 07/01/19
 * Time: 11:42
 */
abstract class Core_Multisite_Fields {
	const site_id_idx = 0;
	const site_name_idx = 1;
	const site_url_idx = 2;
	const api_key = 3;
}

class Core_MultiSite {
	private $sites_array;
	private $master_id;
	private $local_site_id;
	private $http_codes;

	/// GETTERS

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

	/**
	 * @return mixed
	 */
	public function getSitesArray() {
		return $this->sites_array;
	}

	/**
	 * @return mixed
	 */
	public function getMasterId() {
		return $this->master_id;
	}

	/**
	 * @param $site_id
	 *
	 * @return mixed
	 */
	public function getHttpCode($site_id) {
		return $this->http_codes[$site_id];
	}

	public function getHttpCodes() {
		return $this->http_codes;
	}

	function getAllServers()
	{
		$result = array();
		foreach ( $this->sites_array as $site_id => $site ) {
			$r = parse_url($this->sites_array[$site_id][Core_Multisite_Fields::site_url_idx]);

			array_push($result, $r['host']);
		}
		return $result;
	}

	public function getSiteName( $site_id ) {
		if (isset($this->sites_array[ $site_id ][ Core_Multisite_Fields::site_name_idx ]))
			return $this->sites_array[ $site_id ][ Core_Multisite_Fields::site_name_idx ];
		die ("invalid site_id");
	}

	public function getSiteURL( $site_id ) {
//		var_dump($this->sites_array);
		if ( isset( $this->sites_array[ $site_id ] ) ) {
			return $this->sites_array[ $site_id ][ Core_Multisite_Fields::site_url_idx ];
		} else {
			print "site $site_id not defined!";

			return null;
		}
	}

	public function getApiKey( $site_id ) {
		return $this->sites_array[ $site_id ][ Core_Multisite_Fields::api_key ];
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

	function getLocalSiteUrl() {
		return $this->getSiteURL( $this->local_site_id );
	}

	/// REMOTING...
	/// Run makes the remoting. first parameter use to indicate if it's the first - so we can create one output from several servers (header = true only on first).
	/// Execute is for single call.
	/// GetAll will Run on all defined severs.
	function GetAll( $func, $verbose = false, $debug = false, $strip = false ) {
		$debug = GetParam("debug", false, $debug);
		$output = "";
		if ( $debug ) {
			print "s= " . $strip . "<br/>";
			$verbose = 1;
		}
		$first = true;
		$data  = array( array( "site name", "result" ));
		$rc = null;

		foreach ( $this->sites_array as $site_id => $site ) {
			$result = $this->Run( $func, $site_id, $first, $debug );
//			print $func . " ". $result . "<br/>";
			if (! $result) {
				$output .= "Can't get from " . $this->getSiteName($site_id) . " http code: " . $this->http_codes[$site_id] . Core_Html::Br();
				$output .= $this->getSiteURL($site_id) . '/' . $func . Core_Html::Br();
			}
			if ( $strip ) {
				$result = strip_tags( $result, "<div><br><p><table><tr><td>" );
			}
			if ( $verbose ) {
				array_push( $data, array( $this->sites_array[ $site_id ][ Core_Multisite_Fields::site_name_idx ], $result ) );
			} else {
				$output .= $result;
			}
			$first = false;
		}

		if ( $verbose ) {
			return Core_Html::gui_table_args( $data );
		}

		return $output;
	}

	function Run( $func, $site_id, $first = false, $debug = false )
	{
		$debug = 0;
		$url = $this->getSiteURL( $site_id );

		$glue = (( strstr( $func, "?" ) ) ? "&" : "?");

		$site_name = $this->getSiteName( $site_id );

		$file = $url . "/" . $func . $glue . "header=" . ( $first ? "1" : "0" );

		if ( $debug ) print "Getting $file...<br/>";

		$username = null;
		$password = null;
		if (isset($this->sites_array[$site_id][3])){ // Would work only for anon
			$username = $this->sites_array[ $site_id ][3];
			$password = $this->sites_array[ $site_id ][4];
		}

		$result_text = self::DoRun($file, $this->http_codes[$site_id], $username, $password);

		if (in_array($this->http_codes[$site_id], array(404, 500))) return false;

		if ( $debug ) {
			print "result from " . $site_name . "<br/>";
			print $result_text . "<br/>";
		}

		// print "id=" . $id . " " . "result: " . $result_text;
		return $result_text;
	}

	static function DoRun($file, &$http_code, $username= null, $password = null)
	{
//		print "u=$username p=$password<br/>";
		 if ($username) $file .= "&AUTH_USER=" . trim($username) . "&AUTH_PW=" . urlencode(trim($password));
//		print "file =$file<br/>";
//		print $file;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $file);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//		curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
//		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

		$result_text = curl_exec($ch);
//		print "ret=$result_text<br/>";
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		return $result_text;
	}

	function Execute( $request, $site, $debug = false ) {
		return $this->Run($request, $site, true, $debug);
//		$remote_request = $this->getSiteToolsURL( $site ) . '/' . $request;
//
////		 print $remote_request . "<br/>";
//		if ( strlen( $remote_request ) < 4 ) {
//			print "remote tools not set.<br/>";
//			die ( 2 );
//		}
//
//		if ( strstr( $remote_request, "?" ) ) {
//			$glue = "&";
//		} else {
//			$glue = "?";
//		}
//		// $api_key = sql_query_single_scalar( "select api_key from im_multisite where id = $site" );
//		$api_key = $this->getApiKey( $site );
//
//		if ( $api_key ) {
//			// print "key";
//			$remote_request .= $glue . "api_key=$api_key";
//		} else {
//			print "no api key while accessing " . $site . "<br/>";
//			die( 1 );
//		}
//		//  print "Execute remote: " . $remote_request . "<br/>";
//		// print "XX" . $remote_request . "XX<br/>";
//
//		if ( $debug ) {
//			print "request = " . $remote_request . "<br/>";
//		}
//		$html = im_file_get_html( $remote_request );
//
//		// print $html;
//
//		return $html;
	}


}