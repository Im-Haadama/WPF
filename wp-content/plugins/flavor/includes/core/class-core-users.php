<?php

class Core_Users {
	protected $id;
	private $wp_user;

	public function __construct($id = 0) {
		if (! $id) $id = get_user_id();
		$this->id = $id;
		$this->wp_user = null; // Create by demand
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

	function can($capability)
	{
		return ( user_can( $this->id, $capability ) );
	}

	function getName()
	{
		if ($u = self::getUser())
			return $u->display_name;
		return "no user for " . $this->id;
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

}

