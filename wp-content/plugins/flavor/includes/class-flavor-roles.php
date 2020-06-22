<?php


class Flavor_Roles {
	protected static $_instance = null;
	private $roles;

	/**
	 * Flavor_Roles constructor.
	 */
	protected function __construct() {
		$this->roles = [];
	}

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function addRole($role)
	{
		array_push($this->roles, $role);
		return;
	}

	/**
	 * @return mixed
	 */
	static public function getRoles() {
		return self::instance()->roles;
	}
}