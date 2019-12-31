<?php

/**
 * Fresh Autoloader.
 *
 * @package Fresh/Classes
 * @version 1
 */

defined( 'FRESH_INCLUDES' ) || exit;

/**
 * Autoloader class.
 */
class Fresh_Autoloader {

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

		$this->include_path = untrailingslashit( plugin_dir_path( FRESH_PLUGIN_FILE ) ) . '/includes/';
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
	 * Auto-load WC classes on demand to reduce memory consumption.
	 *
	 * @param string $class Class name.
	 */
	public function autoload( $class ) {
		$class = strtolower( $class );
		$file  = $this->get_file_name_from_class( $class );
		if ( 0 === strpos( $class, 'fresh_' ) ) {
			$path = $this->include_path;
		} elseif ( 0 === strpos( $class, "core" ) ) {
			$path = $this->include_path . 'core/';
		} else {
			return;
		}

//		print "looking for " . $this->include_path . $file . "<br/>";
		if ( empty( $path ) || ! $this->load_file( $path . $file ) ) {
			$this->load_file( $this->include_path . $file );
		}
	}
}

new Fresh_Autoloader();

