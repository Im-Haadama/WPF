<?php


class Fresh_Client {

	private $user_id;
	private $user;

	/**
	 * Fresh_Client constructor.
	 *
	 * @param $user_id
	 */
	public function __construct( $user_id = 0 ) {
		if (! $user_id) {
			if (! get_user_id())
			$user_id = 0;
		}
		$this->user_id = $user_id;
	}

	/**
	 * @return int
	 */
	public function getUserId(): int {
		return $this->user_id;
	}

	static public function init_hooks()
	{
		AddAction("set_client_type", __CLASS__ . "::set_client_type_wrap");
		add_filter('woocommerce_new_customer_data', array(__CLASS__, 'wc_assign_custom_role'), 10, 1);
		add_filter( 'woocommerce_shop_manager_editable_roles', array(__CLASS__, 'shop_manager_role_edit_capabilities' ));
	}

	static function shop_manager_role_edit_capabilities( $roles ) {
		$roles[] = 'subscriber';
		return $roles;
	}

	static 	function wc_assign_custom_role($args) {
		$args['role'] = 'subscriber';

		return $args;
	}

	static function set_client_type_wrap()
	{
		$id   = $_GET["id"];
		$type = $_GET["type"];
		return self::set_client_type( $id, $type );
	}

	static function set_client_type($id, $type)
	{
		if ( $type == 0 ) {
			delete_user_meta( $id, "_client_type" );

			return true;
		}
		$meta = sql_query_single_scalar( "select type from im_client_types where id = " . $type );
		// print "meta: " . $meta . "<br/>";
		return update_user_meta( $id, "_client_type", $meta );
	}

	public function balance() {
		$sql = 'select sum(transaction_amount) '
		       . ' from im_client_accounts '
		       . ' where client_id = ' . $this->user_id;

		return round( sql_query_single_scalar( $sql ), 2 );
	}

	function get_payment_method( ) {
		$m = get_user_meta( $this->user_id, "payment_method", true );
		if ( $m ) {
			return $m;
		}

		$p = sql_query_single_scalar( "SELECT id FROM im_payments WHERE `default` = 1" );
		if ( $p ) {
			return $p;
		} else {
			return "לא נבחר אמצעי ברירת מחדל";
		}
	}

	function get_payment_method_name()
	{
		return (Finance_Payment_Methods::get_payment_method_name(self::get_payment_method()));
	}

	function customer_type( ) {
		$key = get_user_meta( $this->user_id, '_client_type', true );

		if ( is_null( $key ) ) {
			return 0;
		}

		return $key;
	}

	function add_transaction( $date, $amount, $ref, $type ) {
		$sql = "INSERT INTO im_client_accounts (client_id, date, transaction_amount, transaction_method, transaction_ref) "
		       . "VALUES (" . $this->user_id . ", \"" . $date . "\", " . $amount . ", \"" . $type . "\", " . $ref . ")";

		MyLog( $sql, "account_add_transaction" );
		sql_query( $sql );
	}

	function update_transaction( $total, $delivery_id) {
		$sql = "UPDATE im_client_accounts SET transaction_amount = " . $total .
		       " WHERE transaction_ref = " . $delivery_id . " and client_id = " . $this->user_id;

		MyLog( $sql, "account_update_transaction" );
		sql_query( $sql );
	}

	function get_customer_email()
	{
		return self::get_user()->user_email;
	}

	function get_phone_number()
	{
		return get_post_meta( self::get_last_order( ), '_billing_phone', true );
	}

	function getName()
	{
//		var_dump(self::get_user());
		// TODO: after fixing the error with invoice4u, remove this:
		self::set_default_display_name();
		return self::get_user()->display_name;
	}

	function get_last_order( ) {
		return sql_query_single_scalar( " SELECT max(meta.post_id) " .
		                                " FROM `wp_posts` posts, wp_postmeta meta" .
		                                " where meta.meta_key = '_customer_user'" .
		                                " and meta.meta_value = " . $this->user_id .
		                                " and meta.post_id = posts.ID");
	}

	function getZone()
	{
		$customer = new WC_Customer($this->user_id);
		$country  = $customer->get_shipping_country();
		$postcode = $customer->get_shipping_postcode();

		return WC_Shipping_Zones::get_zone_matching_package( array(
			'destination' => array(
				'country'  => $country,
				'state'    => '',
				'postcode' => $postcode,
			),
		) );

	}

	private function get_user()
	{
		if (! $this->user) {
			$this->user = get_user_by('id', $this->user_id);
		}
		if (! $this->user) return null;
		return $this->user;
	}

	static 	function gui_select_client( $id, $value, $args = null )
	{
		if ( ! $id ) {
			$id = "client_select";
		}

		$events = GetArg($args, "events", null);
		$active_days = GetArg($args, "active_days", null);
		$new = GetArg($args, "new", false);

		if ( $active_days > 0 ) {
			$sql_where = "where id in (select client_id from im_client_accounts where DATEDIFF(now(), date) < " . $active_days;
			if ( $new ) {
				$sql_where .= " union select id from wp_users where DATEDIFF(now(), user_registered) < 3";
			}
			$sql_where .= ")";
			$sql_where .= "order by 2";
		} else {
			$sql_where = "where 1 order by 2";
		}

//		$select_args = array("name" => "client_displayname(id)", "include_id" => 1, "where"=> $sql_where, "events" => $events, "value"=>$value, "datalist" => 1);
		return Core_Html::GuiAutoList( $id, "users", $args);
	}

	function set_default_display_name( ) {
		// $user = get_userdata( $user_id );
		$user = get_user_by( "id", $this->user_id );

		$name = $user->user_firstname . " " . $user->user_lastname;;
		// print $this->user_id . " " . $name;
		if ( strlen( $name ) < 3 ) {
			$name = get_user_meta( $this->user_id, 'billing_first_name', true ) . " " .
			        get_user_meta( $this->user_id, 'billing_last_name', true );
			// print "user meta name " . $name;

		}
		$args = array(
			'ID'           => $this->user_id,
			'display_name' => $name,
			'nickname'     => $name
		);

		// print "<br/>";
		if ( strlen( $name ) > 3 ) {
			wp_update_user( $args );
		}
	}


}

