<?php


class Israel_Zones {

	protected $shortcodes;
	protected $auto_loader;


	/**
	 * Israel_Zones constructor.
	 */
	public function __construct() {
		self::init();
	}

	public function init()
	{
		define( 'ISRAEL_ZONES_ABSPATH', dirname( ISRAEL_ZONES_PLUGIN_FILE ) . '/' );

		$this->auto_loader = new Core_Autoloader(ISRAEL_ZONES_ABSPATH);

		$this->init_hooks();

		$this->shortcodes = Core_Shortcodes::instance();
		$this->shortcodes->add(array("israel_zones" => array("Israel_Zones::main", null)));
		$this->shortcodes->do_init();
	}

	private function init_hooks() {
		add_action( 'init', array( 'Core_Shortcodes', 'init' ) );
		Core_Gem::AddTable("cities");
	}

	static function Args($type = null)
	{
		$args = [];
		$args["post_file"] = plugin_dir_url(dirname(__FILE__)) . "post.php";
		$args["edit"] = true;

		return $args;
	}

	static function main_wrapper()
	{
		$args = self::Args();
		$operation = GetParam("operation");

		if ($operation){
			$args["operation"] = $operation;
			$id = GetParam("id", false, 0);

			$result = apply_filters( $operation, $operation, $id, $args );
			if ( $result ) 	return $result;
		}

		$result = Core_Gem::GemTable("cities", $args);
		return $result;
	}
}