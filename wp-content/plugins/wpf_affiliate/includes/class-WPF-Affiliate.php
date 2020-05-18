<?php


class WPF_Affiliate {
	private $version;

	protected $database;
	/**
	 * Affiliate constructor.
	 */
	public function __construct() {
		$version = '1.0';
		$database = new Affiliate_Database();
		if (is_admin_user())
		$database->install($this->version);
	}

	public function init_hooks()
	{

	}
}