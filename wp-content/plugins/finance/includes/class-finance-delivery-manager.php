<?php


class Finance_Delivery_Manager
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

	public function init(Core_Hook_Handler $loader)
	{
		$loader->AddAction("update_shipping_methods", $this);
		$loader->AddAction("update_mission_shipping_anonymous", $this, "update_mission_shipping");
		$key = __CLASS__ . "_last_mission_update";
		// Get once a hour data from master.
		$format = "d-G";
//		FlavorLog(InfoGet($key));
		if (InfoGet($key) != current_time($format))
		{
			FinanceLog(__FUNCTION__ . " sync from master");

			if (self::sync_from_master())
				InfoUpdate($key, current_time($format));
		}

		add_action('admin_menu',array($this, 'admin_menu'));
		add_filter('delivery_args', array($this, 'delivery_args'));
	}

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			return new self();
		}
		return self::$_instance;
	}

	static function sync_from_master()
	{
		FinanceLog(__FUNCTION__);
		$m = Core_Db_MultiSite::getInstance();
		if ( $m->isMaster() ) { // if not master, get info from master.
//			MyLog("master - skipped", __FUNCTION__);
			return true;
		}

		if (! $m->UpdateFromRemote( "missions", "id", 0, "date > SUBDATE(now(), INTERVAL 1 week)")) return false;
		if (! $m->UpdateFromRemote( "mission_types")) return false;
		if (! $m->UpdateFromRemote( "woocommerce_shipping_zones", "zone_id" )) return false;
		if (! $m->UpdateFromRemote( "woocommerce_shipping_zone_methods", "instance_id" )) return false;
		if (! $m->UpdateFromRemote( "woocommerce_shipping_zone_locations", "location_id" )) return false;
		if (! $m->UpdateFromRemote( "options", "option_name", 0, "option_name like 'woocommerce_flat_rate_%_settings'", array( 'option_id' ))) return false;

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

	function admin_menu()
	{
	}

	function delivery_args($args)
	{
		array_push($args["fields"], 'has_vat');
		$args["edit_cols"]['has_vat'] = true;
		$args["header_fields"]['has_vat'] = "Vat";
		return $args;
	}
}

function delete_wp_woocommerce_shipping_zone_methods($instance_id)
{
	if ($instance_id > 0) {
		deleteWpOption( 'woocommerce_flat_rate_' . $instance_id . '_settings' );
		SqlQuery( "delete from wp_woocommerce_shipping_zone_methods where instance_id = " . $instance_id );
	} else {
		die( __FUNCTION__ . "invalid instance" );
	}
}

function deleteWpOption($option_id)
{
	SqlQuery("delete from wp_options where option_id='$option_id'");
}
// UPDATE `wp_options` SET `option_value` = 'a:8:{s:11:\"instance_id\";i:70;s:5:\"title\";s:25:\"Thursday 16/04/2020 14-18\";s:10:\"tax_status\";s:7:\"taxable\";s:4:\"cost\";s:3:\"411\";s:14:\"class_cost_154\";s:0:\"\";s:14:\"class_cost_187\";s:0:\"\";s:13:\"no_class_cost\";s:0:\"\";s:4:\"type\";s:5:\"class\";}', `autoload` = 'yes' WHERE `option_name` = 'woocommerce_flat_rate_70_settings'
