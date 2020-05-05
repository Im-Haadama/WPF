<?php

abstract class Core_Logger_Severity
{
	const trace = 1;
	const info = 2;
	const warning = 3;
	const error = 4;
	const fatal = 5;
}


class Core_Logger
{
	protected static $_instance = null;
	protected $source;
	protected $filter_levels;
	protected static $mode;


	/**
	 * Core_Logger constructor.
	 */
	public function __construct($source) {
		$this->source = $source;
		$this->filter_levels = array(); // array(1, 2);
		self::$mode = "db";
	}

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			die(__CLASS__ . ": initilize first");
		}
		return self::$_instance;
	}

	function fatal($message)
	{
		return self::log (Core_Logger_Severity::fatal, $message);
	}

	function info($message)
	{
		return self::log (Core_Logger_Severity::info, $message);
	}

	function log($severity, $message)
	{
		$db_prefix = GetTablePrefix();

		if (isset($this->filter_levels[$severity])) return true;
		switch (self::$mode){
			case "db":
				$sql = sprintf("insert into ${db_prefix}log (time, source, severity, message) \n" .
				               "values(NOW(), '%s', %d, '%s')", $this->source, $severity, EscapeString($message));

				return SqlQuery($sql);
			default:
				if(get_user_id() == 1) print $message . "<br/>";
		}
	}

	function trace($message)
	{
		// Todo: enable turn off trace
		return self::log(Core_Logger_Severity::trace, $message);
	}

	static function Args()
	{
		$args = [];
		$args["post_file"] = FLAVOR_INCLUDES_ABSPATH . '/post.php';
		$args["page"] = GetParam("page", false, 1);
		$args["order"] = "id desc";
		$args["reverse"] = 1; // In order to get the newest by order
		$args["links"] = array("id"=>self::getUrl("log_entry"));
		$args["row_id"] = GetParam("row_id", false, null);
		$args["check_active"] = false;
		$args["edit"] = false;
		$args["rows_per_page"] = 50;
		return $args;
	}

	static function getUrl($type)
	{
		return AddToUrl("row_id", "%d");
	}

	static function log_viewer()
	{
		$result = "";
		$args = self::Args();
		if (isset($args["row_id"])) return Core_Gem::GemElement("log", $args["row_id"], $args);
		$result .= Core_Gem::GemTable("log", $args);
		return $result;
	}
}
