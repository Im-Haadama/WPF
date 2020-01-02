<?php

/**
 * Focus Autoloader.
 *
 * @package Focus/Classes
 * @version 1
 */

defined( 'ABSPATH' ) || die(__FILE__ . ":" . __LINE__);

/**
 * Autoloader class.
 */
if (! class_exists('Core_Autoloader')) {
	class Core_Autoloader {

		/**
		 * Path to the includes directory.
		 *
		 * @var string
		 */
		private $include_path = '';

		/**
		 * The Constructor.
		 */
		public function __construct( $plugin_dir ) {
//		print $plugin_dir . "<br/>";
			if ( function_exists( '__autoload' ) ) {
				spl_autoload_register( '__autoload' );
			}

			spl_autoload_register( array( $this, 'autoload' ) );

			$this->include_path = untrailingslashit( $plugin_dir ) . '/includes/';

//		print $this->include_path . "<br/>";
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
			$path = $this->include_path;
			$class = strtolower( $class );
//		print "loading " . $class . " " . strpos($class, "core_") . "<br/>";
			$file = $this->get_file_name_from_class( $class );

			if ( 0 === strpos( $class, 'core_' ) ) {
				$path = $this->include_path . "core/";
//				print "CORE";
			} elseif ( 0 === strpos( $class, 'core_shortcode_' ) ) {
				$path = $this->include_path . 'shortcodes/';
			} elseif ( 0 === strpos( $class, 'org' ) ) {
				$path = $this->include_path . 'org/';
			}

//		print "looking for " . $path . $file . "<br/>";
			if ( empty( $path ) || ! $this->load_file( $path . $file ) ) {
				$this->load_file( $path . $file );
			}
		}

		/**
		 * @return string
		 */
		public function getIncludePath(): string {
			return $this->include_path;
		}


	}

// new Core_Autoloader();

}