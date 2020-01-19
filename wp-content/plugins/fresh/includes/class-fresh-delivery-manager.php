<?php


class Fresh_Delivery_Manager
{
	protected static $_instance = null;
	private $logger;

	/**
	 * Fresh_Delivery_Manager constructor.
	 *
	 * @param $logger
	 */
	public function __construct( ) {
		$this->logger = new Core_Logger(__CLASS__);
	}

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			return new self();
		}
		return self::$_instance;
	}

	static function updateShippingMethods()
	{
		$instance = self::instance();
		if ($instance)
			return $instance->doUpdateShippingMethods();
	}

	function doUpdateShippingMethods()
	{
		$this->logger->info(__FUNCTION__);
	}

	function getShortcodes() {
		//           code                   function                              capablity (not checked, for now).
		return array( 'delivery_manager_update' => array( 'Fresh_Delivery_Manager::update_shipping_methods',    null ));          // Payments data entry
	}

	static function update_shipping_methods()
	{
		if (! self::create_missions()) return false;
		return self::do_update_shipping_methods();
	}

	static function create_missions($path_ids = null, $forward_week = 0)
	{
		$result = "";
		if (! $path_ids) $path_ids = sql_query_array_scalar("select distinct id from im_paths");
		foreach ($path_ids as $path_id){
			$result .= Core_Html::gui_header(1, "Create missions");
			if (! Mission::CreateFromPath($path_id, 8)) return false;
		}
		return true;
	}

	/**
	 * @param int $days_forward
	 * @param bool $disable_all
	 *
	 * 1) Create missions for the coming week.
	 * 2) update shipping description

	 * @return string
	 * @throws Exception
	 */

	static function do_update_shipping_methods()
	{
		$result = "";

		$paths = Fresh_Path::getAll();
		$zone_times = []; // [zone][date] = times
		foreach ($paths as $path)
		{
			$missions = sql_query_array_scalar("select id from im_missions where path_code = $path and date > curdate() and accepting = 1");
			foreach ($missions as $mission_id){
				$m = new Mission($mission_id);
				$date = $m->getDate();
				$mission_zone_times = $m->getZoneTimes();
				foreach($mission_zone_times as $zone_id => $zone_time){
					if (! isset($zone_times[$zone_id])) $zone_times[$zone_id] = [];
					if (! isset($zone_times[$zone_id][$date])) $zone_times[$zone_id][$date] = [];
					$zone_times[$zone_id][$date] = $zone_time;
				}
			}
		}

		$result .= Core_Html::gui_header(1, "Updating all shipping methods");
		$wc_zones = WC_Shipping_Zones::get_zones();

		foreach ($wc_zones as $wc_zone)
		{
			$zone_id = $wc_zone['id'];
//		print "handling $zone_id<br/>";
			$result .= Core_Html::gui_header(2, "Updating zone " . $wc_zone['zone_name']);

			foreach ($wc_zone['shipping_methods'] as $shipping){
				if (!isset($zone_times[$zone_id])) { // No zone times. Disabling.
					$result .= "No missions to zone " . $wc_zone['zone_name'] . " Disabling shipping methods<br/>";
					$args                = [];
					$args["is_enabled"]  = 0;
					$args["instance_id"] = $shipping->instance_id;
					// $args[""] = ;
					update_wp_woocommerce_shipping_zone_methods( $args );
					break;
				}
				foreach ($zone_times[$zone_id] as $date => $times) {
//				$result .= "date = $date<br/>";
					if ( strstr( $shipping->title, DateDayName( $date ) ) ) {
						$args                = [];
						$args["is_enabled"]  = 1;
						$args["instance_id"] = $shipping->instance_id;
						$args["title"]       = DateDayName( $date ) . " " . date( 'd/m/Y', strtotime( $date ) ) . ' ' . $times;
						$result .= "title: " . $args["title"] . "<br/>";
						update_wp_woocommerce_shipping_zone_methods( $args );
					}
				}
			}
			continue;
			// There are times. Update the shipping methods.

			$has_missions = false;
			if ($all_missions) {
				foreach ($all_missions as $mission_id) {
					$m       = new Mission( $mission_id );
					$result  .= Core_Html::gui_header( 3, $m->getMissionName() ) . "<br/>";
					$mission = new Mission( $mission_id );
					$date    = $mission->getDate();
					// print $date . " " . date_day_name($date);

					$shipping_ids = $mission->getShippingMethods();
					var_dump($shipping_ids);
					if ( $shipping_ids ) {
						foreach ( $shipping_ids as $zone_id => $shipping ) {
							$result .= $shipping->title . ", ";
							if ( ! strstr( $shipping->title, DateDayName( $date ) ) ) {
								continue;
							}
							//debug_var($shipping->get_data_store());
							//die(1);
							$args                = [];
							$args["is_enabled"]  = 1;
							$args["instance_id"] = $shipping->instance_id;
							$args["title"]       = DateDayName( $date ) . " " . date('d/m/Y', strtotime($date)) . ' ' . strtok( $mission->getStartTime(), ":" ) . '-' . strtok( $mission->getEndTime(), ":" );
							// $args[""] = ;
							update_wp_woocommerce_shipping_zone_methods( $args );
							$has_missions = true;
						}
						$result .= "updated mission " . Core_Html::GuiHyperlink($mission_id, AddToUrl(array( "operation" => "show_mission", "mission_id" => $mission_id))) . "<br/>";
					}
				}
			}
			if (! $has_missions) {
				$result .= "No future missions for path. Disabling shipping zones: ";
				foreach ( $wc_zone['shipping_methods'] as $shipping_method ) {
					$result              .= $shipping_method->title . ", ";
					$args["is_enabled"]  = 0;
					$args["instance_id"] = $shipping_method->instance_id;
					update_wp_woocommerce_shipping_zone_methods( $args );
				}
			}
		}
		// For debug use $result.

		return true;
	}

}