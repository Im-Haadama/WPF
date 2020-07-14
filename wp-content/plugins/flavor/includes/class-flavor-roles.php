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

	public function addRole($role_name, $capabilities = null)
	{
		array_push($this->roles, $role_name);

		$caps = [];
		if ($capabilities) {
//			foreach ( $capabilities as $capability ) {
//				MyLog($capability);
//				$caps[$capability] = true;
//			}
			$rc = add_role($role_name, convert_to_title($role_name), $caps);
			$role = get_role($role_name);
			foreach ($capabilities as $capability) {
//				MyLog("Add $capability to $role_name");
				$role->add_cap( $capability );
			}
//			print "=============================>$role $rc " . $capabilities[0] . "<br/>";
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