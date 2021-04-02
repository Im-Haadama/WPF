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

	public function addRole($role, $capabilities = null)
	{
		array_push($this->roles, $role);

		$caps = [];
		if ($capabilities) {
//			foreach ( $capabilities as $capability ) {
//				MyLog($capability);
//				$caps[$capability] = true;
//			}
			$rc = add_role($role, convert_to_title($role), $caps);
			$role = get_role($role);
			if (! $role) {
				print "Role $role not found<br/>";
				return false;
			}
			foreach ($capabilities as $capability) {
				$role->add_cap( $capability );
//				print "=============================>$role $rc " . $capabilities[0] . "<br/>";
			}
		}

		return;
	}

	/**
	 * @return mixed
	 */
	static public function getRoles() {
		return self::instance()->roles;
	}
}