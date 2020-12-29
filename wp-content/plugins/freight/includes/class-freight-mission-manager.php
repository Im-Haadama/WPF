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
	const order_number = 1;
	const client_nubmer = 2;
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
	const max = 13;
}

class Freight_Mission_Manager 
{
	static $multi_site;
	private $points_per_sites; // 2D array: site_id and order_names;
	private $lines_per_station;
	private $prerequisite;
	private $supplies_to_collect;
	private $stop_points;
	private $point_sites;
	private $point_orders;

	static private $_instance;

	/**
	 * Freight_Mission_Manager constructor.
	 */

	public function __construct( ) {
		$this->points_per_sites    = array();
		$this->lines_per_station   = array();
		$this->prerequisite        = array();
		$this->supplies_to_collect = array();
		$this->stop_points         = array();
		$this->point_sites         = array();
		$this->point_orders        = array();
	}

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	function init_hooks(Core_Hook_Handler $loader)
	{
//		print debug_trace(10); print "---------------------<br/>";
		add_action("order_save_pri", __CLASS__ . '::order_save_pri');
		add_action("mission_update_type", __CLASS__ . '::mission_update_type');
		add_action("mission_details", __CLASS__ . '::mission_details');
		add_action("freight_do_add_delivery", __CLASS__ . "::do_add_delivery");
		add_action('delivered', array(__CLASS__, "delivered_wrap"));
		add_action('download_mission', array($this, 'download_mission'));
		add_action('print_mission', array($this, 'print_mission'));
		AddAction('order_update_driver_comment', array(__CLASS__, 'order_update_driver_comment'));
		$loader->AddAction("freight_do_import", $this);
	}

	function freight_do_import_wrap($mission_id)
	{
		if (! isset($_FILES["fileToUpload"]["tmp_name"])) {
			print "No file selected";

			return;
		}

		$file_name = $_FILES["fileToUpload"]["tmp_name"];

		$file = fopen( $file_name, "r" );
		$header = fgetcsv( $file ); // Skip header.
		$customer = new Finance_Client(1);
		$zone = $customer->getZone();
		$the_shipping = null;
		foreach ($zone->get_shipping_methods(true) as $shipping_method) {
			// Take the first option.
			$the_shipping = $shipping_method;
			break;
		}
		while ($line = fgetcsv( $file ))
		{
			$order_id = $line[0];
			$client_name = $line[1];
			$address1 = $line[2];
			$address2 = $line[3];
			$city = $line[4];
			$comments = $line[5];
			$phone = $line[6];

			$delivery_info = array(
			'shipping_first_name' => $client_name,
				'shipping_last_name' => '',
				'shipping_address_1' => $address1,
				'shipping_address_2'=> $address2,
				'shipping_city'=>$city,
				'shipping_postcode'=>'',
				'billing_phone'=>$phone
			);

			$O = Finance_Order::CreateOrder(1, $mission_id, null, $the_shipping, $comments, 10, $delivery_info);
			print "Created order  " . $O->GetID() . " client $client_name<br/>";
		}
	}

	function print_mission()
	{
		$id = GetParam("id", true);
		print Core_Html::HeaderText();
		// The route stops.
		$args = array("print" => true, "edit" => false);
		print $this->dispatcher($id, $args);

		// Supplies to collect
		$supplies = Fresh_Supplies::mission_supplies($id);
		foreach ($supplies as $supply_id) {
			$s = new Fresh_Supply($supply_id);
			print $s->Html($args) ;
		}
		die(0);
	}

	function download_mission()
	{
		$id = GetParam("id", true, "");
		$file = $this->getCSV($id);
		$date = date('Y-m-d');
		$file_name = "mission_${id}_${date}.csv";

		header("Content-Disposition: attachment; filename=\"" . $file_name . "\"");
		header("Content-Type: application/octet-stream");
		header("Content-Length: " . strlen($file));
		header("Connection: close");
		print $file;
		die (0);
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

		$this->prepare_route($the_mission, $path);

		if (! $path or ! count($path))
			return;

		for ( $i = 0; $i < count( $path ); $i ++ ) {
			if (isset($this->lines_per_station[$path[$i]]))
				foreach ( $this->lines_per_station[ $path[ $i ] ] as $order_info ) {
					if (! is_array($order_info) or count($order_info) < 10)
					{
						var_dump($order_info);
						continue;
					}
					$order_id  = $order_info[ OrderTableFields::order_number ];
					$site_id   = $order_info[ OrderTableFields::site_id ];
					$order_pri = self::order_get_pri( $order_id, $site_id );
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

		$result = "";
		foreach ($path_info as $row) {
			foreach ($row as $cell)
				$result .= str_replace(array(',', "<br/>", PHP_EOL, "\r"), ' ', $cell) . ",";
			$result .=  PHP_EOL;
		}

		return $result;
	}

	static function gui_select_mission_type($id, $selected, $args)
	{
		$args["selected"] = $selected;
		$args["name"] = 'mission_name';
		return Core_Html::GuiSelectTable($id, "mission_types", $args);
	}

	static function dispatcher_wrap()
	{
		$operation = GetParam("operation", false, null);
		$header = __("Dispatch mission") ;
		$week = GetParam("week", false, null);
		if ($week)
			$header .= __("Missions of week") . " " . $week;

		self::update_missions_from_master();

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

	function dispatcher($the_mission, $args)
	{
		$edit = GetArg($args, "edit", true);
		$print = GetArg($args, "print", false);

		$multi_site = Core_Db_MultiSite::getInstance();
		$result = "";
		$post_file = Flavor::getPost();

		$this->prepare_route($the_mission, $path);

		$m = new Mission($the_mission);

		$arrive_time = strtotime( $m->getStartTime());
		$prev           = $m->getStartAddress();
		$total_distance = 0;

		if ($path and count($path)) {
			$result .= Core_Html::GuiHeader(1, __("Details for dispatch number") . " " . $the_mission);
			if (! $print) $result .= "<div>" . __("Mission start") . ":" . Core_Html::gui_input_time("start_h", null, $m->getStartTime(),
					"onchange=\"update_table_field('" . $post_file . "', 'missions', $the_mission, 'start_h', location_reload)\"") . "</div>";
			$result .= Core_Html::GuiHeader(2, $m->getDate());
			$path_info = [];
			for ( $i = 0; $i < count( $path ); $i ++ ) {
				if ( isset( $this->lines_per_station[ $path[ $i ] ] ) ) {
					foreach ( $this->lines_per_station[ $path[ $i ] ] as $order_info ) {
						$arrive_time += 6 *60; // 6 minutes
						$order_id  = $order_info[ OrderTableFields::order_number ];
						$site_id   = $order_info[ OrderTableFields::site_id ];
						$site      = $order_info[ OrderTableFields::site_name ];
						$user_id   = $order_info[ OrderTableFields::client_nubmer ];
						$order_pri = self::order_get_pri( $order_id, $site_id );
						$user_name = $order_info[ OrderTableFields::client_name ];
						$pri_input = Core_Html::GuiInput( "pri" . $order_id . "_" . $site_id, $order_pri, array( "size"   => 5,
						                                                                                         "events" => 'onchange="update_order_pri(\'' . self::getPost() . '\',this)"'
							) ) .
						             Core_Html::GuiButton( "btn_reset_reset", "R", array( "action" => "reset_path('" . self::getPost() . "'," . ( $i + 1 ) . ")" ) );
						$type      = "orders";
						if ( $site == "supplies" ) {
							$type = "supplies";
						} else if ( $site == "משימות" ) {
							$type = "tasklist";
						}

						// Time calculation
						$distance       = round( self::get_distance( $prev, $path[ $i ] ) / 1000, 1 );
//						print "dis=$distance<br/>";
//						if (! $first ) {
							$total_distance += $distance;
							$duration       = round( self::get_distance_duration( $prev, $path[ $i ] ) / 60, 0 );
//						} else {
//							$duration = 5;
//							$first          = false;
//						}
						$arrive_time += $duration * 60;

						$edit_user = Core_Html::GuiHyperlink( $user_id, self::$multi_site->getSiteURL( $site_id ) . "/wp-admin/user-edit.php?user_id=" . $user_id );
						$comments  = ( ( $site_id == $multi_site->getLocalSiteID() and $edit) ?
							Core_Html::GuiInput( "comments_$order_id",
								$order_info[ OrderTableFields::comments ],
								array( "events" => "onchange=\"order_update_driver_comment('" . Freight::getPost() . "', $order_id)\"" )
							) : $order_info[ OrderTableFields::comments ] );
						$url = (($type == "supplies") ? Core_Html::GuiHyperlink($order_id, "/wp-admin/admin.php?page=supplies&operation=show_supply&id=$order_id") :
							Core_Html::GuiHyperlink( $order_id, "/wp-admin/post.php?post=$order_id&action=edit" ));
						$new_row =
							array(
								$url,
								$edit_user,
								Core_Html::GuiHyperlink( $user_name, $multi_site->getSiteURL( $site_id ) . "/wp-admin/user-edit.php?user_id=$user_id" ),
								$path[ $i ],
								$order_info[ OrderTableFields::address_2 ],
								$comments,
								$order_info[ OrderTableFields::shipping ],
								$order_info[ OrderTableFields::phone ]);
						if (! $print) array_push($new_row,  Core_Html::GuiCheckbox( "chk_$order_id", false,
									array( "events" => 'onchange="delivered(\'' . Freight::getPost() . "', " . $site_id . "," . $order_id . ', \'' . $type . '\')"' ) ));
						array_push($new_row, date('H:i', $arrive_time));
						array_push($new_row, $total_distance);

						if ($edit) array_push($new_row, $pri_input);
						array_push( $path_info, $new_row);
					}
				} else {
//					var_dump($lines_per_station[$path[$i]]);
					print "no lines per station $path[$i]<br/>";
				}

				$prev = $path[ $i];
			}

			$args = array( "class" => "sortable" );
			$header = array(
				__( "Order number" ),
				__( "Customer name" ),
				__( "Address 1" ),
				__( "Address 2" ),
				__( "Comments" ),
				__( "Shipping method" ),
				__( 'Phone' )
			);
			if (! $print) array_push($header, __( 'Delivered' ));
			if ($edit) array_push($header, __( 'Priority' ));
			array_unshift( $path_info, $header);

//		$args["links"] = array(1 => self::$multi_site->getSiteURL($site_id) . "/wp-admin/user-edit.php?user_id=%d");
//		var_dump($path_info[1]);
			$args["class"] = "widefat";

			$args["hide_cols"] = array( OrderTableFields::client_nubmer - 1 => 1 );
			$result            .= Core_Html::gui_table_args( $path_info, "dispatch_" . $the_mission, $args );
			$total_distance += round(self::get_distance($prev, $m->getEndAddress()) / 1000, 1);

			$result .= __("Total distance") . ": $total_distance<br/>";
		}
		if (! $print) {
			$result .= self::add_delivery( $m->getDefaultFee() ); // , array("style" => "border:1px solid #000;")
			$result .= Core_Html::GuiHyperlink( "Download CSV", Freight::getPost() . "?operation=download_mission&id=$the_mission" ) . "<br/>";
			$result .= Core_Html::GuiHyperlink( "Print", Freight::getPost() . "?operation=print_mission&id=$the_mission" ) ."</br>";
			$result .= self::get_maps_url($m, $path);
		}
		return $result;
	}

	function prepare_route($mission_id, &$path) {
		$mission = new Mission($mission_id);
		$debug = false;

		// Read from all sites.
		self::$multi_site = Core_Db_MultiSite::getInstance();
		$data_url = Flavor::getPost() . "?operation=get_local_anonymous&mission_ids=$mission_id";
		$output   = self::$multi_site->GetAll( $data_url, false, $debug );

		// Parse the output
		$rows = self::parse_output($output);

		// Collect the points
		self::collect_points( $rows, $mission_id);

		$path = array($mission->getStartAddress());

		// Build the path
		self::find_route_1(array_diff($this->stop_points, array($mission->getStartAddress())), // Remove start address if appears in points.
			$path, false, $mission->getEndAddress() );

	}

	static private function parse_output($output)
	{
		require_once( ABSPATH . 'vendor/simple_html_dom.php' );

		$rows = [];
		$dom = \Dom\str_get_html( $output );
		foreach ( $dom->find( 'tr' ) as $row ) {
			$new_row = [];
			for ($i = 0; $i < OrderTableFields::max; $i++)
				$new_row[$i] = TableGetText($row, $i);

//			var_dump($new_row); print "<br/>";
			array_push($rows, $new_row);
		}

		return $rows;
	}

	static function mission_details()
    {
    	$id = GetParam("id", true);
    }

	static function getPost()
	{
		return Flavor::getPost();
	}

	function collect_points($data_lines, $mission_id) {
		$multisite         = Core_Db_MultiSite::getInstance();
		$this->stop_points = array();

		$mission = new Mission( $mission_id );

//		for ( $i = 0; $i < count( $data_lines); $i ++ )
//		{
//			$order_info = $data_lines[$i];
//			print $order_info[OrderTableFields::client_name] . "<br/>";
//		}
		for ( $i = 0; $i < count( $data_lines ); $i ++ ) {
			$order_info = $data_lines[ $i ];
			$stop_point = str_replace( '-', ' ', $order_info[ OrderTableFields::address_1 ] . " " . $order_info[ OrderTableFields::city ] );
			$order_id   = $order_info[ OrderTableFields::order_number ];
			$order_site = $order_info[ OrderTableFields::site_name ];
			$site_id    = $order_info[ OrderTableFields::site_id ];

			// Collect information about pickup points.
			if ( ! isset( $this->points_per_sites[ $site_id ] ) ) {
				$this->points_per_sites[ $site_id ] = array();
			}
			array_push( $this->points_per_sites[ $site_id ], $order_id );

			$pickup_address = Core_Db_MultiSite::getPickupAddress( $site_id );

			// Deliveries created in other place
//			if ( ($order_info['site'] != "משימות") and ($order_info['site'] != "supplies") and ($pickup_address != $mission->getStartAddress()) ) {
//			print "$pickup_address<br/>";
			// print "adding $pickup_address<br/>";
			// Add Pickup
			self::add_stop_point( $pickup_address, $order_id, $site_id );

			if ( $order_info[ OrderTableFields::site_name ] == "supplies" ) {
				array_push( $this->supplies_to_collect, array( $order_id, $order_info[ OrderTableFields::site_id ] ) );
			} else {
				$this->AddPrerequisite( $order_id, $pickup_address );
			}

			if (0) { /// Pickup lines.
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

			self::add_stop_point( $stop_point, $order_id, $site_id );
//			if (! isset($this->prerequisite[$stop_point])) {
//				$p = self::order_get_pri($order_id, $site_id);
//				if (strlen($p)) $this->prerequisite[$stop_point] = $p;
//			}

			self::add_line_per_station( $mission->getStartAddress(),
				$stop_point,
				$order_info,
				$order_id );

			// Check if we need to collect something on the go
			if ( $site_id == $multisite->getLocalSiteID() and $order_site != "supplies" ) {
				$order = self::getOrder($order_id);
				if ($order instanceof Fresh_Order and $supply_points = $order->SuppliersOnTheGo() ) {
					$order = new Fresh_Order( $order_id );
					if ( $supply_points = $order->SuppliersOnTheGo( $mission_id ) ) {
						$supplier = new Fresh_Supplier( $supply_points[0] );
						$this->AddPrerequisite( $order_id, $supplier->getAddress() );
					}
				}
			}
		}
	}

	function add_stop_point( $point, $order_id, $site_id)
	{
		$point = trim($point);
		if ( ! in_array( $point, $this->stop_points ) ) {
			array_push( $this->stop_points, $point);
			$this->point_sites[$point] = $site_id;
			$this->point_orders[$point] = $order_id;
		}
	}

	function add_line_per_station($start_address, $stop_point, $order_info, $order_id ) {
		$stop_point = trim($stop_point);
		if ( ! isset( $this->lines_per_station[ $stop_point ] ) ) {
			$this->lines_per_station[ $stop_point ] = array();
		}
		if ( self::get_distance( $start_address, $stop_point ) or ( $start_address == $stop_point ) ) {
			array_push( $this->lines_per_station[ $stop_point ], $order_info );
		} else {
			print "לא מזהה את הכתובת של הזמנה " . $order_id . "<br/>";
		}

		if ($order_info[OrderTableFields::site_name] != 'supplies') {
			// collect point
			$start_address = trim( $start_address );
			if ( ! isset( $this->lines_per_station[ $start_address ] ) ) {
				// Add row for the pick-up.
				$new_row = [];
				for ($i = 0; $i < OrderTableFields::max; $i++) $new_row[$i] = '';
				$new_row[OrderTableFields::address_1] = $start_address;
				$this->lines_per_station[ $start_address ] = array($new_row);
			}
			// Add info to pickup line.
			$this->lines_per_station[ $start_address ][0][ OrderTableFields::client_name ]  .= $order_info[ OrderTableFields::client_name ] . "<br/>";
			$this->lines_per_station[ $start_address ][0][ OrderTableFields::order_number ] .= $order_info[ OrderTableFields::order_number ] . "<br/>";
		}

	}

	static function get_distance( $address_a, $address_b ) {
		if ( 0) {
			print "a: " . $address_a . "<br/>";
			print "b: " . $address_b . "<br/>";
		}
		if ( rtrim( $address_a ) == rtrim( $address_b ) ) {
			return 0;
		}
		$sql = "SELECT distance FROM im_distance WHERE address_a = '" . EscapeString( $address_a ) . "' AND address_b = '" .
		       EscapeString( $address_b ) . "'";
		// print $sql . " ";
		$ds  = SqlQuerySingleScalar( $sql );

		if ( $ds > 0 ) {
			return $ds;
		}
		$r = self::do_get_distance( $address_a, $address_b );
		if ( $r  == -1 or ! is_array($r)) {
			// One is invalid
			return -1;
		}
		$distance = $r[0];
		$duration = $r[1];
		if ( $distance > 0 ) {
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

	function find_route_1( $rest, &$path, $print, $end )
	{
		if (! $rest or ! is_array($rest)) return;
		if ( count( $rest ) == 1 ) {
			array_push( $path, $rest[0] );
			return;
		}
		$this->find_route( $rest, $path );

		$best_cost = self::evaluate_path( $path, $end );

		if ($print) {
			print "first guess route<br/>";
			print_path( $path );
		}

		// Continue as long as switching adjacent nodes makes the route shorter
		// Disable for now, because the preq is not implemented here.
		$switched  = false;
		while ( $switched ) {
			$switched = false;
			for ( $switch_node = 1; $switch_node < count( $path ) - 1; $switch_node ++ ) {
				$alternate_path = $path;
				swap( $alternate_path[ $switch_node ], $alternate_path[ $switch_node + 1 ] );
				$temp_cost = evaluate_path( $node, $alternate_path, $end );
				if ( $temp_cost < $best_cost ) {
					if ( $print ) {
						print "Best: " . $temp_cost . " " . $switch_node . " " . $path[ $switch_node ] . " " .
						      $path[ $switch_node + 1 ] . "<br/>";
					}
					$switched = true;
					swap( $path[ $switch_node ], $path[ $switch_node + 1 ] );
					$best_cost = $temp_cost;
				}
			}
		}
	}

	// Make sure that point doesn't appear twice
	function find_route( $rest, &$path ) {
		$debug = false;

		$current_node = end($path);
		if ($debug) {
			print "================ START. cur=$current_node ====================<br/>";
//			print debug_trace(10);
		}

		if ( sizeof( $rest ) == 1 ) {
			if ($debug) print "just 1 left. Done<br/>";
			array_push( $path, reset( $rest ) );
			return true;
		}

		if ($debug) var_dump($rest);
		$candidates = $rest;
		// Remove points that does not meet preq.
		foreach ( $candidates as $key => $candidate ) {
			$order_id = $this->point_orders[ $candidate ];

			if (isset($this->prerequisite[$order_id]))
				if (0 and $debug) print "<br/>checking preq for $candidate: ";
				if (isset($this->prerequisite[$order_id])) {
					$diff = array_diff($this->prerequisite[$order_id], $path, array($candidate));
					if (0 and $debug) var_dump($diff);
					if ($diff) unset( $candidates[ $key ] );
				}
		}

		// If just 1 candidate remains go there.
		if ( sizeof( $rest ) == 1 ) {
			if ($debug) {
				print "just " . reset($rest) . " left<br/>";
			}
			array_push( $path, reset( $rest ) );

			return;
		}

		if (! $candidates or ! count($candidates)) {
			$candidates = array(reset($rest));
			print ( "nothing to work with" );
		}
		// Pick from the left points the point with minimum priority and distance.
		if ($debug) {
			print "<br/>checking candidates<br/>";
			var_dump( $candidates );
		}
		$selected          = array_shift( $candidates );
		$selected_priority = self::order_get_pri( $this->point_orders[ $selected ], $this->point_sites[ $selected ] );
		$selected_distance = self::get_distance( $current_node, $selected );
		if ($debug) print "<br/>checking $selected $selected_priority $selected_distance";

		foreach ( $candidates as $candidate ) {
			$candidate_priority = self::order_get_pri( $this->point_orders[ $candidate ], $this->point_sites[ $candidate ] );
			$candidate_distance = self::get_distance( $current_node, $candidate );
			if ($debug) print "<br/>evaluating $candidate pri=$candidate_priority dis=$candidate_distance sp=$selected_priority sd=$selected_distance";
			if (($candidate_priority < $selected_priority) // Better priority
			    or (($candidate_priority == $selected_priority) and ($candidate_distance < $selected_distance)) // Same priority.
				or (false))
				/// It's  a collection. ignore the prio
			{
				if ($debug) print " taken";
				$selected          = $candidate;
				$selected_distance = $candidate_distance;
				$selected_priority = $candidate_priority;
			}
		}

		if ($debug)
		{
			print "--------------------------- Selected: $selected ------------------<Br/>";
		}
		array_push( $path, $selected );
		// Remove it from $rest
		foreach ( $rest as $key => $item ) {
			if (0 and $debug) print "searching $item<br/>";
			if ( trim( $rest[ $key ] ) == trim( $selected ) ) {
				if (0 and $debug) print "removing " . $rest[$key] . "<br/>";
				unset ( $rest[ $key ] );
				return $this->find_route( $rest, $path );
			}
		}
		die ("Hee????");
	}

	static function evaluate_path( $elements, $end ) {
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

	static function do_get_distance( $a, $b ) {
		// $start = new DateTime();
		if ( trim($a) == trim($b) ) {
			return 0;
		}
		if ( is_null( $a ) or strlen( $a ) < 1 ) return -1;

		if ( is_null( $b ) or strlen( $b ) < 1 ) return -1;

//	debug_time1("google start");
		$s = "https://maps.googleapis.com/maps/api/directions/json?origin=" . urlencode( $a ) . "&destination=" .
		     urlencode( $b ) . "&key=" . MAPS_KEY . "&language=iw";

		// print $s;
		$result = file_get_contents( $s );
//	debug_time1("google end");

		$j = json_decode( $result );

		if ( ! $j or ! isset( $j->routes[0] ) ) {
			print "Can't find distance between '" . $a . "' and '" . $b . "'<br/>";

			return null;
		}

		$v = $j->routes[0]->legs[0]->distance->value;
		$t = $j->routes[0]->legs[0]->duration->value;

//	$end = new DateTime();
//
//	$delta = $start->diff($end)->format("%s");
//	// var_dump($delta); print "<br/>"; // ->format("%s");
//	// print "diff: " . $sec . "<br/>";
//	if ($delta > 0) {
//		print "בדוק כתובות" . $a . " " . $b . "<br/>";
//	}
		if ( $v > 0 ) {
			return array( $v, $t );
		}

		print "can't find distance between " . $a . " " . $b . "<br/>";

		return null;
	}

	static function order_get_pri($order_id, $site_id, $default = 100)
	{
		if (! ($order_id > 0))
		{
			return "bad order_id $order_id";
		}
		$i = Core_Options::info_get("mission_order_priority_" . $site_id . '_' .$order_id);

		if ($i) return $i;
		return $default;
	}

	static function order_save_pri()
	{
		$order_id = GetParam("order_id", true);
		$site_id = GetParam("site_id", true);
		$pri = GetParam("pri", true);

	//			print info_get("mission_order_priority_" . $site_id . '_' .$order_id);
		// TEMP: Remove duplicates.
		Core_Options::info_remove("mission_order_priority_" . $site_id . '_' .$order_id);

		if ($pri > 0)
			return InfoUpdate("mission_order_priority_" . $site_id . '_' .$order_id, $pri);
		return false;

	}

	static function mission_update_type()
	{
		$mission_id = GetParam("mission", true);
		$type = GetParam("type", true);

		$m = new Mission($mission_id);
		return $m->setType($type);
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
		$result .= __("Recipient") . "<br/>"
		. Fresh_Client::gui_select_client("delivery_client", null, $args) . "<br/>".
		 __("Price before taxes:") . "<br/>" .
		           Core_Html::GuiInput("delivery_price", $price, $args) . "<br/>" .
			Core_Html::GuiButton("btn_add_delivery", "Add", array("action" => "freight_add_delivery('" . Freight::getPost() . "', $mission_id)"));

		$result .= "</div>";
		return $result;
	}

	static function do_add_delivery()
	{
		$client = GetParam("client", true);
		$fee = GetParam("fee", true);
		$mission_id = GetParam("mission_id", true);

		$customer = new Fresh_Client($client);
		$zone = $customer->getZone();
		if (! $zone) {
			return false;
		}
		$the_shipping = null;
		foreach ($zone->get_shipping_methods(true) as $shipping_method) {
			// Take the first option.
			$the_shipping = $shipping_method;
			break;
		}
		if (! $the_shipping) {
//			print "No shipping method to zone " . $zone->get_zone_name();
			return false;
		}

		$o = Finance_Order::CreateOrder( $client, $mission_id, null, $the_shipping,
			" משלוח המכולת " . date( 'Y-m-d' ) . " " . $customer->getName(), Fresh_Pricing::addVat($fee));

		if (! $o)
			return false;
		$o->setStatus( 'wc-processing' );
//		$o->setMissionID($mission_id);

		return true;
	}

	static function print_deliveries( $mission_id, $selectable = false, $debug = false ) {
		$data = "";
		$sql  = 'SELECT posts.id, order_user(posts.id) '
		        . ' FROM `wp_posts` posts'
		        . ' WHERE ' .
		        " `post_status` in ('wc-awaiting-shipment', 'wc-processing') and " .
		        " id in (select post_id from wp_postmeta " .
		        " WHERE meta_key = 'mission_id' " .
		        " AND meta_value = " . $mission_id . ") ";
		$sql .= ' order by 1';

		$orders    = SqlQuery( $sql );

		$prev_user = - 1;
		while ( $order = SqlFetchRow( $orders ) ) {
			$order_id   = $order[0];
			if ($debug) MyLog(__FUNCTION__ . ': $order_id');
			$o          = new Finance_Order( $order_id );
			$is_group   = false; // $order[1];
			$order_user = $order[1];
			if ( $debug ) print "order " . $order_id . "<br/>";

			if (get_post_meta($order_id, "delivered")) continue;
			if ( ! $is_group ) {
				$data .= $o->PrintHtml( $selectable );
				continue;
			} else {
				if ( $order_user != $prev_user ) {
					$data      .= $o->PrintHtml( $selectable );
					$prev_user = $order_user;
				}
			}
		}

		// print "data=" . $data . '<br/>';
		return $data;
	}

	static function delivered_wrap()
	{
		$site_id = GetParam("site_id", true);
		$type = GetParam("type", true);
		$id = GetParam("id", true);

		return self::delivered($site_id, $type, $id);
	}

	static function delivered($site_id, $type, $id, $debug = false)
	{
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
		}
		// Running local. Let's do it.
		// print "type=" . $type . "<br/>";
		switch ( $type ) {
			case "orders":
				$o = new Finance_Order( $id );
				$message = "";
				if ( ! $o->delivered($message) )
					print $message;
				else
					return true;
				break;
			case "tasklist":
				$t = new Focus_Tasklist( $id );
				$t->Ended();
				return true;
				break;
			case "supplies":
				$s = new Fresh_Supply( $id );
				$s->picked();
				return true;
				break;
		}
		return false;
	}

	static function getOrder($order_id)
	{
		if (class_exists("Fresh_Order"))
			return new Fresh_Order($order_id);
		new Finance_Order( $order_id );
	}

	static function order_update_driver_comment()
	{
		$order_id = GetParam("order_id", true);
		$comments = GetParam("comments", true);
//		print "$order_id \'$comments\'";
		$o = new Finance_Order($order_id);
		return $o->UpdateDriverComments($comments);
	}

	function AddPrerequisite($order_id, $pre_point)
	{
//		print "Adding $pre_point to $order_id<br/>";
		if (! isset($this->prerequisite[$order_id])) $this->prerequisite[$order_id] = array();
		if (! in_array($pre_point, $this->prerequisite[$order_id]))
			array_push($this->prerequisite[$order_id], trim($pre_point));
	}
}