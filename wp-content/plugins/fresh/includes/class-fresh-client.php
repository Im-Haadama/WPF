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
//	    MyLog(__FUNCTION__ . $user_id);
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
		add_action( 'admin_menu', __CLASS__ . '::admin_menu' );

		AddAction("set_client_type", __CLASS__ . "::set_client_type_wrap");
		add_filter('woocommerce_new_customer_data', array(__CLASS__, 'wc_assign_custom_role'), 10, 1);
		add_filter( 'woocommerce_shop_manager_editable_roles', array(__CLASS__, 'shop_manager_role_edit_capabilities' ));

		// Admin user customer type
		add_action( 'show_user_profile', array(__CLASS__, 'extra_user_profile_fields') );
		add_action( 'edit_user_profile', array(__CLASS__, 'extra_user_profile_fields' ));
		add_action( 'personal_options_update', array(__CLASS__, 'save_extra_user_profile_fields') );
		add_action( 'edit_user_profile_update', array(__CLASS__, 'save_extra_user_profile_fields') );

		Core_Gem::AddTable("client_types");
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
		$meta = SqlQuerySingleScalar( "select type from im_client_types where id = " . $type );
		// print "meta: " . $meta . "<br/>";
		return update_user_meta( $id, "_client_type", $meta );
	}

	public function balance() {
		$sql = 'select sum(transaction_amount) '
		       . ' from im_client_accounts '
		       . ' where client_id = ' . $this->user_id;

		return round( SqlQuerySingleScalar( $sql ), 2 );
	}

	function get_payment_method( ) {
		$m = get_user_meta( $this->user_id, "payment_method", true );
		if ( $m ) {
			return $m;
		}

		$p = SqlQuerySingleScalar( "SELECT id FROM im_payments WHERE `default` = 1" );
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
	    MyLog(__FUNCTION__ . " " . $this->user_id);
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
		return SqlQuery( $sql );
	}

	function update_transaction( $total, $delivery_id) {
		$sql = "UPDATE im_client_accounts SET transaction_amount = " . $total .
		       " WHERE transaction_ref = " . $delivery_id . " and client_id = " . $this->user_id;

		MyLog( $sql, "account_update_transaction" );
		SqlQuery( $sql );
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
		return SqlQuerySingleScalar( " SELECT max(meta.post_id) " .
		                             " FROM `wp_posts` posts, wp_postmeta meta" .
		                             " where meta.meta_key = '_customer_user'" .
		                             " and meta.meta_value = " . $this->user_id .
		                             " and meta.post_id = posts.ID");
	}

	function getInvoiceUserId($create = false)
    {
	    $invoice = Finance_Invoice4u::getInstance();
	    if (! $invoice) return null;

//	    // Try the cache
		$id = get_user_meta( $this->user_id, 'invoice_id', 1 );
		if ($id) return $id;

		if ($u = self::getInvoiceUser($create)) {
		    $id = $u->ID;

		    // Save cache
			update_user_meta( $this->user_id, 'invoice_id', $id );
        }
		return $id;
    }

	function getInvoiceUser($create = true)
	{
		// Invoice is alive?
        $invoice = Finance::Invoice4uConnect();
		if (! $invoice) return null;

//	    // Try the cache
		$id = get_user_meta( $this->user_id, 'invoice_id', 1 );
		if ($id) return $invoice->GetCustomerByID($id);

        // Try to get by email.
        $email = $this->get_customer_email();
        $c = $invoice->GetCustomerByEmail($email);
        if (is_array($c)) foreach ($c as $customer) {
            if ($customer->Email == $email) return $customer;
        }
        if ($c) return $c;

        // Try to get by name (is unique in invoice4u);
		$c = $invoice->GetCustomerByName($this->getName());
		if ($c) return $c;

        if (! $create) return null;
        MyLog("Creating user " . $this->getName() . " " . $this->get_customer_email() . " ". $this->get_phone_number());

        // Create the user.
        if ($id = $invoice->CreateUser($this->getName(), $this->get_customer_email(), $this->get_phone_number())){
	        update_user_meta( $this->user_id, 'invoice_id', $id );
	        return $invoice->GetCustomerByEmail($this->get_customer_email());
        }
        return null;
	}

	function createInvoiceUser()
	{
		IF ($I = Finance_Invoice4u::getInstance())
		    return $I->CreateUser($this->getName(), $this->get_customer_email(), $this->get_phone_number());
		return null;
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

		if (! $user) return "user $this->user_id not found";
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

	static function extra_user_profile_fields( $user ) {
		if (! is_admin_user()) return "";
		$u = new Fresh_Client($user->ID);
		?>
		<h3><?php _e("Extra profile information", "blank"); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="address"><?php _e("Address"); ?></label></th>
                <td>
                    <input type="text" name="address_lalal" id="address" value="<?php echo esc_attr( get_the_author_meta( 'address', $user->ID ) ); ?>" class="regular-text" /><br />
                    <span class="description"><?php _e("Please enter your address."); ?></span>
                </td>
            </tr>
            <tr>
                <th><label for="city"><?php _e("City"); ?></label></th>
                <td>
                    <input type="text" name="city" id="city" value="<?php echo esc_attr( get_the_author_meta( 'city', $user->ID ) ); ?>" class="regular-text" /><br />
                    <span class="description"><?php _e("Please enter your city."); ?></span>
                </td>
            </tr>
            <tr>
                <th><label for="postalcode"><?php _e("Postal Code"); ?></label></th>
                <td>
                    <input type="text" name="postalcode" id="postalcode" value="<?php echo esc_attr( get_the_author_meta( 'postalcode', $user->ID ) ); ?>" class="regular-text" /><br />
                    <span class="description"><?php _e("Please enter your postal code."); ?></span>
                </td>
            </tr>
        </table>
		<table class="form-table">
			<tr>
				<th><label for="customer_type"><?php _e("Customer type"); ?></label></th>
				<td>
					<?php
					print Fresh_Client::gui_select_client_type("customer_type", $u->customer_type());
					?><br/>
					<span class="description"><?php _e("Please select customer type."); ?></span>
				</td>
			</tr>
		</table>
	<?php }

	static function admin_menu() {
		$menu = new Core_Admin_Menu();

		$menu->AddSubMenu( "users.php", "edit_shop_orders",
			array( 'page_title' => 'Client types', 'function' => array( __CLASS__, 'admin_page' ) ) );

	}

	static function admin_page()
	{
		print Core_Html::gui_header( 2, "מחירונים" );

		$args = [];
		$args['post_file'] = Fresh::getPost();
		$args['edit'] = true;
		print Core_Gem::GemTable("client_types", $args);
            // "SELECT rate, dry_rate AS מרווח, type AS 'שם מחירון' FROM im_client_types");

	}

	function client_types()
	{
		print Core_Html::gui_header( 1, "שיוך לקוחות למחירון" );

		$sql = "SELECT user_id, meta_value FROM wp_usermeta WHERE meta_key = '_client_type'";

		$result = SqlQuery( $sql );

		$table = array( array( "מזהה", "לקוח", "מחירון" ) );

		while ( $row = SqlFetchRow( $result ) ) {
//    print $row[0] . " " . $row[1] . "<br/>";
			$user_id = $row[0];

			$client_type_id = SqlQuerySingleScalar( "SELECT id FROM im_client_types WHERE type = '" . $row[1] . "'" );
			array_push( $table, array(
				$user_id,
				GetUserName( $user_id ),
				gui_select_client_type( "select_type_" . $user_id,
					$client_type_id, "onchange=update_client_type(" . $user_id . ")" )
			) );
		}

		print Core_Html::gui_table_args( $table );

		print Core_Html::gui_header( 2, "הוסף שיוך" );

		$args = [];
		$args["post_file"] = Fresh::getPost();
		print Core_Html::gui_table_args( array(
			array( "בחר לקוח", self::gui_select_client("client_select", null, $args) ),
			array(
				"בחר מחירון",
				gui_select_client_type( "select_type_new", 1 )
			)
		) );

		print Core_Html::GuiButton( "btn_save", "שמור", array("action"=>"add_client_type()") );
		print Core_Html::gui_header( 2, "מחירונים" );

		print Core_Html::GuiTableContent("table", "SELECT id, rate, dry_rate AS מרווח, type AS 'שם מחירון' FROM im_client_types");
	}

	static function save_extra_user_profile_fields( $user_id ) {
	    $type = $_POST['customer_type'];
		if ( !current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}
		update_user_meta( $user_id, '_client_type', $type );
	}

	static function gui_select_client_type( $id, $value, $args = null )
    {
        if (! $args) $args = [];
        MyLog("value =$value");
        $args["selected"] = $value;
        $args['more_values'] = array(array( "id" => 0, "type" => "רגיל" ));
        $args["name"] = "type";

	    return Core_Html::GuiSelectTable($id, "client_types", $args);
    }
}
