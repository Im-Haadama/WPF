<?php
/*
 * Copyright (c) 2022. Lorem ipsum dolor sit amet, consectetur adipiscing elit.
 * Morbi non lorem porttitor neque feugiat blandit. Ut vitae ipsum eget quam lacinia accumsan.
 * Etiam sed turpis ac ipsum condimentum fringilla. Maecenas magna.
 * Proin dapibus sapien vel ante. Aliquam erat volutpat. Pellentesque sagittis ligula eget metus.
 * Vestibulum commodo. Ut rhoncus gravida arcu.
 */

class WPF_Plugin {
	protected $auto_loader;
	protected $settings;
	protected $loader;
	protected $database;
	public $version;
	protected $plugin_name;

	public function run()
	{
		// Install tables
		$this->register_activation(dirname(__FILE__) . '/class-fresh-database.php', [$this, 'install']);

		// Temp migration. run once on each installation
		// Fresh_Database::convert_supplier_name_to_id();

		// Create functions, tables, etc.
	}

	function register_activation($file, $function)
	{
		if (! file_exists($file)){
			print "file $file not exists";
			return;
		}
		if (! is_callable($function)){
			print __FUNCTION__ . ": function is not callable. file=$file";
			var_dump($function);
			return;
		}
		register_activation_hook($file, $function);
	}

}