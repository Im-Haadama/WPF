<?php

/*
 * Copyright (c) 2020. Lorem ipsum dolor sit amet, consectetur adipiscing elit.
 * Morbi non lorem porttitor neque feugiat blandit. Ut vitae ipsum eget quam lacinia accumsan.
 * Etiam sed turpis ac ipsum condimentum fringilla. Maecenas magna.
 * Proin dapibus sapien vel ante. Aliquam erat volutpat. Pellentesque sagittis ligula eget metus.
 * Vestibulum commodo. Ut rhoncus gravida arcu.
 */

abstract class OrderTableFields
{
	const site_name = 0;
	const order_id = 1;
	const client_number = 2;
	const client_name = 3;
	const city = 4;
	const address_1 = 5;
	const address_2 = 6;
	const phone = 7;
	const shipping = 8;
	const mission_id = 9;
	const site_id = 10;
	const fee = 11;
	const comments = 12;
	const external_order_id = 13;
	const order_status = 15;
	const max = 15;
}

abstract class DispatchTableFields
{
	const order_number = 0;
	const order_status = 1;
	const customer_name = 2;
	const city = 3;
	const address_1 = 4;
	const address_2 = 5;
	const comments = 6;
	const external_id = 7;
	const phone = 8;
	const delivered = 9;
	const eta = 10;
	const acc_km = 11;
	const priority = 12;
	const max = 13;
}

class Freight_Mission_Manager
{
	private $mission_id;
	private $zoom;
	private $center;
	private $distance_matrix;

	// Data from sites: Point order ids and site ids
	private $point_orders;

	// Built path
	private $path;

	private $lines_per_point;
	private $prerequisite;
	private $supplies_to_collect;
	private $stop_points;

	static Core_MultiSite $multi_site;

	function instance()
	{
		return null;
	}
	/**
	 * Freight_Mission_Manager constructor.
	 */

	private function __construct($mission_id) {
		$this->mission_id          = $mission_id;
		$this->lines_per_point     = array();
		$this->prerequisite        = array();
		$this->supplies_to_collect = array();
		$this->stop_points         = array();
		$this->point_sites         = array();
		$this->point_orders        = array();
		$this->path                = null;
		$this->distance_matrix = array();
	}

	static public function get_mission_manager($mission_id)
	{
		if (! self::check_settings())
			return false;

		self::$multi_site = Core_Db_MultiSite::getInstance();

		FreightLog(__FUNCTION__);
		// Try cache
		$data = InfoGet("mission_$mission_id");
		if ($data) {
			FreightLog("using cache");
			return unserialize($data);
		}

		// Get from all sites.
		$n = new Freight_Mission_Manager($mission_id);
		if (! $n->get_route())
		{
			print "<br/><h1>Failed: No orders found</h1>";
			return $n;
		}
		$n->save();
		return $n;
	}

	private static function check_settings(): bool {
		if (! defined('MAPS_KEY'))
		{
			print "define MAPS_KEY first! in wp-config.php";
			return false;
		}
		return true;
	}

	private function save()
	{
		$mission_id = $this->mission_id;
		FreightLog("updating cache");
		InfoUpdate("mission_$mission_id", serialize($this));
	}

	function getCSV($the_mission)
	{
		$path_info = array(array(
			"Order Number",
			"Order Status",
			"Order Date",
			"Customer Note",
			"First Name (Billing)",
			"Last Name (Billing)",
			"Company (Billing)",
			"Address 1&2 (Billing)",
			"City (Billing)",
			"State Code (Billing)",
			"Postcode (Billing)",
			"Country Code (Billing)",
			"Email (Billing)",
			"Phone (Billing)",
			"First Name (Shipping)",
			"Last Name (Shipping)",
			"Address 1&2 (Shipping)",
			"City (Shipping)"
			));

		$supplies_to_collect = array();

		$this->prepare_route($path);

		if (! $path or ! count($path))
			return "No points";

		for ( $i = 0; $i < count( $path ); $i ++ ) {
			if (isset($this->lines_per_point[$path[$i]]))
				foreach ( $this->lines_per_point[ $path[ $i ] ] as $order_info ) {
					if (! is_array($order_info) or count($order_info) < 10)
					{
						var_dump($order_info);
						continue;
					}
					$order_id  = $order_info[ OrderTableFields::order_id ];
					$site_id   = $order_info[ OrderTableFields::site_id ];
					$order_pri = self::order_get_pri( $order_info );
					$site      = $order_info[ OrderTableFields::site_name ];
					$type      = "orders";
					if ( $site == "supplies" ) {
						$type = "supplies";
					} else if ( $site == "משימות" ) {
						$type = "tasklist";
					}
					array_push( $path_info,
						array(
							$order_id, //"Order Number",
							'', //"Order Status",
							'', //"Order Date",
							$order_info[ OrderTableFields::address_2 ] . " " . $order_info[OrderTableFields::comments], //"Customer Note",
							$order_info[ OrderTableFields::client_name ], //"First Name (Billing)",
							'',//"Last Name (Billing)",
							'',//"Company (Billing)",
							'',//"Address 1&2 (Billing)",
							'',//"City (Billing)",
							'',//"State Code (Billing)",
							'',//"Postcode (Billing)",
							'',//"Country Code (Billing)",
							'',//"Email (Billing)",
							$order_info[ OrderTableFields::phone ],//"Phone (Billing)",
							'',//"First Name (Shipping)",
							'',//"Last Name (Shipping)",
							$order_info[ OrderTableFields::address_1 ],//"Address 1&2 (Shipping)",
							$order_info[ OrderTableFields::city ]//"City (Shipping)"
						) );

			}
		}

		return array2csv($path_info);
	}

	static function gui_select_mission_type($id, $selected, $args)
	{
		$args["selected"] = $selected;
		$args["name"] = 'mission_name';
		return Core_Html::GuiSelectTable($id, "mission_types", $args);
	}

	static function dispatcher_wrap()
	{
		dd("AAA");
		$operation = GetParam("operation", false, null);
		$header = __("Dispatch mission") ;
		$week = GetParam("week", false, null);
		if ($week)
			$header .= __("Missions of week") . " " . $week;

		$result = Core_Html::GuiHeader(1, $header);

		if ($operation)
		{
			apply_filters($operation, $result);
		}

		$args = [];
		$args["links"] = array("mission" => AddToUrl("id", "%d"));
		$args["post_file"] = Freight::getPost();
		$rows = SqlQueryAssoc("select p.post_status as status, pm.meta_value as mission, m.name, count(*) as c, date
from wp_postmeta pm 
    join wp_posts p,
     im_missions m
where meta_key = 'mission_id' 
  and pm.post_id = p.ID 
  and p.post_status not in ('wc-completed','wc-cancelled', 'trash')
and pm.meta_value > 0
and m.id = pm.meta_value
group by pm.meta_value, p.post_status");

		$missions_data = SqlQuery("select id, name from im_missions where date = curdate()");
		while ($mission = SqlFetchAssoc($missions_data)) {
			$found = false;
			if ( $rows ) {
				foreach ( $rows as $row ) {
					if ( $row['mission'] == $mission ) {
						$found = true;
					}
				}
			}
			if (! $found)
				array_push($rows, array("status"=>"new", "mission"=>$mission['id'], "name" => $mission['name'], "count"=> 0, "date" => "today"));
			}
		if (count($rows)) {
			array_unshift($rows, array("status", "id", "mission", "count", "date"));
			$result .= Core_Gem::GemArray( $rows, $args, "missions" );
		}

		print $result;
	}

	function markers()
	{
		$output = "";
		$output .='<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL .
		'<markers>' . PHP_EOL;
		$points = $this->prepare_route(true);
		if (! $points)
		{
			print 'Failed: no points';
			return null;
		}
		$center_long = 0;
		$center_lat = 0;
		$count = 0;

		foreach ($points as $orders)
		{
//			$point = $points[0];
//			$site_id = $point[OrderTableFields::site_id];
//			$order_id = $point[OrderTableFields::order_number];
			$pri = Freight_Mission_Manager::order_get_pri($orders);
			$name = '';
			foreach ($orders as $order) {
				$ext = $order[OrderTableFields::external_order_id];
				if ($ext) $name .= " " . $ext;
				else $name .= " " . $order[OrderTableFields::order_id];
				// else $name = strtok($point[OrderTableFields::comments], " ");
			}
			$name = trim($name);
			$point = $orders[0];

			$address = $point[OrderTableFields::address_1] . " " . $point[OrderTableFields::city];

			// Set the name;

			$lat_long = Freight_Mission_Manager::get_lat_long($address);
			$center_long += $lat_long[1]; $center_lat+=$lat_long[0]; $count++;

			if (! $lat_long) $lat_long = array(32, 34);
			$id = $point[OrderTableFields::order_id] . '_' . $point[OrderTableFields::site_id];
			$output .='<marker id="' . $id . '" pri="' . $pri . '" address="' . urlencode($address . " " . $order[OrderTableFields::external_order_id]) . '" type="Delivery"'.
			' lat="' . $lat_long[0]. '" lng="' . $lat_long[1] . '" '
			          .'/>' . PHP_EOL;
		}

		$output .= '<marker id="center"' .
		          ' lat="' . ($center_lat / $count) . '" lng="' . ($center_long / $count) . '" '
		          .'/>' . PHP_EOL;

		$output .='</markers>';

		return $output;
	}

	function dispatcher($args = null)
	{
		FreightLog(__FUNCTION__);
		$the_mission = $this->mission_id;

		$edit = GetArg($args, "edit", true);
		$print = GetArg($args, "print", false);
		$sort = true; // GetParam("sort", false, false);

		$multi_site = Core_Db_MultiSite::getInstance();
		$result = "";
		$post_file = WPF_Flavor::getPost();

		$m = new Mission($the_mission);
		if (! $m->getStartAddress()){
			return  "No start address<br/>" .
			        "Set " . Core_Html::GuiHyperlink("here", self::get_url($the_mission));
		}

		if ($sort) {
			$this->path = [];
			$this->prepare_route();
		} else {
			$this->path = $this->stop_points;
		}

		$arrive_time = strtotime( $m->getStartTime());
		$prev           = $m->getStartAddress();
		$total_distance = 0; // self::get_distance($prev, $path[0]);
		$path_info = [];
		$path_info['header'] = array(
			__( "Order number" ),
			__( "Order status"),
			__( "Customer name" ),
			__( "City"),
			__( "Address 1" ),
			__( "Address 2" ),
			__( "Comments" ),
			__( "External order id" ),
			__( 'Phone' ),
			__("Time"),
			__("Km")
		);
		if (! $print) array_push($path_info['header'], __( 'Delivered' ));
		if ($edit) array_push($path_info['header'], __( 'Priority' ));

		$point_number = count($this->path);
		if ($this->path and count($this->path)) {
			$result .= Core_Html::GuiHeader(1, __("Details for dispatch") . " " . $m->getMissionName() . " ($the_mission)");
			if (! $print) $result .= "<div>" . __("Mission start") . ":" . Core_Html::gui_input_time("start_h", null, $m->getStartTime(),
					"onchange=\"update_table_field('" . $post_file . "', 'missions', $the_mission, 'start_h', location_reload)\"") . "</div>";

			for ( $i = 0; $i < count( $this->path ); $i ++ ) {
				$distance       = round( self::get_distance( $prev, $this->path[ $i ] ) / 1000, 1 );
				$total_distance += $distance;
				if ( isset( $this->lines_per_point[ $this->path[ $i ] ] ) ) {
					// Time calculation

					foreach ( $this->lines_per_point[ $this->path[ $i ] ] as $order_info ) {
//						var_dump($order_info); print "<br/>";
						$arrive_time += 6 *60; // 6 minutes
						$order_id  = $order_info[ OrderTableFields::order_id ];
						$site_id   = $order_info[ OrderTableFields::site_id ];
						$site      = $order_info[ OrderTableFields::site_name ];
						$user_id   = $order_info[ OrderTableFields::client_number ];
						$order_pri = self::order_get_pri( $order_info);
						$user_name = $order_info[ OrderTableFields::client_name ];
						$pri_input = Core_Html::GuiInput( "pri" . $order_id . "_" . $site_id, $order_pri, array( "size"   => 5,
						                                                                                         "events" => 'onchange="update_order_pri(\'' . self::getPost() . '\',this)"'
							) );
						// Core_Html::GuiButton( "btn_reset_reset", "R", array( "action" => "reset_path('" . self::getPost() . "'," . ( $i + 1 ) . ")" ) );
						$type      = "orders";
						if ( $site == "supplies" ) {
							$type = "supplies";
						} else if ( $site == "משימות" ) {
							$type = "tasklist";
						}

						$duration       = round( self::get_distance_duration( $prev, $this->path[ $i ] ) / 60, 0 );
						$arrive_time += $duration * 60;

						$edit_user = Core_Html::GuiHyperlink( $user_id, self::$multi_site->getSiteURL( $site_id ) . "/wp-admin/user-edit.php?user_id=" . $user_id );

						$comments = $order_info[ OrderTableFields::comments ];
						$city = $order_info[OrderTableFields::city];
						$address_1 = $order_info[OrderTableFields::address_1];
						$address_2 = $order_info[ OrderTableFields::address_2 ];

						$external_id = $order_info[OrderTableFields::external_order_id];
						$external_link = self::get_url($external_id, "external_order", true);

						// Editable fields
						if (($site_id == $multi_site->getLocalSiteID()) and $edit and ($type == "orders")) {
							$comments  = Core_Html::GuiInput( "comments_$order_id", $comments,
									array( "events" => "onchange=\"order_update_driver_comment('" . Freight::getPost() . "', $order_id)\"" ));
							$city = Core_Html::GuiInput("city_$order_id", $city,
								array( "events" => "onchange=\"order_update_field('" . Freight::getPost() . "', $order_id, 'city')\"" ));
							$address_1 = Core_Html::GuiInput("address_1_$order_id", $address_1,
								array( "events" => "onchange=\"order_update_field('" . Freight::getPost() . "', $order_id, 'address_1')\"" ));
							$address_2 = Core_Html::GuiInput("address_2_$order_id", $address_2,
								array( "events" => "onchange=\"order_update_field('" . Freight::getPost() . "', $order_id, 'address_2')\"" ));

						}

						$url = (($type == "supplies") ? Core_Html::GuiHyperlink($order_id, "/wp-admin/admin.php?page=supplies&operation=show_supply&id=$order_id") :
							Core_Html::GuiHyperlink( $order_id, "/wp-admin/post.php?post=$order_id&action=edit" ));
						$order_status = $order_info[OrderTableFields::max];
						$new_row = array_fill(0, DispatchTableFields::max,'');
						$new_row[DispatchTableFields::order_number] = $url;
						$new_row[DispatchTableFields::order_status] = self::getStatus($order_info);
						$new_row[DispatchTableFields::customer_name] = $user_name;
						$new_row[DispatchTableFields::city] = $city;
						$new_row[DispatchTableFields::address_1] = $address_1;
						$new_row[DispatchTableFields::address_2] = $address_2;
						$new_row[DispatchTableFields::comments] = $comments;
						$new_row[DispatchTableFields::external_id] = $external_link;
						$new_row[DispatchTableFields::phone] = $order_info[OrderTableFields::phone];
						if (! $print) $new_row[DispatchTableFields::delivered] = Core_Html::GuiCheckbox( "chk_$order_id", false,
									array( "events" => 'onchange="delivered(\'' . Freight::getPost() . "', " . $site_id . "," . $order_id . ', \'' . $type . '\')"' ) );
						$new_row[DispatchTableFields::eta] = date('H:i', $arrive_time);
						$new_row[DispatchTableFields::acc_km] = $total_distance;
						if ($edit) $new_row[DispatchTableFields::priority] = $pri_input;
						array_push( $path_info, $new_row);
					}
				} else {
					// TODO - check order when pickup
					$pickup_row = array_fill(0, DispatchTableFields::max, "");
					if (isset($this->point_orders[$this->path[$i]])) {
						$pickup_info = $this->point_orders[ $this->path[ $i ] ];
						$site_id     = $pickup_info[ OrderTableFields::site_id ];
						$order_id    = $pickup_info[ OrderTableFields::order_id ];;
						$order_pri                                       = self::order_get_pri( $pickup_info );
						$pickup_row[ DispatchTableFields::order_number ] = 'pickup';
						$pickup_row[ DispatchTableFields::city ]         = $this->path[ $i ];
						$pickup_row[ DispatchTableFields::acc_km ]       = $total_distance;
						$pickup_row[ DispatchTableFields::priority ]     = Core_Html::GuiInput( "pri" . $order_id . "_" . $site_id,
							$order_pri, array( "size"   => 5,
							                   "events" => 'onchange="update_order_pri(\'' . self::getPost() . '\',this)"'
							) );
						array_push( $path_info, $pickup_row );
					} else {
						print "no info for " . $this->path[$i] . "</br>";
					}
					// array_push($path_info, array(__("pickup"), '', $this->path[$i], '','','','','','', $total_distance));
				}

				$prev = $this->path[$i];
			}

			$args = array( "class" => "sortable" );

//		$args["links"] = array(1 => self::$multi_site->getSiteURL($site_id) . "/wp-admin/user-edit.php?user_id=%d");
//		var_dump($path_info[1]);
			$args["class"] = "widefat";
			$args["col_width"] = array(); $args["col_width"][3] = 10; // Not working...

//			$args["hide_cols"] = array( OrderTableFields::client_number - 1 => 1 );
			$result            .= Core_Html::gui_table_args( $path_info, "dispatch_" . $the_mission, $args );
			$result .= Core_Html::GuiButton("btn_number", "Set seq", "freight_set_seq($the_mission)");
			$result .= Core_Html::GuiButton("btn_number", "Reset seq", "freight_reset_seq($the_mission)");
			if (($end_address = $m->getEndAddress()))
				$total_distance += round(self::get_distance($prev, $end_address) / 1000, 1);

			$result .= Core_Html::GuiHeader(1, __("Total distance") . " $total_distance") .
				Core_Html::GuiHeader(1, __("Number of points") . " $point_number");
		}
		if (! $print) {
			$result .= self::add_delivery( $m->getDefaultFee() ); // , array("style" => "border:1px solid #000;")
			$result .= Core_Html::GuiHyperlink( "Download CSV", Freight::getPost() . "?operation=download_mission&id=$the_mission" ) . "<br/>";
			$result .= Core_Html::GuiHyperlink( "Print", Freight::getPost() . "?operation=print_mission&id=$the_mission" ) ."</br>";
			$result .= self::get_maps_url($m, $this->path);
		}
		FreightLog("done");
		$this->save();
		return $result;
	}

	function prepare_route($build = true) {
		$mission = new Mission($this->mission_id);

//		$this->get_route($mission_id, $debug);
		// Build the path
		if ($build or !$this->path) {
			$this->path = array($mission->getStartAddress());
			self::calculate_center();
			self::find_route_1( array_diff( $this->stop_points, array( $mission->getStartAddress() ) ), // Remove start address if appears in points.
				$mission->getEndAddress() );
			// return null;
		}
		return $this->lines_per_point;
	}

	function get_route($debug = false)
	{
		$mission_id = $this->mission_id;
		// Read from all sites.
		$data_url = WPF_Flavor::getPost() . "?operation=get_local_anonymous&mission_ids=$mission_id";

		$output   = self::$multi_site->GetAll( $data_url, false, $debug );

//		print $output;
		// Parse the output
		$rows = self::parse_output($output);

		if (count($rows) < 2) {
			return false;
		}

		self::add_loading_points();
		// Collect the points
		return self::collect_points( $rows, $mission_id);
	}

	static private function parse_output($output)
	{
		require_once( ABSPATH . 'vendor/simple_html_dom.php' );

		$rows = [];
		$dom = \Dom\str_get_html( $output );
		foreach ( $dom->find( 'tr' ) as $row ) {
			$new_row = [];
			for ($i = 0; $i <= OrderTableFields::max; $i++){
				$new_row[$i] = TableGetText($row, $i);
//				print $new_row[$i] . "<br/>";
			}

//			var_dump($new_row); print "<br/>";
			array_push($rows, $new_row);
		}

		return $rows;
	}

	static function getPost()
	{
		return WPF_Flavor::getPost();
	}

	// Calculates points_pe
	function collect_points($data_lines, $mission_id) {
		$debug = false;
		FreightLog(__FUNCTION__);

		$mission = new Mission( $mission_id );

		for ( $i = 1; $i < count( $data_lines ); $i ++ ) {
			$order_info = $data_lines[ $i ];

			$stop_point = trim(str_replace( '-', ' ', $order_info[ OrderTableFields::address_1 ] . " " . $order_info[ OrderTableFields::city ] ));
			if (! strlen($stop_point)) $stop_point = $mission->getStartAddress();
			if ($debug) print $stop_point ."<br/>";
			$order_id   = $order_info[ OrderTableFields::order_id ];
			$order_site = $order_info[ OrderTableFields::site_name ];
			$site_id    = $order_info[ OrderTableFields::site_id ];

			$pickup_address = Core_Db_MultiSite::getPickupAddress( $site_id );

			// Deliveries created in other place
//			if ( ($order_info['site'] != "משימות") and ($order_info['site'] != "supplies") and ($pickup_address != $mission->getStartAddress()) ) {
//			print "$pickup_address<br/>";
			// print "adding $pickup_address<br/>";
			// Add Pickup
//			self::add_stop_point( $pickup_address, $order_id, $order_info, $site_id );
//			self::add_line_per_station($pickup_address, $pickup_address, $order_info, $order_id);


			if ( $order_info[ OrderTableFields::site_name ] == "supplies" ) {
				array_push( $this->supplies_to_collect, array( $order_id, $order_info[ OrderTableFields::site_id ] ) );
			} else {
				$this->AddPrerequisite( $order_id, $pickup_address );
			}

			if(0) { /// Pickup lines.
				$pickup_order_info                                  = $order_info;
				$pickup_order_info[ OrderTableFields::client_name ] = "<b>העמסה</b> " . $order_info[ OrderTableFields::client_name ];
				$pickup_order_info[ OrderTableFields::address_2 ]   = '';
				$pickup_order_info[ OrderTableFields::phone ]       = '';
				// $this->prerequisite[$order_id] = $pickup_order_info[OrderTableFields::address_1];
				self::add_line_per_station( $mission->getStartAddress(),
					$pickup_address,
					$pickup_order_info,
					$order_id );
			}

			if (self::get_distance($mission->getStartAddress(), $stop_point) == -1) {
//				print "Address of order $order_id is not vaild. " . $stop_point . "<br/>";
				continue;
			} else
				self::add_stop_point( $stop_point, $order_info );
//			if (! isset($this->prerequisite[$stop_point])) {
//				$p = self::order_get_pri($order_id, $site_id);
//				if (strlen($p)) $this->prerequisite[$stop_point] = $p;
//			}

			self::add_line_per_station( $mission->getStartAddress(),
				$stop_point,
				$order_info,
				$order_id );
		}
		FreightLog("done");
		return (count ($this->stop_points) > 0);
	}

	function add_stop_point( $point, $order_info, $loading = false)
	{
		$multi = Core_Db_MultiSite::getInstance();

		$point = trim($point);
		$point = str_replace( '-', ' ', $point);
		if ( ! in_array( $point, $this->stop_points ) ) {
			array_push( $this->stop_points, $point);
			$this->point_orders[$point] = $order_info;
			if (! $loading)
				$this->prerequisite[$point] = $multi->getSiteAddress($order_info[OrderTableFields::site_id]);

		}
	}

	function add_line_per_station($start_address, $stop_point, $order_info, $order_id ) {
		$stop_point = trim($stop_point);
		if ( ! isset( $this->lines_per_point[ $stop_point ] ) ) {
			$this->lines_per_point[ $stop_point ] = array();
		}
		if ( self::get_distance( $start_address, $stop_point ) or ( $start_address == $stop_point ) ) {
			array_push( $this->lines_per_point[ $stop_point ], $order_info );
		} else {
			print "לא מזהה את הכתובת של הזמנה " . $order_id . "<br/>";
		}

		if (0 and $order_info[OrderTableFields::site_name] != 'supplies') {
			// collect point
			$start_address = trim( $start_address );
			if ( ! isset( $this->lines_per_point[ $start_address ] ) ) {
				// Add row for the pick-up.
				$new_row = [];
				for ($i = 0; $i < OrderTableFields::max; $i++) $new_row[$i] = '';
				$new_row[OrderTableFields::address_1]    = $start_address;
				$this->lines_per_point[ $start_address ] = array($new_row);
			}
			// Add info to pickup line.
			$this->lines_per_point[ $start_address ][0][ OrderTableFields::client_name ] .= $order_info[ OrderTableFields::client_name ] . "<br/>";
			$this->lines_per_point[ $start_address ][0][ OrderTableFields::order_id ]    .= $order_info[ OrderTableFields::order_id ] . "<br/>";
		}

	}

	static function geodecode_address($text)
	{
		// Check cache
		$r = InfoGet("geodecode_city" . $text);
		if ($r) {
			return unserialize($r);
		}

		// Get from google
		$url = "https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode( $text ).
		     "&key=" . MAPS_KEY . "&language=iw";

//		$ch = curl_init();
//		curl_setopt( $ch, CURLOPT_URL, $url );
//		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
//		$result = curl_exec( $ch );

		$result = GetContent($url);
		if (! $result) return null;

		$j = json_decode( $result );

		$city = '';
		$address_1= '';
		$address_2 = '';
		$street_number = '';

		foreach ($j->results as $result) {
			foreach ($result->address_components as $comp) {
				if (in_array("locality", $comp->types )) {
					$city = $comp->long_name;
				}
				if (in_array("route", $comp->types))
					$address_1 = $comp->long_name;

				if (in_array("street_number", $comp->types )) {
					$street_number = $comp->long_name;
				}
			}
		}
		if (strlen($city) < 2) return null;

		$address = array("city" => $city,
		                 "address_1" => $street_number . " " . $address_1,
		                 "address_2" => $address_2);

		FreightLog(__FUNCTION__ . " $city $street_number $address_1");

		// Save the cache
		InfoUpdate("geodecode_city". $text, serialize($address));
		return $address;
	}

	static function get_lat_long($address)
	{
		$r = InfoGet("lat_long" . $address);

		if ($r and strlen($r) > 1) {
			return array(strtok($r, ":"), strtok(null));
		}
		$s = "https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode( $address ).
		     "&key=" . MAPS_KEY; // . "&language=iw";

		// print $s;
		$result = GetContent( $s );

		$j = json_decode( $result );
		if (! isset ($j->results[0])) {
			return null;
		}
		$lat = $j->results[0]->geometry->location->lat;
		$long = $j->results[0]->geometry->location->lng;
		InfoUpdate("lat_long".  $address, $lat . ":" . $long);
		return array($lat, $long);
	}

	function get_center_distance($address): float {
		$lat_long = self::get_lat_long($address);
		return sqrt(($lat_long[0] - $this->center[0]) ** 2 +($lat_long[1] - $this->center[1]) ** 2);
	}

	function get_distance( $address_a, $address_b ) {
		if ( rtrim( $address_a ) == rtrim( $address_b ) ) {
			return 0;
		}
		if (isset($this->distance_matrix[$address_a][$address_b])) return $this->distance_matrix[$address_a][$address_b];

		$sql = "SELECT distance FROM im_distance WHERE address_a = '" . EscapeString( $address_a ) . "' AND address_b = '" .
		       EscapeString( $address_b ) . "'";
		// print $sql . " ";
		$ds  = SqlQuerySingleScalar( $sql );

		if ( $ds > 0 ) {
			if (! isset($this->distance_matrix[$address_a])) $this->distance_matrix[$address_a] = array();
			$this->distance_matrix[$address_a][$address_b] = $ds;
			return $ds;
		}
		$r = self::do_get_distance( $address_a, $address_b );
		if (! $r or ! is_array($r)) {
			return -1;
		}
		$distance = $r[0];
		$duration = $r[1];

//		$walk = self::do_get_distance( $address_a, $address_b, 'walking' );
//		if ($walk[1] < $duration) {
//			$distance = $walk[0];
//			$duration = $walk[1];
//		}
		if ( $distance > 0 ) {
			if (! isset($this->distance_matrix[$address_a])) $this->distance_matrix[$address_a] = array();
			$this->distance_matrix[$address_a][$address_b] = $distance;
			$sql1 = "insert into im_distance (address_a, address_b, distance, duration) VALUES 
				('" . EscapeString( $address_a ) . "', '" .
			        EscapeString(  $address_b ) . "', $distance, $duration)";
			SqlQuery( $sql1 );
			if ( SqlAffectedRows( ) < 1 ) {
				print "fail: " . $sql1 . "<br/>";
			}

			return $distance;
		}

		return - 1;
	}

//	function
	function find_route_1( $rest, $end )
	{
		if (! $rest or ! is_array($rest)) return;
		if ( count( $rest ) == 1 ) {
			array_push( $this->path, reset($rest ));
			return;
		}
		$this->find_route( $rest, $this->path );

		$best_cost = self::evaluate_path( $this->path, $end );

		// Try to put the last points next to neighbour.
		$continue = 5;
		$new_path = $this->path;

		while ($continue) {
			$point = end( $new_path );
			$close = $this->close_to( $point );
			$index = array_search( $close, $new_path );
			unset_by_value( $new_path, $point );
			array_splice( $new_path, $index + 1, 0, $point );

			$cost = self::evaluate_path($new_path, $end);
			if ($cost < $best_cost) {
				FreightLog(__FUNCTION__ . " cost $best_cost new_cost $cost");
				$this->path = $new_path;
				$best_cost = $cost;
			} else {
				$continue--;
			}
		}


//		$alter_path = [];
//		$this->find_route_alter($rest, $alter_path);

//		$best_cost = self::evaluate_path( $this->path, $end );

//		for ($i = 0; $i < count($this->path) - 1; $i++)
//		{
//			$next_dis = self::get_distance($this->path[$i], $this->path[$i+1]);
//			for ($j = $i + 2; $j < count($this->path); $j++) {
//				if (self::get_distance($this->path[$i], $this->path[$j]) < $next_dis) {
//
//				}
//			}
//		}
//		// Continue as long as switching adjacent nodes makes the route shorter
//		// Disable for now, because the preq is not implemented here.
//		$switched  = false;
//		while ( $switched ) {
//			$switched = false;
//			for ( $switch_node = 1; $switch_node < count( $this->path ) - 1; $switch_node ++ ) {
//				for ( $delta = 1; $delta < $switch_node; $delta ++) {
//					FreightLog("switch $switch_node $delta");
//					$alternate_path = $this->path;
//					$other = ($switch_node + $delta) % count($this->path);
//					swap( $alternate_path[ $switch_node ], $alternate_path[ $other ] );
//					$temp_cost = self::evaluate_path( $alternate_path, $end );
//					if ( $temp_cost < $best_cost ) {
//						$switched = true;
//						FreightLog( "switching " . $this->path[ $switch_node ] . " " . $this->path[ $switch_node + 1 ] );
//						swap( $this->path[ $switch_node ], $this->path[ $other ] );
//						$best_cost = $temp_cost;
//					}
//				}
//			}
//		}
	}

	function find_route( $rest, &$path ) {
		FreightLog(__FUNCTION__ . count($rest));

		$current_node = end($path);

		if ( sizeof( $rest ) == 1 ) { // End condition
			array_push( $path, reset( $rest ) );
			return true;
		}

		$candidates = $rest;
		// Remove points that does not meet preq.
//		foreach ( $candidates as $key => $candidate ) {
//			$order_id = $this->point_orders[ $candidate ][OrderTableFields::order_id];
//
//			if (isset($this->prerequisite[$order_id]))
//				if (isset($this->prerequisite[$order_id])) {
//					$diff = array_diff($this->prerequisite[$order_id], $path, array($candidate));
//					if (count($diff))
//						unset( $candidates[ $key ] );
//				}
//		}

		// If just 1 candidate remains go there. and recurse to continue.
		if ( sizeof( $candidates ) == 1 ) {
			$next = reset($candidates);
			array_push( $path, $next );
			unset_by_value($rest, $next);

			return $this->find_route( $rest, $path );
		}

		if (! $candidates or ! count($candidates)) {
			$candidates = array(reset($rest));
			die ( "nothing to work with" );
		}
		// Pick from the left points the point with minimum priority and distance.
		$selected          = array_shift( $candidates );
		$selected_priority = self::order_get_pri( $this->point_orders[ $selected ]);
		$selected_distance = self::get_distance( $current_node, $selected );

		foreach ( $candidates as $candidate ) {
			$candidate_priority = self::order_get_pri( $this->point_orders[ $candidate ]);
			$candidate_distance = self::get_distance( $current_node, $candidate ) + 30000 * (self::get_center_distance($candidate));
			if (($candidate_priority < $selected_priority) // Better priority
			    or (($candidate_priority == $selected_priority) and ($candidate_distance < $selected_distance)) // Same priority.
				or (false))
				/// It's  a pickup. ignore the prio
			{
				$selected          = $candidate;
				$selected_distance = $candidate_distance;
				$selected_priority = $candidate_priority;
			}
		}

		array_push( $path, $selected );
		unset_by_value($rest, $selected);
		return $this->find_route($rest, $path);
	}

	function evaluate_path( $elements, $end ) {
//	if ( $end < 1 ) {
//		print "end is " . $end . "<br/>";
//	}
		// $cost = get_distance( $start, $elements[0] );
		$cost = 0; // self::get_distance_duration( $start, $elements[0] );
		$size = sizeof( $elements );
//	print "size: " . $size . "<br/>";
		for ( $i = 1; $i < $size; $i ++ ) {
//		print "i = " . $i . " e[i-1] = " . $elements[$i-1] . " e[i] = " . $elements[$i] . "<br/>";
			$dis = self::get_distance( $elements[ $i - 1 ], $elements[ $i ] );
			if ($dis > -1)
				$cost += $dis;
		}
//	print "end = " . $end . "<br/>";
		$cost += self::get_distance( $elements[ $size - 1 ], $end );

		return $cost;
	}

	static function get_distance_duration( $address_a, $address_b ) {
		$sql = "SELECT duration FROM im_distance WHERE address_a = '" . EscapeString( $address_a ) .
		       "' AND address_b = '" . EscapeString( $address_b ) . "'";

		return SqlQuerySingleScalar( $sql );
	}

	static function get_maps_url($mission, $path)
	{
		$maps_base = "https://www.google.com/maps/dir/";
		$url = $maps_base . $mission->getStartAddress();
		$dynamic_url = "https://www.google.com/maps/dir/My+Location";
		$result = "";
		$m = 1;
		for ( $i = 0; $i < count( $path ); $i ++ ) {
			$add = urlencode(str_replace('#', '', $path[ $i ]));
			$url .= "/" . $add;
			$dynamic_url .= "/" . $add;
			if ($i % 10 == 9) {
				$result .= Core_Html::GuiHyperlink( "Map" . $m ++, $url ) . "<br/>";
				$url    = $maps_base . $add;
			}
		}
		$url .= "/" . $mission->getEndAddress();
		$result .= Core_Html::GuiHyperlink( "Maps" . $m++, $url ) . " " . Core_Html::GuiHyperlink("Dyn", $dynamic_url);
		$result .= Core_Html::GuiButton("btn_import", "Import", "freight_import('" . GetUrl() . "', import_div)");
		$result .= Core_Html::GuiDiv("import_div");
		return $result;
	}

	static function save_route($missions, $path) {
//    print "missions=$missions<br/>";
//    print "path=" . var_dump($path);
		! is_array( $missions ) or die ( "missions array" );

		SqlQuery( "update im_missions set path = \"" . EscapeString(CommaImplode($path, true)) . "\" where id = " . $missions );
	}

	static function do_get_distance( $a, $b, $mode = null ) {
		// $start = new DateTime();
		if ( trim($a) == trim($b) ) {
			return 0;
		}
		if ( is_null( $a ) or strlen( $a ) < 1 ) return null;

		if ( is_null( $b ) or strlen( $b ) < 1 ) return null;

		$s = "https://maps.googleapis.com/maps/api/directions/json?origin=" . urlencode( $a ) . "&destination=" .
		     urlencode( $b ) . "&key=" . MAPS_KEY . "&language=iw";

		if ($mode) $s .= "&mode=$mode";

		// print $s;
		$result = GetContent( $s );

		$j = json_decode( $result );

		if ( ! $j or ! isset( $j->routes[0] ) ) return null;

		$v = $j->routes[0]->legs[0]->distance->value;
		$t = $j->routes[0]->legs[0]->duration->value;

		if ( $v > 0 ) return array( $v, $t );

		return null;
	}

	static function order_get_pri($orders_info, $default = 100)
	{
		if (! is_array($orders_info[0]))
			$orders_info = array($orders_info);
		$pri = $default;
		foreach ($orders_info as $order_info) {
			$order_id = $order_info[ OrderTableFields::order_id ];
			$site_id  = $order_info[ OrderTableFields::site_id ];
			$i = InfoGet( "mission_order_priority_" . $site_id . '_' . $order_id );
			if ($i) $pri = min($pri, $i);
		}

		return $pri;
	}

	static function add_delivery($price, $args = null)
	{
		$mission_id = GetParam("id");
		$style = GetArg($args, "style", null);
		$result = "<div ";
		if ($style) $result .= "style=\"$style\"";
		$result .= ">";
		$result .= Core_Html::GuiHeader(1, "add delivery");

		$args = array("post_file" => Freight::getPost());
		$result .= __("Recipient") . "<br/>". Finance_Client::gui_select_client("delivery_client", null, $args) . "<br/>".
		 __("Price before taxes:") . "<br/>" .
		           Core_Html::GuiInput("delivery_price", $price, $args) . "<br/>" .
			Core_Html::GuiButton("btn_add_delivery", "Add", array("action" => "freight_add_delivery('" . Freight::getPost() . "', $mission_id)"));

		$result .= "</div>";
		return $result;
	}

	static function delivered($site_id, $type, $id, $debug = false)
	{
		$debug = false;
		if ( $debug ) {
			print "start<br/>";
		}
		if ( $site_id != Core_Db_MultiSite::LocalSiteID() ) {
			if ( $debug ) {
				print "remote.. ";
			}
			$request = Freight::getPost() . "?site_id=" . $site_id .
			           "&type=" . $type . "&id=" . $id . "&operation=delivered";
			if ( $debug ) {
				$request .= "&debug=1";
				print $request;
			}
//			print "X" . Core_Db_MultiSite::sExecute( $request, $site_id, $debug ) . "X<br/>";
			if ( check_for_error( Core_Db_MultiSite::sExecute( $request, $site_id, $debug ) )) {
				print "failed:<br/>";
				print $request;
				return false;
			}
			return true;
		}
		// Running local. Let's do it.
		// print "type=" . $type . "<br/>";
		$rc = false;
		switch ( $type ) {
			case "orders":
				$o = new Finance_Order( $id );
				$message = "";
				if ( ! $o->delivered($message) )
					print $message;
				else {
					$rc = true;
					Freight_Mission_Manager::clean($o->getMissionId());
				}
				break;
			case "tasklist":
				$t = new Focus_Tasklist( $id );
				$t->Ended();
				$rc = true;
				break;
			case "supplies":
				$s = new Fresh_Supply( $id );
				$s->picked();
				$rc =  true;
				break;
		}
		return $rc;
	}

	static function getOrder($order_id)
	{
//		if (class_exists("Fresh_Order"))
//			return new Fresh_Order($order_id);
		try {
			return new Finance_Order( $order_id );
		} catch (Exception $e)
		{
			return null;
		}
	}

	function AddPrerequisite($order_id, $pre_point)
	{
//		print "Adding $pre_point to $order_id<br/>";
		if (! isset($this->prerequisite[$order_id])) $this->prerequisite[$order_id] = array();
		if (! in_array($pre_point, $this->prerequisite[$order_id]))
			array_push($this->prerequisite[$order_id], trim($pre_point));
	}

	static function clean($mission_id)
	{
		InfoDelete("mission_$mission_id");
	}

	static function get_url($id, $type = "mission_info", $link = false)
	{
		$result = $id;
		switch ($type)
		{
			case "mission_info":
				$result =  "/wp-admin/admin.php?page=missions&id=" . $id;
			case "dispatcher":
				$result = "/wp-admin/admin.php?page=missions&week=2021-02-21&operation=mission_dispatch&id=" . $id;
			case "external_order":
				if ($id > '170000') { // baldar
					$result = "http://89.208.0.62/smartphone/TaskDetails.aspx?dlv=$id";
				}
		}
		if ($link) return Core_Html::GuiHyperlink($id, $result);
		return $result;
	}

	function add_loading_points()
	{
		$multi = Core_Db_MultiSite::getInstance();
		foreach ($multi->getSitesArray() as $site_id => $site_info)
		{
			if ($multi->getResults($site_id)){
				$pickup_info = array_fill(0, OrderTableFields::max, '');
				$pickup_info[OrderTableFields::order_id] = "loading";
				$pickup_info[OrderTableFields::site_id] = $site_id;
				self::add_stop_point($multi->getSiteAddress($site_id), $pickup_info, true);
			}
		}
	}

	function driver_page($current_point)
	{
		$result = Core_Html::GuiHeader(1, "Freight Driver Page", array("center"=>true));
		if (! $this->path) {
			return "no path. Open " . Core_Html::GuiHyperlink("dispatcher", self::get_url($this->mission_id, "dispatcher")) . " first";
		}
		for ($i = 0; $i < 3; $i ++)
		{
			if ($current_point == count($this->path)) {
				$result .= "That's All";
				break;
			}
			// Handle here delivered.
			// if ($this->point_orders[])
			$result .= "<div>";
			$result .= Core_Html::GuiHeader(2, $this->path[$i]);
			$current_address = $this->path[$i];
			if (isset($this->point_orders[$current_address])) {
				$order_info = $this->point_orders[$current_address];
				$result .=  "מסירה ללקוח " . $order_info[OrderTableFields::client_name] . "<br/>";
				if ($order_info[OrderTableFields::external_order_id]) {
					$result .= "הזמנת בלדר " . self::get_url($order_info[OrderTableFields::external_order_id], "external_order", true);
				}
				$result .= Core_Html::GuiButton("btn_delivered", __("Delivered"), "delivered");
			} else {
				$result .= " העמסה" . $current_address;
			}
//			foreach ($this->lines_per_station[$current_address] as $order) {
//
//			}
			$result .= "</div>";
		}
//		$result .= Core_Html::GuiHeader(2, $m->getMissionName(), array("center"=>true));

		return $result;
	}

	static function create_missions()
	{
		$types = SqlQueryArrayScalar("select id from im_mission_types");
		foreach ($types as $type) {
			Mission::CreateFromType($type);
		}
		return true;
	}

	static function update_shipping_methods($result = null)
	{
		FreightLog(__FUNCTION__);
		// Otherwise - master - update.
		$sql = "select * from wp_woocommerce_shipping_zone_methods";
		$sql_result = SqlQuery($sql);
		while ($row = SqlFetchAssoc($sql_result)) {
			$instance_id = $row['instance_id'];
			self::update_shipping_method($instance_id);
		}
	}

	static function update_shipping_method($instance_id) //, $date, $start, $end, $price = 0)
	{
		$args                = [];
		$args["is_enabled"]  = 1;
		$args["instance_id"] = $instance_id;
		$method_info = SqlQuerySingleAssoc("select mission_code from wp_woocommerce_shipping_zone_methods where instance_id = $instance_id");
		$mission_type = $method_info['mission_code'];
		if ($mission_type)
			$week_day = SqlQuerySingleScalar("select week_day from im_mission_types where id = $mission_type");
		else
			$week_day = 2;

		if (! $week_day) return false;
		$start = "13";
		$end = "18";
		$date = next_weekday($week_day);
		$args["title"]       = DateDayName( $date ) . " " . date('d-m-Y', strtotime($date)) . ' ' . $start . "-". $end;

		return self::update_woocommerce_shipping_zone_methods($args);
	}

	static function update_woocommerce_shipping_zone_methods($args) {
		$instance_id = GetArg( $args, "instance_id", null );
		if ( ! ( $instance_id > 0 ) ) {
			print __ ( "Error: #R1 invalid instance_id" );
			return false;
		}

		// Updating directly to db. and prepare array to wp_options
		$sql           = "update wp_woocommerce_shipping_zone_methods set ";
		$table_list    = array( "is_enabled", "method_order" ); // Stored in the wp_woocommerce_shipping_zone_methods table
		$update_table  = false;
		$update_option = false;
		$option_id     = 'woocommerce_flat_rate_' . $instance_id . '_settings';
		$options       = get_wp_option( $option_id );

		foreach ( $args as $k => $v ) {
			if ( ! in_array( $k, $table_list ) ) {
				$options[ $k ] = $v;
				$update_option = true;
				continue;
			}
			$sql          .= $k . "=" . QuoteText( $v ) . ", ";
			$update_table = true;
		}
		if ( $update_table ) {
			$sql = rtrim( $sql, ", " );
			$sql .= " where instance_id = " . $instance_id;
			if ( ! SqlQuery( $sql ) ) {
				return false;
			}
		}

		if ( $update_option ) {
			return update_wp_option( 'woocommerce_flat_rate_' . $instance_id . '_settings', $options );
		}
		return false;
	}

	// Update in master site mission and shipments.
	static public function update_mission_shipping()
	{
		FreightLog(__FUNCTION__);
		$multi = Core_Db_MultiSite::getInstance();

		if (! $multi->isMaster()) return;

		Freight_Mission_Manager::create_missions();
		Freight_Mission_Manager::update_shipping_methods();
		FreightLog(__FUNCTION__ . " done");
	}

	static function getStatus($order_info)
	{
		$order_status = $order_info[OrderTableFields::order_status];
		if ($order_status == 'wc-processing') return "V";
//		return "X";

		$order_id = $order_info[OrderTableFields::order_id];
		if ($order_info[OrderTableFields::site_id] == self::$multi_site->getLocalSiteID())
		return Core_Html::GuiHyperlink("+",
		                                    wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=processing&order_id=' .
		                                                             $order_id ), 'woocommerce-mark-order-status' )
		                                    );

		return "X";
	}

	function map_center()
	{
		$min_long = 360;
		$max_long = 0;
		$min_lat = 360;
		$max_lat = 0;
		foreach ($this->stop_points as $point) {
			$long_lat = self::get_lat_long($point);
			if (! $long_lat) continue;
			if ($long_lat[0] > $max_lat) $max_lat = $long_lat[0];
			if ($long_lat[0] < $min_lat) $min_lat = $long_lat[0];
			if ($long_lat[1] > $max_long) $max_long = $long_lat[1];
			if ($long_lat[1] < $min_long) $min_long = $long_lat[1];
		}
		$max_size = max($max_long - $min_long, $max_lat - $min_lat) * 6378137;

		$this->zoom = round(log($max_size / 1128.497220, 2), 0);
		return ($min_lat + $max_lat) / 2 . ", " .
		       ($min_long + $max_long) / 2;
	}

	function zoom()
	{
		if ($this->zoom > 0 and $this->zoom< 20)
		return $this->zoom;
		return 12;
	}

	function calculate_center()
	{
		$this->center = array(0, 0);
		$count = 0;
		foreach ($this->stop_points as $point)
		{
			$lat_long = self::get_lat_long($point);
			$this->center[0] += $lat_long[0];
			$this->center[1] += $lat_long[1];
			$count ++;
		}
		if (! $count) return;
		$this->center[0] /= $count;
		$this->center[1] /= $count;
	}

	function close_to($address_b)
	{
		$close = null;
		$distance = null;
		foreach ($this->distance_matrix as $address_a => $values){
			if (isset($this->distance_matrix[$address_a][$address_b]) and (! $distance or $distance > $this->distance_matrix[$address_a][$address_b]))
			{
				$distance =$this->distance_matrix[$address_a][$address_b];
				$close = $address_a;
			}
		}
		return $close;
	}
}