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
	const user=3;
	const password=4;
	const pickup_address = 5;
}

class Core_MultiSite {
	protected $sites_array;
	protected $master_id;
	protected $local_site_id;
	private $http_codes;
	private $results;

	/**
	 * @return mixed
	 */
	public function getResults($site_id) {
		return $this->results[$site_id] ?? null;
	}

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
//		print "master $master_site_id local $local_site_id<br/>";
//		var_dump($sites_array);
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
		die ("invalid site_id: $site_id");
	}

	public function getSiteURL( $site_id ) {
		if ( isset( $this->sites_array[ $site_id ] ) ) {
			return $this->sites_array[ $site_id ][ Core_Multisite_Fields::site_url_idx ];
		} else {
//			print "site $site_id not defined!";

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
//		dd("master " . $this->master_id . " local " . $this->local_site_id . "<br/>");
		if (! $this->master_id) return false;
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
		$this->results = array();
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
			$this->results[$site_id] = $result;
			if (! $result) {
				$output .= "Can't get from " . Core_Html::GuiHyperlink($this->getSiteName($site_id), $this->getSiteURL($site_id) . '/' . $func) .
				           " http code: " . $this->http_codes[$site_id] . Core_Html::Br();
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
		$url = $this->getSiteURL( $site_id );

		$glue = (( strstr( $func, "?" ) ) ? "&" : "?");

		$file = $url . "/" . $func . $glue . "header=" . ( $first ? "1" : "0" );
//		print $file;

		if ( $debug ) print "Getting $file...<br/>";

		$username = null;
		$password = null;
		if (isset($this->sites_array[$site_id][3])){ // Would work only for anon
			$username = $this->sites_array[ $site_id ][3];
			$password = $this->sites_array[ $site_id ][4];
		}

		$result_text = self::DoRun($file, $this->http_codes[$site_id], $username, $password, $debug);

		if (in_array($this->http_codes[$site_id], array(404, 500))) return false;

		return $result_text;
	}

	static function DoRun($file, &$http_code, $username= null, $password = null, $debug = false)
	{
		if (! function_exists("curl_init")) {
			print "Curl_init is missing<br/>";
			print "Php version: " . phpversion() . "<br/>";
			print 'sudo apt-get install php*-curl<br/>';
			print 'add ;extension=php_curl.dll to php.ini';
			print phpinfo();
			die (1);
		}

		if ($username) $file .= "&AUTH_USER=" . trim($username) . "&AUTH_PW=" . urlencode(trim($password));

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $file);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 3 );
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

		$result_text = curl_exec($ch);

		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		if (curl_errno($ch) == CURLE_OPERATION_TIMEDOUT)
		{
			echo "TIMEOUT";
			die(1);
		}

		curl_close($ch);

		if ($debug) {
			print "Failed! Trying to get $file<br/>";
			print "result: <br/>" . $result_text;
			print "http code: $http_code<br/>";
		}

		return $result_text;
	}

	function Execute( $request, $site, $debug = false ) {
		return $this->Run($request, $site, true, $debug);
	}

	function getSiteAddress($site_id)
	{
		if (isset($this->sites_array[$site_id][Core_Multisite_Fields::pickup_address]))
			return $this->sites_array[$site_id][Core_Multisite_Fields::pickup_address];
		return '';
	}
}