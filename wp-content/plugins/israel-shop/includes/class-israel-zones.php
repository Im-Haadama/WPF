<?php


class Israel_Zones {

	protected $auto_loader;
	protected $table_prefix;
	protected $shortcodes;

	protected static $_instance = null;

	/**
	 * Israel_Zones constructor.
	 */
	public function __construct() {
		self::$_instance = $this;
		self::init();
	}

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function init()
	{
		define( 'ISRAEL_ZONES_ABSPATH', dirname( ISRAEL_ZONES_PLUGIN_FILE ) . '/' );

		$this->auto_loader = new Core_Autoloader(ISRAEL_ZONES_ABSPATH);

		$this->init_hooks();

		// Todo: move to admin page
		$this->shortcodes = Core_Shortcodes::instance();
		$this->shortcodes->add(array("israel_zones" => array("Israel_Zones::main", null),
			                          "israel_zone"=>array("Israel_Zones::zone", null)));
		$this->shortcodes->do_init();
		$this->table_prefix = GetTablePrefix();
	}

	private function init_hooks() {
		add_action( 'init', array( 'Core_Shortcodes', 'init' ) );
		add_action('import_cities', array(__CLASS__, 'import_wrapper'));
		Core_Gem::AddTable("cities");
		add_filter('wpf_freight_cities', array($this, 'freight_cities'));

	}

	static function Args($type = null)
	{
		$args = [];
		$args["post_file"] = Flavor::getPost();
		$args["import_page"] = AddToUrl(array("operation"=> "gem_do_import", "table"=>"cities"));
		// $args["edit"] = true;
		return $args;
	}

	static function zone_wrapper()
	{
		$id = GetParam("id", true);
		$zone = WC_Shipping_Zones::get_zone_by( 'zone_id', $id );
		$operation = GetParam("operation", false, null);

		if ($operation){
			switch ($operation)
			{
				case "create":
					$zone = self::create_zone();
					break;
			}
		}
		if (! $zone)
			return "Zone $id not found<br/>" .
				Core_Html::GuiHyperlink("Create", AddToUrl("operation", "create"));

		$result = "";
		$result .= Core_Html::GuiHeader(1, $zone->get_zone_name());
		$zone_info = SqlQueryArray("select city_name, zipcode from im_cities where zone = $id");
		$result .= Core_Html::gui_table_args($zone_info);
		$result .= Core_Html::GuiHeader(2, "Common prefixes");
		$prefixes = self::CommonPrefix(array_column($zone_info, 1), $id);
		foreach ($prefixes as $prefix)
			$result .= $prefix . "*<br>";
		$zone = new WC_Shipping_Zone($id);
		$zone->set_zone_locations($prefixes);
		return $result;
	}

	static function create_zone()
	{
		$zone = new WC_Shipping_Zone();
		$store = new WC_Shipping_Zone_Data_Store();
		$store->create($zone);

		return $zone;
	}

	static function CommonPrefix($zipcodes, $zone_id)
	{
		$prefix_array = [];

		foreach ($zipcodes as $code)
		{
			if (strlen($code) < 1) continue;
			if ($code == "-1") continue;
			$found = false;
			// Find if this code matched by previous prefixes.
			foreach ($prefix_array as $prefix){
				if (strstr($code, $prefix)) $found = true;
			}
			if (! $found){
				// We need to find the shortes prefix that matches the current codes in this zone but not other zones.
				for ($i = 1; $i < strlen($code); $i++) {
					$candidate = substr($code, 0, $i);
//					print "Can: $candidate<br/>";
					if (! SqlQuerySingleScalar("select count(*) from im_cities where zone != $zone_id and zipcode like '$candidate%'")) {
						array_push($prefix_array, $candidate);
//						print "Adding $candidate<br/>";
						$found = true;
						break;
					}
				}
				if (! $found) print "Something wrong: " . $code . "<br/>";
			}
		}

		return $prefix_array;
	}

	function freight_cities()
	{
		$args = self::Args();
		$operation = GetParam("operation");

		if ($operation){
			$args["operation"] = $operation;
			$id = GetParam("id", false, 0);

			$result = apply_filters( $operation, $operation, $id, $args );
			if ( $result ) 	return $result;
		}

		if ($id = GetParam("id")){
			return Core_Gem::GemElement("cities", $id, $args);
		}
//		$args["import_action"] = 'http://127.0.0.1/zone-editor/?operation=import'; // AddToUrl("operation", "import");
//		print $args["import_action"];
		$args["enable_import"] = true;
		$args["links"] = array("zone" => self::GetLink("zone", "%s"),
			"id"=>self::GetLink("city", "%s"));

		$result = Core_Gem::GemTable("cities", $args);
		return $result;
	}

	static public function GetLink($type, $id)
	{
		switch ($type) {
			case "zone":
				return "/zone?id=$id";
			case "city":
				return AddToUrl("id", $id);
		}
	}

//	static function import_wrapper()
//	{
//		$me = self::instance();
//		return $me->do_import();
//	}

//	function do_import()
//	{
//		print 1/0;
//		$file_name = $_FILES["fileToUpload"]["tmp_name"];
//		print "Trying to import $file_name<br/>";
//		$I                    = new Core_Importer();
//		$fields               = null;
//		$fields               = array();
//		try {
//			$result = $I->Import( $file_name, "{$this->table_prefix}cities", $fields, null );
//		} catch ( Exception $e ) {
//			print $e->getMessage();
//
//			return false;
//		}
//		print $result[0] . " rows imported<br/>";
//		print $result[1] . " duplicate rows <br/>";
//		print $result[2] . " failed rows <br/>";
//		return true;
//	}

	function run($limit){
		$sql = "select id from im_cities where zipcode is null limit $limit";
		$cities = SqlQueryArrayScalar($sql);
		foreach ($cities as $city_id) {
			$city_name = SqlQuerySingleScalar("select city_name from im_cities where id = $city_id");
			$code = self::israelpost_get_city_postcode($city_name);
			if (! strlen($code)) $code = "-3";
//			print "city: $city_name code: $code";
			SqlQuery("update im_cities set zipcode = $code where id = $city_id");
		}
	}

	function israelpost_get_address_postcode( $city, $street, $house ) {
		return israelpost_get_city_postcode($city, $street, $house);
	}

	static function israelpost_get_city_postcode( $city )
	{
		return israelpost_get_city_postcode($city);
	}
}