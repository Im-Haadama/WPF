<?php

class Core_Users {
	private $id;
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
		if (! $this->wp_user) $this->wp_user = wp_get_current_user();
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
		return $this->wp_user->display_name;
	}
}

