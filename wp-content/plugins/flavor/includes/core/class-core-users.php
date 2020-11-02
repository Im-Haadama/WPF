<?php

class Core_Users {
	protected $id;
	private $wp_user;

	public function __construct($id = 0) {
		if (! $id) $id = get_user_id();
		$this->id = $id;
		$this->wp_user = null; // Create by demand
	}

	/**
	 * @return int
	 */
	public function getId(): int {
		return $this->id;
	}

	function CustomerEmail() {
		$user_info = get_userdata( $this->id );
		return $user_info->user_email;
	}

	private function getUser()
	{
		if (! $this->wp_user)
			$this->wp_user = get_user_by("id", $this->id);
		return $this->wp_user;
	}

	function hasRole($role){
		if (! $u = self::getUser()) return false;
		return in_array($role, $u->roles);
	}

	static function get_user_by_email($meail)
	{
		$u = get_user_by('email', $meail);
		if ($u) return new Core_Users($u->id);
		return null;
	}

	function can($capability)
	{
		return ( user_can( $this->id, $capability ) );
	}

	function getName()
	{
		return self::getUser()->display_name;
	}

	static function gui_select_user( $id = null, $selected = null, $args = null ) {
		// $events = GetArg($args, "events", null);
		$edit = GetArg( $args, "edit", true );

		if (! $edit){
			$u = get_user_to_edit( $selected );
			return $u->display_name;
		}


		$args["name"]     = "client_displayname(id)";
		$args["id_key"]   = "id";
		$args["selected"] = $selected;

		$result = Core_Html::GuiAutoList( $id, "users", $args );
		$result .= Core_Html::GuiHyperlink("Create new user", "/wp-admin/user-new.php");

		return $result;
	}

	static function create_user($email, $user_name, $password = null)
	{
		$u_id = wp_create_user($user_name, $password, $email);

//		MyLog("setting password $password to user $u_id email $email");
//		wp_set_password($password, $u_id) ;

		return new Core_Users($u_id);
	}

	function setName($name)
	{
		return self::set_field('nickname', $name);
	}

	function set_field($field_name, $field_value)
	{
		return update_user_meta($this->id, $field_name, $field_value);
	}

	function addCapability($capablity)
	{
		$this->getUser()->add_cap($capablity); // Seems not to return a value. Assuming success.
		return true;
	}
}
