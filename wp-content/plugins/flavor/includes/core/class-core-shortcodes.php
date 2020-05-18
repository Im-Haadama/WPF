<?php

/**
 * Shortcodes
 *
 * @package Core/Shortcodes
 * @version 3.2.0
 */

//defined( 'CORE_INCLUDES' ) || exit;

/**
 * Core Shortcodes class.
 */
if (class_exists('Core_Shortcodes')) return;

class Core_Shortcodes {
	protected $shortcodes;
	private static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Core_Shortcodes constructor.
	 *
	 * @param $shortcodes
	 */
	protected function __construct( ) {
//		 var_dump($shortcodes);
//		$this->shortcodes = $shortcodes;
		self::$_instance = $this;
	}


	public function add($new_shortcodes) {
		if ($new_shortcodes)
			foreach ( $new_shortcodes as $code => $result ) {
				$this->shortcodes[ $code ] = $result;
			}
	}
	/**
	 * Init shortcodes.
	 */
	static public function init() {
		self::instance()->do_init();
	}

	function do_init()
	{
		$debug = false; // (get_user_id() == 1);
		if ($this->shortcodes)
			foreach ( $this->shortcodes as $shortcode => $function_couple ) {
				if (! is_array($function_couple)) print $function_couple . " is not array";
				if (count($function_couple) < 2) print $function_couple[0] . " not a couple";
				$function = $function_couple[0];
				$capability = $function_couple[1];
				if ($debug) print "<br/>handling $shortcode $capability<br/>";
				if ($capability and strlen($capability) and ! im_user_can($capability)) {
//					print "<br/>" . $capability;
//					if ($debug >= 1) print "capability '" . $capability . "' is missing";
					add_shortcode( apply_filters( "{$shortcode}_shortcode_tag", $shortcode ), 'Core_Shortcodes::missing_capability' );
					continue;
				}
				if ($debug == 2) print " going to add";
				if (is_string($function) and is_callable($function . "_wrapper")) {
					if ($debug == 2) print "adding $shortcode => wrapper: " . $function . "_wrapper<br/>";
					add_shortcode( apply_filters( "{$shortcode}_shortcode_tag", $shortcode ), $function. "_wrapper" );
					continue;
				}
				if (is_callable($function)) {
					if ($debug == 2)	print "adding function $function<br/>";
					add_shortcode( apply_filters( "{$shortcode}_shortcode_tag", $shortcode ), $function );
					continue;
				}
				if ($debug == 2) print " no wrapper ";

				if ($debug >= 1) print "not callable: $function<br/>";
			}
	}

	/**
	 * Shortcode Wrapper.
	 *
	 * @param string[] $function Callback function.
	 * @param array    $atts     Attributes. Default to empty array.
	 * @param array    $wrapper  Customer wrapper data.
	 *
	 * @return string
	 */
	public static function shortcode_wrapper(
		$function,
		$atts = null,
		$wrapper = array(
			'class'  => 'core',
			'before' => null,
			'after'  => null,
		)
	) {
		ob_start();

		if (! $atts) {
			$atts = array();
			foreach ($_GET as $key => $value) $atts[$key] = $value;
		}
		// @codingStandardsIgnoreStart
		echo empty( $wrapper['before'] ) ? '<div class="' . esc_attr( $wrapper['class'] ) . '">' : $wrapper['before'];
		call_user_func( $function, $atts );
		echo empty( $wrapper['after'] ) ? '</div>' : $wrapper['after'];
		// @codingStandardsIgnoreEnd

		return ob_get_clean();
	}

	public static function core_management( $atts ) {
		return self::shortcode_wrapper( array( 'Core_Shortcode_Management', 'output' ), $atts );
	}

	public static function suppliers( $atts ) {
		$atts = [];
		foreach ($_GET as $param => $value)
		{
			$atts[$param] = $value;
		}

		return self::shortcode_wrapper( array( 'Core_Suppliers', 'handle' ), $atts );
	}

	public static function missing_capability($a, $b, $cap)
	{
		return "Capability '$cap' is missing.";
	}
}
