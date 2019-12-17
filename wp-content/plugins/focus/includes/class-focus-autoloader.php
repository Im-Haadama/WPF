<?php

/**
 * Focus Autoloader.
 *
 * @package Focus/Classes
 * @version 1
 */

defined( 'FOCUS_INCLUDES' ) || exit;

/**
 * Autoloader class.
 */
class Focus_Autoloader {

	/**
	 * Path to the includes directory.
	 *
	 * @var string
	 */
	private $include_path = '';

	/**
	 * The Constructor.
	 */
	public function __construct() {
		if ( function_exists( '__autoload' ) ) {
			spl_autoload_register( '__autoload' );
		}

		spl_autoload_register( array( $this, 'autoload' ) );

		$this->include_path = untrailingslashit( plugin_dir_path( FOCUS_PLUGIN_FILE ) ) . '/includes/';
	}

	/**
	 * Take a class name and turn it into a file name.
	 *
	 * @param string $class Class name.
	 *
	 * @return string
	 */
	private function get_file_name_from_class( $class ) {
		// print 'class-' . str_replace( '_', '-', $class ) . '.php';
		return 'class-' . str_replace( '_', '-', $class ) . '.php';
	}

	/**
	 * Include a class file.
	 *
	 * @param string $path File path.
	 *
	 * @return bool Successful or not.
	 */
	private function load_file( $path ) {
		if ( $path && is_readable( $path ) ) {
			require_once $path;

			return true;
		}

		return false;
	}

	/**
	 * Auto-load Focus classes on demand to reduce memory consumption.
	 *
	 * @param string $class Class name.
	 */
	public function autoload( $class ) {
		$class = strtolower( $class );
		$file = $this->get_file_name_from_class( $class );
		if (0 === strpos ($class, 'focus_')) {
			$path = $this->include_path;
		} else
		if ( 0 === strpos( $class, 'focus_shortcode_' ) ) {
			$path = $this->include_path . 'shortcodes/';
		} elseif ( 0 === strpos( $class, 'org' ) ) {
			$path = $this->include_path . 'org/';
		} else {
			return;
		}
//		} elseif ( 0 === strpos( $class, 'wc_admin' ) ) {
//			$path = $this->include_path . 'admin/';
//		} elseif ( 0 === strpos( $class, 'wc_payment_token_' ) ) {
//			$path = $this->include_path . 'payment-tokens/';
//		} elseif ( 0 === strpos( $class, 'wc_log_handler_' ) ) {
//			$path = $this->include_path . 'log-handlers/';
//		}

//		print "looking for " . $this->include_path . $file . "<br/>";
		if ( empty( $path ) || ! $this->load_file( $path . $file ) ) {
			$this->load_file( $this->include_path . $file );
		}
	}
}

new Focus_Autoloader();

