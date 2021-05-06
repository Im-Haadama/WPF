<?php


/**
 * Class Finance_Payments
 */
class Finance_Payments {
	/**
	 * @var null
	 */
	protected static $_instance = null;

	/**
	 * @var string
	 */
	protected static $version = "1.0";

	/**
	 * @var string post file
	 */
	private $post_file;

	/**
	 * Finance_Payments constructor.
	 * gets the post file.
	 */
	public function __construct( $post_file ) {
		$this->post_file = $post_file;
	}

	/**
	 * enqueue_scripts - add scripts to be loaded.
	 */
	function enqueue_scripts() {
//		$file = plugin_dir_url( __FILE__ ) . 'org/people.js';
//		wp_enqueue_script( 'people', $file, null, self::$version, false );
	}

	/**
	 * @return Finance_Payments|null
	 * single instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( WPF_Flavor::getPost());
		}
		return self::$_instance;
	}

	/**
	 * @param $module_operation
	 *
	 * @return bool
	 */
	static public function handle_operation($operation) {
		// Take the operation from module_operation.
		switch ( $operation ) {
		}

		return false;
	}

	static function getPost()
	{
		return self::instance()->post_file;
	}

	function init_hooks($loader)
	{
		Core_Gem::getInstance()->AddTable("payments");
		$loader->AddAction("update_payment_method", $this, 'update_payment_method');

		// Don't user loader for init hooks.
		add_action('init', array($this, 'insert_payment_info_wrap'), 10, 1);
		add_action('admin_init', array($this, 'wp_payment_list_admin_script'));

	}

	static function update_payment_method()
	{
		$user_id   = GetParam("user_id", true);
		$method_id = GetParam("method_id", true);
		update_user_meta( $user_id, 'payment_method', $method_id );
		return true;
	}
	//* Shortcode handling
	//* 1) payment - list of payments and the handling workers.

	/**
	 * @return string
	 * @throws Exception
	 */
	static function main_wrapper()
	{
		$me = self::instance();
		if ($operation = GetParam("operation", false))
			return self::handle_operation($operation);
		return $me->main();
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	static function payment_methods() {
		$operation = GetParam("operation", false, null);
		$result = Core_Html::GuiHeader(1, "ניהול אמצעי תשלום");
		if ($operation) {
			$args = [];
			$args["operation"] = $operation;
			$args["post_file"] = Finance::getPostFile();
			$result .= apply_filters($operation, $operation, null, $args);
		}
		$args["edit"] = 1;
		$args["post_file"] = self::instance()->post_file;
		$result .= Core_Gem::GemTable("payments", $args);
		print $result;
	}

	// 2) Report - data to payments accountant
	/**
	 * @return string
	 * @throws Exception
	 *
	 */
	static function payments_wrapper()
	{
		$year_month = GetParam( "month", false, date( 'Y-m', strtotime('-15 days') ) );

		return self::payments();
	}


	/**
	 * @return array
	 */
	function getShortcodes() {
		//           code                   function                              capablity (not checked, for now).
		// return array( 'finance_payment'      => array( 'Finance_Payments::payments',    null ));          // Payments data entry
	}

	function insert_payment_info_wrap()
	{
		$sql = "select post_id from wp_postmeta where meta_key = 'card_number'";
		$orders = SqlQueryArrayScalar($sql);
		foreach ($orders as $order)
			$this->insert_payment_info($order);
	}

	function insert_payment_info( $order_id )
	{
		MyLog(__FUNCTION__, "handling $order_id");
		if ( ! $order_id ) return;
		if ( ! get_post_meta( $order_id, '_thankyou_action_done', true ) ) {

//		$order = wc_get_order( $order_id );
			$first_name = get_post_meta($order_id, '_billing_first_name', TRUE);
			$last_name = get_post_meta($order_id, '_billing_last_name', TRUE);
			$full_name = $first_name.' '.$last_name;
			$billing_email = get_post_meta($order_id, '_billing_email', TRUE);
			$card_number = get_post_meta($order_id, 'card_number', TRUE);

			$card_last_4_digit = $this->setCreditCard($card_number);
			$card_type = get_post_meta($order_id, 'card_type', TRUE);
			$exp_date_month = get_post_meta($order_id, 'expdate_month', TRUE);
			$exp_date_year = get_post_meta($order_id, 'expdate_year', TRUE);
			$billing_id_number = get_post_meta($order_id, 'id_number', TRUE);
			$user_id = get_post_meta($order_id, '_customer_user', true);

			if($card_number != ''){
				global $wpdb;
				$table = 'im_payment_info';
				$data = array('user_id' => $user_id,
				              'full_name' => $full_name,
				              'email' => $billing_email,
				              'card_number' => $card_number,
				              'card_four_digit' => $card_last_4_digit,
				              'card_type' => $card_type,
				              'exp_date_month' => $exp_date_month,
				              'exp_date_year' => $exp_date_year,
				              'id_number' => $billing_id_number );
				$wpdb->insert($table, $data);
				$last_id = $wpdb->insert_id;

				if ($last_id){
					delete_post_meta($order_id, 'card_number');
					delete_post_meta($order_id, 'card_type');
					delete_post_meta($order_id, 'expdate_month');
					delete_post_meta($order_id, 'expdate_year');
					delete_post_meta($order_id, 'cvv_number');
					delete_post_meta($order_id, 'id_number');
				}
			}
		}
	}

	/*-- Start save payment info --*/

	static function setCreditCard($cc)
	{
		$cc_length = strlen($cc);

		for($i=0; $i<$cc_length-4; $i++){
			if($cc[$i] == '-'){continue;}
			$cc[$i] = 'X';
		}
		return $cc;
	}

	/*-- End save payment info --*/

	function wp_payment_list_admin_script() {

		wp_enqueue_script( 'dataTables.min', plugins_url(). '/fresh/includes/js/jquery.dataTables.min.js',array('jquery') );

		wp_enqueue_script( 'dataTables.bootstrap.min', plugins_url(). '/fresh/includes/js/dataTables.bootstrap.min.js' );

		wp_enqueue_script( 'dataTables.buttons.min', plugins_url(). '/fresh/includes/js/dataTables.buttons.min.js' );

	}

}
