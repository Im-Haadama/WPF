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
		AddAction("update_shipping_methods", __CLASS__ . "::update_shipping_methods");
		AddAction("update_shipping_methods_anonymous", __CLASS__ . "::update_shipping_methods");
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

	static function update_shipping_methods($result = null) {
		$result .= "Updating<br/>";
		$sql = "select * from wp_woocommerce_shipping_zone_methods";
		$sql_result = sql_query($sql);
		while ($row = sql_fetch_assoc($sql_result)) {
			$instance_id = $row['instance_id'];
			self::update_shipping_method($instance_id);
		}
		return $result;
	}

	static function stop_accept()
	{
		// Loop on on methods.
		// Delete dated at tomorrow and before.
		$result = "";
		$wc_zones = WC_Shipping_Zones::get_zones();
		foreach ($wc_zones as $wc_zone) {
			$result .= Core_Html::gui_header(2, $wc_zone['zone_name']);
			foreach ( $wc_zone['shipping_methods'] as $shipping ) {
				$result .= "<br/>checking " . $shipping->title;
				if ($shipping->id == 'local_pickup') {
					$result .= " local_pickup unchanged";
					continue;
				}
				$date = null;
				if (preg_match('/[0-9]+\-[0-9]+\-[0-9]+/', $shipping->title, $date) or
				    preg_match('/[0-9]+\/[0-9]+\/[0-9]+/', $shipping->title, $date)) { // Date based
					$s_date = strtotime($date[0]);
					$result .=  "<br/>date = " . $date[0] . " " . $s_date;
					if (! $s_date or ($s_date < strtotime('tomorrow'))) {
						$result .=  " deleting";
						self::delete_shipping_method( $shipping->instance_id );
					}
				} else {
					$result .=  " no date ";

					self::delete_shipping_method($shipping->instance_id);
				}

//				$result .=  "<br/>";
			}
		}
		print $result;

//		$missions = sql_query_array_scalar("select id from im_missions where date = '" .
//		                                   date("Y-m-d", strtotime('tomorrow')) . "'");
//
//		foreach ($missions as $mission_id){
//			$m = new Mission($mission_id);
//			print "mission $mission_id stop<br/>";
//			$m->stopAccept();
//		}
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

	static function delete_shipping_method($instance_id)
	{
		if (! ($instance_id > 0))
		{
			print "bad instance id: $instance_id<br/>";
			return false;
		}
		$option_id     = 'woocommerce_flat_rate_' . $instance_id . '_settings';
		sql_query("delete from wp_woocommerce_shipping_zone_methods where instance_id = $instance_id");
		delete_wp_option( $option_id );
		sql_query("delete from im_path_shipments where instance = $instance_id");

	}

	static private function do_update_shipping_methods()
	{
		return;
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
					self::update_wp_woocommerce_shipping_zone_methods( $args );
				}
			}
		}
		return $result;
	}

	static function update_shipping_method($instance_id) //, $date, $start, $end, $price = 0)
	{
		$args                = [];
		$args["is_enabled"]  = 1;
		$args["instance_id"] = $instance_id;
		$week_day = sql_query_single_scalar("select week_day from wp_woocommerce_shipping_zone_methods where instance_id = $instance_id");
		if (! $week_day) return false;
		$start = "13";
		$end = "18";
		$date = next_weekday($week_day);
		$args["title"]       = DateDayName( $date ) . " " . date('d-m-Y', strtotime($date)) . ' ' . $start . "-". $end;

		return self::update_woocommerce_shipping_zone_methods($args);

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

	static function update_woocommerce_shipping_zone_methods($args) {
		$instance_id = GetArg( $args, "instance_id", null );
		if ( ! ( $instance_id > 0 ) ) {
			print __ ( "Error: #R1 invalid instance_id" );
			return false;
		}

		// Updating directly to db. and prepare array to wp_options
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
			return update_wp_option( 'woocommerce_flat_rate_' . $instance_id . '_settings', $options );
		}
		return false;
	}

}

function delete_wp_woocommerce_shipping_zone_methods($instance_id)
{
	if ($instance_id > 0) {
		deleteWpOption( 'woocommerce_flat_rate_' . $instance_id . '_settings' );
		sql_query( "delete from wp_woocommerce_shipping_zone_methods where instance_id = " . $instance_id );
	} else {
		die( __FUNCTION__ . "invalid instance" );
	}
}

function deleteWpOption($option_id)
{
	sql_query("delete from wp_options where option_id='$option_id'");
}

// UPDATE `wp_options` SET `option_value` = 'a:8:{s:11:\"instance_id\";i:70;s:5:\"title\";s:25:\"Thursday 16/04/2020 14-18\";s:10:\"tax_status\";s:7:\"taxable\";s:4:\"cost\";s:3:\"411\";s:14:\"class_cost_154\";s:0:\"\";s:14:\"class_cost_187\";s:0:\"\";s:13:\"no_class_cost\";s:0:\"\";s:4:\"type\";s:5:\"class\";}', `autoload` = 'yes' WHERE `option_name` = 'woocommerce_flat_rate_70_settings'

