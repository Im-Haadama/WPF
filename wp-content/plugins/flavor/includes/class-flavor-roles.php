<?php


class Flavor_Roles {
	protected static $_instance = null;
	private $roles, $capabilities;

	/**
	 * Flavor_Roles constructor.
	 */
	protected function __construct() {
		$this->roles = [];
		$this->capabilities = [];
	}

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public static function addRole($role, array $capabilities)
	{
		global $wp_roles;
//		var_dump($wp_roles);
		if (!$wp_roles) return; //  or die ( "no wp_roles" );

		$i = self::instance();
		if (! in_array($role, $i->roles))
			array_push($i->roles, $role);

		if (! isset($capabilities[$role])) $capabilities[$role] = [];

		foreach ($capabilities as $cap => $not_used) {
//			print "adding $cap to $role<br/>";
			$capabilities[ $role ][ $cap ] = 1;

			$wp_role = $wp_roles->get_role( $role );

			if ( ! $wp_role ) {
//				print "=====================> Adding $role<br/>";
				$wp_roles->add_role($role, $role);
			}
			foreach ( $capabilities as $cap => $not_used ) {
				$wp_roles->add_cap( $role, $cap );
			}
		}
	}

	/**
	 * @return mixed
	 */
	static public function getRoles() {
		return self::instance()->roles;
	}
}