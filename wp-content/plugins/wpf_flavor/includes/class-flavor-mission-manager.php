<?php
/*
 * Copyright (c) 2021. Lorem ipsum dolor sit amet, consectetur adipiscing elit.
 * Morbi non lorem porttitor neque feugiat blandit. Ut vitae ipsum eget quam lacinia accumsan.
 * Etiam sed turpis ac ipsum condimentum fringilla. Maecenas magna.
 * Proin dapibus sapien vel ante. Aliquam erat volutpat. Pellentesque sagittis ligula eget metus.
 * Vestibulum commodo. Ut rhoncus gravida arcu.
 */

class Flavor_Mission_Manager {
	static private $_instance;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( "Flavor" );
		}

		return self::$_instance;
	}

	function init_hooks($loader)
	{
		$loader->AddAction('get_local_anonymous', $this);
		$loader->AddAction('get_local_supplies_anonymous', $this);
	}

	function init()
	{
	}
	static function get_local_anonymous()
	{
		FlavorLog(__FUNCTION__);
		$mission_ids = GetParam("mission_ids", true);
		$header = GetParam("header", false, false);
		print self::GetLocalMissions($mission_ids, $header);
		return true;
	}

	static function get_local_supplies_anonymous()
	{
		FlavorLog(__FUNCTION__);
		$mission_ids = GetParam("mission_ids", true);
//		$header = GetParam("header", false, false);
		self::GetLocalSupplies($mission_ids);
		return true;
	}

	static function GetLocalSupplies($mission_ids)
	{
		$args = array("edit"=>false);
		if (! is_array($mission_ids)) $mission_ids = array($mission_ids);
		foreach ($mission_ids as $mission_id) {
			$supplies = Fresh_Supplies::mission_supplies( $mission_id );
			foreach ( $supplies as $supply_id ) {
				$s = new Fresh_Supply( $supply_id );
				print $s->Html( $args );
			}
		}
	}

	static function GetLocalMissions($mission_ids, $header = true)
	{
		$data = "";

//		FinanceLog(__FUNCTION__);
		if ($header) print Flavor_Mission_Views::delivery_table_header();
		if (! is_array($mission_ids)) $mission_ids = array($mission_ids);

		foreach ( $mission_ids as $mission_id ) {
			if ( $mission_id ) {
//				if (class_exists('Flavor_Mission_Manager'))
					$data .= self::print_deliveries( $mission_id, false);

				if (class_exists("Fresh_Supplies") and TableExists("supplies"))
					$data .= Fresh_Supplies::print_driver_supplies( $mission_id );
//				else
//					print "NNN";

				if (class_exists("Focus"))
					$data .= Focus::print_driver_tasks( $mission_id );
			}
		}

		return $data;
	}

	static function print_deliveries( $mission_id, $selectable = false, $debug = false ) {
		$m = new Mission($mission_id);
		$orders = $m->getOrders();
		$data = "";

		$prev_user = - 1;
		foreach ($orders as $order) {
//		while ( $order = SqlFetchRow( $orders ) ) {
			$order_id   = $order[0];
//			print "order: $order_id<br/>";
			if ($debug) MyLog(__FUNCTION__ . ': $order_id');
			$o          = new Finance_Order( $order_id );
			$is_group   = false; // $order[1];
			$order_user = $order[1];
			if ( $debug ) print "order " . $order_id . "<br/>";

//			if (get_post_meta($order_id, "delivered")) continue;
			if ( ! $is_group ) {
				$data .= $o->PrintHtml( $selectable, $mission_id );
				continue;
			} else {
				if ( $order_user != $prev_user ) {
					$data      .= $o->PrintHtml( $selectable );
					$prev_user = $order_user;
				}
			}
		}

		return $data;
	}


}