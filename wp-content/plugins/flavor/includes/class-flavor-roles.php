<?php


class Flavor_Roles {
	protected static $_instance = null;
	private $roles, $capabilities;

	/**
	 * Flavor_Roles constructor.
	 */
	protected function __construct() {
	}

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public static function addRole($role, array $capabilities)
	{
		return;
		global $wp_roles;
		if (!$wp_roles) return; //  or die ( "no wp_roles" );

		$rc = $wp_roles->add_role($role, $role, $capabilities);
//		if (get_user_id() == 1)
//			var_dump($rc);
//		var_dump($wp_roles);

		foreach ($capabilities as $cap => $not_used) {
			$wp_roles->add_cap( $role, $cap );
		}
	}

	/**
	 * @return mixed
	 */
	static public function getRoles() {
		return self::instance()->roles;
	}
}