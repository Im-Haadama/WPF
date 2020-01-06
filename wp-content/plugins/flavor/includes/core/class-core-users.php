<?php

class Core_Users {
	private $id;

	public function __construct($id) {
		$this->id = $id;
	}

	function CustomerEmail() {
		$user_info = get_userdata( $this->id );
		return $user_info->user_email;
	}
}