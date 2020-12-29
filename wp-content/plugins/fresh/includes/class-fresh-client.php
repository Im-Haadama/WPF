<?php

require_once(ABSPATH . 'wp-includes/pluggable.php');

class Fresh_Client extends  Finance_Client {

	/**
	 * Fresh_Client constructor.
	 *
	 * @param $user_id
	 */
//	public function __construct( $user_id = 0 ) {
//		if (! $user_id) {
//			if (! get_user_id())
//			$user_id = 0;
//		}
//		$this->user_id = $user_id;
//	}

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

		Core_Gem::getInstance()->AddTable("client_types");
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

	function get_payment_method_name()
	{
		return (Finance_Payment_Methods::get_payment_method_name(self::get_payment_method()));
	}

	function customer_type( ) {
		$key = get_user_meta( $this->getUserId(), '_client_type', true );
//		MyLog(__FUNCTION__ . " " . $this->user_id . " $key");

		if ( is_null( $key ) ) {
			return 0;
		}

		return $key;
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

	static function extra_user_profile_fields( $user ) {
		if (! is_shop_manager()) return "";
		$u = new Fresh_Client($user->ID);
				?>
		<h3><?php _e("Extra profile information", "blank"); ?></h3>
		<table class="form-table">
            <th><label for="preference">העדפות</label></th>

            <td>
                <input type="text" name="preference" id="preference"
                       value="<?php echo esc_attr( get_the_author_meta( 'preference', $user->ID ) ); ?>"
                       class="regular-text"/><br/>
                <span class="description">הכנס העדפות משתמש.</span>
            </td>

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
		$menu = Core_Admin_Menu::instance();

		$menu->AddSubMenu( "users.php", "edit_shop_orders",
			array( 'page_title' => 'Client types', 'function' => array( __CLASS__, 'admin_page' ) ) );

	}

	static function admin_page()
	{
		print Core_Html::GuiHeader( 2, "מחירונים" );

		$args = [];
		$args['post_file'] = Fresh::Post();
		$args['edit'] = true;
		print Core_Gem::GemTable("client_types", $args);
            // "SELECT rate, dry_rate AS מרווח, type AS 'שם מחירון' FROM im_client_types");

	}

	function client_types()
	{
		print Core_Html::GuiHeader( 1, "שיוך לקוחות למחירון" );

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

		print Core_Html::GuiHeader( 2, "הוסף שיוך" );

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
		print Core_Html::GuiHeader( 2, "מחירונים" );

		print Core_Html::GuiTableContent("table", "SELECT id, rate, dry_rate AS מרווח, type AS 'שם מחירון' FROM im_client_types");
	}

	static function save_extra_user_profile_fields( $user_id ) {
		if ( !current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}
		update_user_meta( $user_id, '_client_type', $_POST['customer_type'] );
		update_usermeta( $user_id, 'preference', $_POST['preference'] );

	}

	static function gui_select_client_type( $id, $value, $args = null )
    {
        if (! $args) $args = [];
        MyLog("value =$value");
        $args["selected"] = $value;
//        $args['more_values'] = array(array( "id" => 0, "type" => "רגיל" ));
//        $args["name"] = "name";

	    return Core_Html::GuiSelectTable($id, "client_types", $args);
    }
}

function is_shop_manager() {
    return true;
	$user    = new WP_User( wp_get_current_user() );
	if ( ! empty( $user->roles ) && is_array( $user->roles ) )
		foreach ( $user->roles as $role ) if ( $role == 'shop_manager' ) return true;

	return false;
}
