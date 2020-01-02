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
	public function __construct( ) {
//		 var_dump($shortcodes);
//		$this->shortcodes = $shortcodes;
		self::$_instance = $this;
	}


	public function add($new_shortcodes) {
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
		if ($this->shortcodes)
			foreach ( $this->shortcodes as $shortcode => $function ) {
//				  print $shortcode . " " . $function . "<br/>";
				// print "{$shortcode}_shortcode_tag" . " ". $shortcode ." " . $function . "<br/>";
				add_shortcode( apply_filters( "{$shortcode}_shortcode_tag", $shortcode ), $function );
			}

		// Alias for pre 2.1 compatibility.
//		add_shortcode( 'core_messages', __CLASS__ . '::shop_messages' );
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
		$atts = array(),
		$wrapper = array(
			'class'  => 'core',
			'before' => null,
			'after'  => null,
		)
	) {
		ob_start();

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
}
