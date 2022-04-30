<?php

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
		private $include_path = [];
		protected static $_instance = null;

		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self(null);
			}
			return self::$_instance;
		}

		/**
		 * The Constructor.
		 */
		private function __construct( ) {
			if ( function_exists( '__autoload' ) )
				spl_autoload_register( '__autoload' );

			spl_autoload_register( array( $this, 'autoload' ) );
			$this->include_path = array(dirname(dirname(__DIR__)) . '/includes',
				dirname(dirname(__DIR__)) . '/includes/core/');

			if (! function_exists('InfoGet')) {
				require_once( ABSPATH . 'wp-content/plugins/wpf_flavor/includes/core/core-functions.php' );
				require_once(ABSPATH . 'wp-content/plugins/wpf_flavor/includes/core/data/sql.php');
				require_once(ABSPATH . 'wp-content/plugins/wpf_flavor/includes/core/fund.php');
				require_once(ABSPATH . 'wp-content/plugins/wpf_flavor/includes/core/wp.php');
			}

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

			$path_part = '';
			if ( 0 === strpos( $class, 'core_' ) ) {
				$path_part = "core/";
			} elseif ( 0 === strpos( $class, 'core_shortcode_' ) ) {
				$path_part = 'shortcodes/';
			} elseif ( 0 === strpos( $class, 'org' ) ) {
				$path_part = 'org/';
			}

			foreach ($this->include_path as $path) {
				if (file_exists($path . '/' . $path_part . $file)) {
					$this->load_file( $path . '/' . $path_part . $file );

					return;
				}
			}
		}

		/**
		 * @return string
		 */
		public function getIncludePath(): string {
			return $this->include_path;
		}

		function add_path($path)
		{
			array_push ($this->include_path, $path);
		}
	}
}