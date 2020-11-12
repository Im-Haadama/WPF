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
			self::$_instance = new self( Flavor::getPost());
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

	function init_hooks()
	{
		Core_Gem::AddTable("payments");
		AddAction("update_payment_method", array($this, 'update_payment_method'));
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

}
