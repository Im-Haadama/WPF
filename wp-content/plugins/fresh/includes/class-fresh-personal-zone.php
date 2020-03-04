<?php


class Fresh_Personal_Zone {

	function getShortcodes() {
		//             code                           function                  capablity (not checked, for now).
		return ( array('fresh_open_orders'           => array( 'Focus_Personal_Zone::open_orders'))
		);
	}

	static function open_orders()
	{
		$user_id = get_user_id();

		if (! $user_id) return "connect first";

	}

}