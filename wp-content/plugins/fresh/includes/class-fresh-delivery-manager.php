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

	public function init()
	{
		AddAction("delivery_delete", array(__CLASS__, "delete"));
	}

	static public function delete()
	{
		$id = GetParam("delivery_id", true);
		$d = new Fresh_Delivery( $id );
		$client = $d->getCustomerId();
		if (get_user_id() != $client and ! im_user_can("delete_shop_orders"))
			die("no permission");

		$d->Delete();

		Finance::delete_transaction( $id );
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
		return array( 'delivery_manager_update' => array( 'Fresh_Delivery_Manager::update_shipping_methods',    null ),
			'fresh_deliveries' => array('Fresh_Delivery_Manager::show_shipping_methods', null));
	}

	static function update_shipping_methods()
	{
		print 1/0;
		return;
		if (! self::create_missions()) return false;
		return self::do_update_shipping_methods();
	}

	static function create_missions($path_ids = null, $forward_week = 0)
	{
		$result = "";
		if (! $path_ids) $path_ids = sql_query_array_scalar("select distinct id from im_paths");
		foreach ($path_ids as $path_id){
//			print "handling $path_id<br/>";
			$result .= Core_Html::gui_header(1, "Create missions");
			if (! Mission::CreateFromPath($path_id, 8)) return false;
		}
		return true;
	}

	function stop_accept()
	{
		$missions = sql_query_array_scalar("select id from im_missions where date = '" .
		                                   date("Y-m-d", strtotime('tomorrow')) . "'");

		foreach ($missions as $mission_id){
			$m = new Mission($mission_id);
			print "mission $mission_id stop<br/>";
			$m->stopAccept();
		}
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
		$instances_updated = [];

		// update shipping method instances.
		$paths = Fresh_Path::getAll();
		$zone_times = []; // [zone][date] = times
		foreach ($paths as $path_id)
		{
			$path = new Fresh_path($path_id);
			$result .= Core_Html::gui_header(1, "working on path $path_id");
			$mission_id = sql_query_single_scalar("select min(id) from im_missions where path_code = $path_id and date > curdate() and accepting = 1");
			if (! $mission_id) continue;
			$m = new Mission($mission_id);

			$zones_info = $path->getZones();
			foreach ($zones_info as $zone_info){
				$date = $m->getDate();
				$start = strtok($zone_info, "-");
				$end = strtok(",");
				$instance_id = strtok(null);
				$result .= self::update_shipping_method($instance_id, $date, $start, $end);

				array_push($instances_updated, $instance_id);
			}
		}

		// Disable the others.
		$result .= Core_Html::gui_header(1, "disabling others");
		$wc_zones = WC_Shipping_Zones::get_zones();

		$result .= Core_Html::gui_header(1, "checking not available");
		foreach ($wc_zones as $wc_zone) {
			$result .= Core_Html::gui_header(2, $wc_zone['zone_name']);
			foreach ( $wc_zone['shipping_methods'] as $shipping ) {
				$result .= "<br/>checking " . $shipping->title;
				if ($shipping->id == 'local_pickup') {
					$result .= " local_pickup unchanged";
					continue;
				}
				if ( ! in_array( $shipping->instance_id, $instances_updated ) ) {
					$result .= "disable ";
					$args = [];
					$args["is_enabled"]  = 0;
					$args["instance_id"] = $shipping->instance_id;
					$args["title"]       = $shipping->title;
					updateWp_woocommerce_shipping_zone_methods( $args );
				}
			}
		}
		return $result;
	}

	static function update_shipping_method($instance_id, $date, $start, $end)
	{
		$args                = [];
		$args["is_enabled"]  = 1;
		$args["instance_id"] = $instance_id;
		$args["title"]       = DateDayName( $date ) . " " . date('d/m/Y', strtotime($date)) . ' ' . $start . "-". $end;
//		$result .= "$start $end $instance_id<br/>";
		if (updateWp_woocommerce_shipping_zone_methods( $args ))
			return $args["title"];
		else
			return "failed";
	}

	static function show_shipping_methods()
	{
		$result = "<table><tr><td>Zone</td><td>Shipping method</td><td>Cost</td></tr>";

		$wc_zones = WC_Shipping_Zones::get_zones();

		foreach ($wc_zones as $wc_zone)
		{
			$first = true;
			foreach ( $wc_zone['shipping_methods'] as $shipping ) {
				$instance_id = $shipping->instance_id;
				if (get_class($shipping) == 'WC_Shipping_Local_Pickup'
				or !$shipping->is_enabled()) continue;

				$result .= "<tr>";
				$zone_name = $wc_zone['zone_name'];
				if ($first) {
					 $result .= "<td rowspan='" . self::count_without_pickup($wc_zone) . "'>". $zone_name . "</td>";
					 $first = false;
				}
				$result .= "<td>" . Core_Html::GuiHyperlink($shipping->title, get_site_url() . "/wp-admin/admin.php?page=wc-settings&tab=shipping&instance_id={$instance_id}") . "</td>";
				preg_match_all('/\d{2}\/\d{2}\/\d{4}/', $shipping->title,$matches);
				$date = str_replace('/', '-', $matches[0][0]);
//				print $matches[0][0] . " " . strtotime($date) . "<br/>";

				$result .= "<td>" . self::get_shipping_cost($instance_id) . "</td>";
			}
			$result .= "</tr>";
		}
		$result .= "</table>";

		return $result;
	}
	static function count_without_pickup($wc_zone){
		$count = 0;
		foreach ( $wc_zone['shipping_methods'] as $shipping )
			if (get_class($shipping) != 'WC_Shipping_Local_Pickup' and
			$shipping->is_enabled())
				$count ++;
		return $count;
	}
	static function get_shipping_cost($instance_id)
	{
		$option = get_wp_option("woocommerce_flat_rate_{$instance_id}_settings");
		if (isset($option["cost"])) return $option["cost"];
		return "not found";
	}
}



//static function do_update_shipping_methods()
//{
//	$result = "";
//
//	// Collect time from active missions into $zone_times;
//	$paths = Fresh_Path::getAll();
//	$zone_times = []; // [zone][date] = times
//	foreach ($paths as $path)
//	{
//		$missions = sql_query_array_scalar("select id from im_missions where path_code = $path and date > curdate() and accepting = 1");
//		foreach ($missions as $mission_id){
//			$m = new Mission($mission_id);
//			$date = $m->getDate();
//			$mission_zone_times = $m->getZoneTimes();
////				$result .= "<br/> reading $mission_id $date ";
//
//			foreach($mission_zone_times as $zone_id => $zone_time){
////					$result .= $zone_id .":" . $zone_time . " ";
//				if (! isset($zone_times[$zone_id])) $zone_times[$zone_id] = [];
//				if (! isset($zone_times[$zone_id][$date])) $zone_times[$zone_id][$date] = [];
//				$zone_times[$zone_id][$date] = $zone_time;
//			}
//		}
//	}
//
//
//	// Foreach zone
//	///// foreach method
//	///////   if not times -> disable method.
//	////////  else enable + change displayed times.
//	$result .= Core_Html::gui_header(1, "Updating all shipping methods");
//	$wc_zones = WC_Shipping_Zones::get_zones();
//
//	foreach ($wc_zones as $wc_zone)
//	{
//		$zone_id = $wc_zone['id'];
//		$result .= Core_Html::gui_header(2, "Updating zone " . $wc_zone['zone_name']);
//
//		foreach ($wc_zone['shipping_methods'] as $shipping){
//			// var_dump($zone_times[$zone_id]); print "<br/";
//			if (!isset($zone_times[$zone_id])) { // No zone times. Disabling.
//				$result .= "No missions to zone " . $wc_zone['zone_name'] . " Disabling shipping methods<br/>";
//				$args                = [];
//				$args["is_enabled"]  = 0;
//				$args["instance_id"] = $shipping->instance_id;
//				// $args[""] = ;
//				update_wp_woocommerce_shipping_zone_methods( $args );
//				break;
//			}
//			foreach ($zone_times[$zone_id] as $date => $times) {
//				$result .= "ship = " . $shipping->title . " ddn=" . DateDayName($date) . "<br/>";
//				if ( strstr( $shipping->title, DateDayName( $date ) ) ) {
//					$args                = [];
//					$args["is_enabled"]  = 1;
//					$args["instance_id"] = $shipping->instance_id;
//					$args["title"]       = DateDayName( $date ) . " " . date( 'd/m/Y', strtotime( $date ) ) . ' ' . $times;
//					$result .= "title: " . $args["title"] . "<br/>";
//					update_wp_woocommerce_shipping_zone_methods( $args );
//				}
//			}
//		}
//		continue;
//
//		// There are times. Update the shipping methods.
//		$has_missions = false;
//		if ($all_missions) {
//			foreach ($all_missions as $mission_id) {
//				$m       = new Mission( $mission_id );
//				$result  .= Core_Html::gui_header( 3, $m->getMissionName() ) . "<br/>";
//				$mission = new Mission( $mission_id );
//				$date    = $mission->getDate();
//				// print $date . " " . date_day_name($date);
//
//				$shipping_ids = $mission->getShippingMethods();
//				var_dump($shipping_ids);
//				if ( $shipping_ids ) {
//					foreach ( $shipping_ids as $zone_id => $shipping ) {
//						$result .= $shipping->title . ", ";
//						if ( ! strstr( $shipping->title, DateDayName( $date ) ) ) {
//							continue;
//						}
//						//debug_var($shipping->get_data_store());
//						//die(1);
//						$args                = [];
//						$args["is_enabled"]  = 1;
//						$args["instance_id"] = $shipping->instance_id;
//						$args["title"]       = DateDayName( $date ) . " " . date('d/m/Y', strtotime($date)) . ' ' . strtok( $mission->getStartTime(), ":" ) . '-' . strtok( $mission->getEndTime(), ":" );
//						// $args[""] = ;
//						update_wp_woocommerce_shipping_zone_methods( $args );
//						$has_missions = true;
//					}
//					$result .= "updated mission " . Core_Html::GuiHyperlink($mission_id, AddToUrl(array( "operation" => "show_mission", "mission_id" => $mission_id))) . "<br/>";
//				}
//			}
//		}
//		if (! $has_missions) {
//			$result .= "No future missions for path. Disabling shipping zones: ";
//			foreach ( $wc_zone['shipping_methods'] as $shipping_method ) {
//				$result              .= $shipping_method->title . ", ";
//				$args["is_enabled"]  = 0;
//				$args["instance_id"] = $shipping_method->instance_id;
//				update_wp_woocommerce_shipping_zone_methods( $args );
//			}
//		}
//	}
//	// For debug use $result.
//	print $result;
//
//	return true;
//}

function delete_wp_woocommerce_shipping_zone_methods($instance_id)
{
	if ($instance_id > 0)
		deleteWpPption( 'woocommerce_flat_rate_' . $instance_id . '_settings');
}

function deleteWpPption($option_id)
{
	sql_query("delete from wp_options where option_id='$option_id'");
}

function updateWp_woocommerce_shipping_zone_methods($args) {
//	$ignore_list = array("id");
	$instance_id = GetArg( $args, "instance_id", null );
	if ( ! ( $instance_id > 0 ) ) {
		print __ ( "Error: #R1 invalid instance_id" );
		return false;
	}

// Coundn't find out how to do that....
//	$zone_id = GetArg($args, "zone_id", null);
//	if (! ($zone_id > 0)) die ("Error: #R1 invalid id");
//	$enable = GetArg($args, "is_enabled", null);
	//	$z = WC_Shipping_Zones::get_zone( $zone_id );
//	$methods = $z->get_shipping_methods();

//	$methods[$instance_id]->enabled = $enable;

//	$methods[$instance_id]->shipping_zone_methods_save_changes();
//
//
//	$z1 = WC_Shipping_Zones::get_zone( $zone_id );
//	$methods1 = $z1->get_shipping_methods();

	// Updating directly to db. and prepare array to wp_options
	$options       = [];
	$sql           = "update wp_woocommerce_shipping_zone_methods set ";
	$table_list    = array( "is_enabled", "method_order" ); // Stored in the wp_woocommerce_shipping_zone_methods table
	$update_table  = false;
	$update_option = false;
	$option_id     = 'woocommerce_flat_rate_' . $instance_id . '_settings';
	$options       = get_wp_option( $option_id );

	foreach ( $args as $k => $v ) {
		if ( ! in_array( $k, $table_list ) ) {
			$options[ $k ] = $v;
			$update_option = true;
			continue;
		}
		$sql          .= $k . "=" . QuoteText( $v ) . ", ";
		$update_table = true;
	}
	if ( $update_table ) {
		$sql = rtrim( $sql, ", " );
		$sql .= " where instance_id = " . $instance_id;
		if ( ! sql_query( $sql ) ) {
			return false;
		}
	}

	if ( $update_option ) {
		update_wp_option( 'woocommerce_flat_rate_' . $instance_id . '_settings', $options );
	}
}
