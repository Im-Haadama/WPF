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

	public function init($loader)
	{
		$loader->AddAction("update_shipping_methods", __CLASS__ . "::update_shipping_methods");
		$loader->AddAction("update_shipping_methods_anonymous", __CLASS__ . "::update_shipping_methods");
		add_action('admin_menu',array($this, 'admin_menu'));
		add_action('admin_notices', array($this, 'delivered_previous_days'));
		add_filter('delivery_args', array($this, 'delivery_args'));
	}

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			return new self();
		}
		return self::$_instance;
	}

	static function update_shipping_methods($result = null)
	{
		self::sync_from_master();

		// Otherwise - master - update.
		$result .= "Updating<br/>";
		$sql = "select * from wp_woocommerce_shipping_zone_methods";
		$sql_result = SqlQuery($sql);
		while ($row = SqlFetchAssoc($sql_result)) {
			$instance_id = $row['instance_id'];
			self::update_shipping_method($instance_id);
		}
		return $result;
	}

	static function sync_from_master()
	{
		MyLog(__METHOD__, __FUNCTION__);
		$m = Core_Db_MultiSite::getInstance();
		if ( $m->isMaster() ) { // if not master, get info from master.
			MyLog("master - skipped", __FUNCTION__);
			return true;
		}

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

	static function update_shipping_method($instance_id) //, $date, $start, $end, $price = 0)
	{
		$args                = [];
		$args["is_enabled"]  = 1;
		$args["instance_id"] = $instance_id;
		$method_info = SqlQuerySingleAssoc("select mission_code from wp_woocommerce_shipping_zone_methods where instance_id = $instance_id");
		$mission_type = $method_info['mission_code'];
		if ($mission_type)
			$week_day = SqlQuerySingleScalar("select week_day from im_mission_types where id = $mission_type");
		else
			$week_day = 2;

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
			if ( ! SqlQuery( $sql ) ) {
				return false;
			}
		}

		if ( $update_option ) {
			return update_wp_option( 'woocommerce_flat_rate_' . $instance_id . '_settings', $options );
		}
		return false;
	}

	function admin_menu()
	{
	}

	function delivered_previous_days() {
		$debug = 0;
		$result = "Marking orders of yesterday delivered:\n";
		$ids    = SqlQueryArrayScalar( "SELECT * FROM `wp_posts` 
WHERE (post_status = 'wc-awaiting-shipment' or post_status = 'wc-processing') 
and curdate() > order_mission_date(id)" );

		$message = "";
		if ( ! $ids or ! count( $ids ) ) {
			if ($debug) MyLog("No del found");
			return;
		}

		foreach ( $ids as $id ) {
			$order = new Finance_Order( $id );
			if ($order->getStatus() == 'wc-processing') continue;
			FinanceLog("adding $id");
			$order->delivered( $message );
			$result .= "Order $id $message\n";
		}

		if ( strlen( $result ) > 39) {
			$class   = 'notice notice-info';

			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $result ) );
		}
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
