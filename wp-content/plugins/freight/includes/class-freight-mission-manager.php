<?php

$point_sites = [];
$point_orders = [];

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
	private $site_info;
	static private $_instance;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	function init_hooks()
	{
//		print debug_trace(10); print "---------------------<br/>";
		add_action("order_save_pri", __CLASS__ . '::order_save_pri');
		add_action("mission_update_type", __CLASS__ . '::mission_update_type');
		add_action("mission_details", __CLASS__ . '::mission_details');
		add_action("freight_do_add_delivery", __CLASS__ . "::do_add_delivery");
		add_action('delivered', array(__CLASS__, "delivered_wrap"));
		add_action('download_mission', array(__CLASS__, 'download_mission'));
		add_action('print_mission', array($this, 'print_mission'));
		AddAction('order_update_driver_comment', array(__CLASS__, 'order_update_driver_comment'));
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

	static function download_mission()
	{
		$id = GetParam("id", true, "");
		$file = self::getCSV($id);
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

		self::prepare_route($the_mission, $path, $supplies_to_collect);

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

		$this->lines_per_station = array();
		$supplies_to_collect = array();
		$multi_site = Core_Db_MultiSite::getInstance();
		$result = "";
		$post_file = Flavor::getPost();

		$this->prepare_route($the_mission, $path, $supplies_to_collect);

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
						$new_row =
							array(
								Core_Html::GuiHyperlink( $order_id, "/wp-admin/post.php?post=$order_id&action=edit" ),
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
								if ($edit) array_push($new_row, $pri_input);
						array_push( $path_info, $new_row);
					}
				}
//				else {
//					var_dump($lines_per_station[$path[$i]]);
//					$new_row = array(__("Collect"), '', $path[ $i ]);
//				}

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
		}
		if (! $print) {
			$result .= self::add_delivery( $m->getDefaultFee() ); // , array("style" => "border:1px solid #000;")
			$result .= Core_Html::GuiHyperlink( "Download CSV", Freight::getPost() . "?operation=download_mission&id=$the_mission" ) . "<br/>";
			$result .= Core_Html::GuiHyperlink( "Print", Freight::getPost() . "?operation=print_mission&id=$the_mission" ) ."</br>";
			$result .= self::get_maps_url($m, $path);
		}
		return $result;
	}

	function prepare_route($mission_id, &$path, $supplies_to_collect) {
		$mission = new Mission($mission_id);
		$debug = false;
		$stop_points      = array();
		self::$multi_site = Core_Db_MultiSite::getInstance();

		$prerequisite = array();

		$data_url = "wp-content/plugins/flavor/post.php?operation=get_local_anonymous&mission_ids=$mission_id";
//		print $data_url . "<br/>";
		$output   = self::$multi_site->GetAll( $data_url, false, $debug );
//		print $output;

		$rows = self::parse_output($output);

//		var_dump($rows);

		self::collect_points( $rows, $mission_id, $prerequisite, $supplies_to_collect, $stop_points );
		// Collect the stop points
		//	foreach ($stop_points as $p) print $p . " ";
		$path = array();

		self::find_route_1( $mission->getStartAddress(), $stop_points, $path, false, $mission->getEndAddress(), $prerequisite );
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
		return "/wp-content/plugins/freight/post.php";
	}

	function collect_points($data_lines, $mission_id, &$prerequisite, &$supplies_to_collect, &$stop_points)
	{
		$multisite = Core_Db_MultiSite::getInstance();
		$stop_points = array();

		$mission = new Mission($mission_id);

		for ( $i = 0; $i < count( $data_lines); $i ++ ) {
			$order_info = $data_lines[$i];
			$stop_point = str_replace('-', ' ', $order_info[OrderTableFields::address_1] . " " . $order_info[OrderTableFields::city]);
			$order_id = $order_info[OrderTableFields::order_number];
			$order_site = $order_info[OrderTableFields::site_name];
			$site_id = $order_info[OrderTableFields::site_id];

			// Collect information about pickup points.
			if (! isset($this->points_per_sites[$site_id])) $this->points_per_sites[$site_id] = array();
			array_push($this->points_per_sites[$site_id], $order_id);

			$pickup_address = Core_Db_MultiSite::getPickupAddress( $site_id );

			// Deliveries created in other place
//			if ( ($order_info['site'] != "משימות") and ($order_info['site'] != "supplies") and ($pickup_address != $mission->getStartAddress()) ) {
//			print "$pickup_address<br/>";
			// print "adding $pickup_address<br/>";
			$prerequisite[$stop_point] = $pickup_address;
			// Add Pickup
			self::add_stop_point( $stop_points, $pickup_address, $order_id, $site_id );
			$pickup_order_info = $order_info;
			$pickup_order_info[OrderTableFields::client_name] = "<b>איסוף</b> " . $order_info[OrderTableFields::client_name];
			$pickup_order_info[OrderTableFields::address_2] = '';
			$pickup_order_info[OrderTableFields::phone] = '';
			self::add_line_per_station($mission->getStartAddress(),
				$pickup_address,
				$pickup_order_info,
			$order_id);
			if ( $order_info[OrderTableFields::site_name] == "supplies" ) array_push( $supplies_to_collect, array( $order_id, $order_info['site_id'] ) );

			self::add_stop_point($stop_points, $stop_point, $order_id, $site_id );
			if (! isset($prerequisite[$stop_point])) {
				$p = self::order_get_pri($order_id, $site_id);
				if (strlen($p)) $prerequisite[$stop_point] = $p;
			}

			self::add_line_per_station($mission->getStartAddress(),
				$stop_point,
				$order_info,
				$order_id);

			// Check if we need to collect something on the go
			if ($site_id == $multisite->getLocalSiteID() and $order_site != "supplies"){
				$order = new Fresh_Order($order_id);
				if ($supply_points = $order->SuppliersOnTheGo()){
					$supplier = new Fresh_Supplier($supply_points[0]);
					$prerequisite[$stop_point] = $supplier->getAddress();
				}
			}
		}
	}

	static function add_stop_point( &$stop_points, $point, $order_id, $site_id) {
		global $point_sites;
		global $point_orders;

		if ( ! in_array( $point, $stop_points ) ) {
			array_push( $stop_points, $point);
			$point_sites[$point] = $site_id;
			$point_orders[$point] = $order_id;
		}
	}

	function add_line_per_station($start_address, $stop_point, $order_info, $order_id ) {
//		print "sa=$start_address sp=$stop_point<br/>";
		if ( ! isset( $this->lines_per_station[ $stop_point ] ) ) {
			$this->lines_per_station[ $stop_point ] = array();
		}
		if ( self::get_distance( $start_address, $stop_point ) or ( $start_address == $stop_point ) ) {
			array_push( $this->lines_per_station[ $stop_point ], $order_info );
		} else {
			print "לא מזהה את הכתובת של הזמנה " . $order_id . "<br/>";
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

	static function find_route_1( $node, $rest, &$path, $print, $end, $prerequisite )
	{
		if (! $rest or ! is_array($rest)) return;
		if ( count( $rest ) == 1 ) {
			array_push( $path, $rest[0] );
			return;
		}
		self::find_route( $node, $rest, $path, $prerequisite );

		$best_cost = self::evaluate_path( $node, $path, $end );

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

	static function find_route( $node, $rest, &$path, $prerequisite = null )
	{
		global $point_orders;
		global $point_sites;
		$debug = 0;
		$check_preq = 0;

		if ($debug)
		{
			print "<br/>Find route from $node<br/>";
			print "<table>";
		}
		if ( sizeof( $rest ) == 1 ) {
			array_push( $path, $rest[0] );
			return;
		}

		$site_id = $point_sites[$rest[0]];
		$order_id = $point_orders[$rest[0]];
//		var_dump($point_sites);
//		print "$site_id $order_id " . $rest[0] . "<br/>";
		$pri = self::order_get_pri($order_id, $site_id);
		$min     = 	self::get_distance( $node, $rest[ 0 ] );
		$min_seq = 0;
		if ($debug) print "<tr><td>checking first " . $rest[0]  . "</td><td> Current pri $pri, dis $min</td></tr>";

		for ( $i = 1; $i < sizeof( $rest ); $i ++ ) {
			$d = self::get_distance( $node, $rest[ $i ] );
			$site_id = $point_sites[$rest[$i]];
			$order_id = $point_orders[$rest[$i]];
			$order_pri = self::order_get_pri($order_id, $site_id);
				// Core_Options::info_get("mission_order_priority_" . $site_id . '_' .$order_id);

			if ($debug) print "<tr><td>checking " . $rest[$i]  . "</td><td> dis=$d pri=$order_pri. Current pri $pri, dis $min </td></tr>";

			if (($order_pri < $pri) or (($order_pri == $pri) and ($d < $min))){
				if ($debug) print "<tr><td>inside " . ($order_pri < $pri)  . "</td><td> " .(($order_pri = $pri) and ($d < $min)) ." </td></tr>";
				$preq_meet = true;
				if ($check_preq and $prerequisite and isset($prerequisite[$rest[$i]])){ //and strlen ($prerequisite[$rest[$i]])){
					if (isset($prerequisite[$rest[$i]])) {
						foreach ( $prerequisite[ $rest[ $i ] ] as $p ) {
							if ( ! in_array( $p, $path, true ) ) {
								$preq_meet = false;
							}
						}
					}
				}
				if ($preq_meet) {
					$min     = $d;
					$min_seq = $i;
					$pri = $order_pri;
				}
			}
		}
		if ($debug)
			print "<tr><td>=====>selected $rest[$min_seq] $pri</td></tr></table>";

		$next = $rest[ $min_seq ];
		array_push( $path, $next );
		$new_rest = array();
		for ( $i = 0; $i < sizeof( $rest ); $i ++ ) {
			if ( $i <> $min_seq ) {
				array_push( $new_rest, $rest[ $i ] );
			}
		}
		self::find_route( $next, $new_rest, $path, $prerequisite );
	}

	static function evaluate_path( $start, $elements, $end ) {
//	if ( $end < 1 ) {
//		print "end is " . $end . "<br/>";
//	}
		// $cost = get_distance( $start, $elements[0] );
		$cost = self::get_distance_duration( $start, $elements[0] );
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
		$url = "https://www.google.com/maps/dir/" . $mission->getStartAddress();
		$dynamic_url = "https://www.google.com/maps/dir/My+Location";

		for ( $i = 0; $i < count( $path ); $i ++ ) {
			$url .= "/" . $path[ $i ];
			$dynamic_url .= "/" . $path[ $i ];
		}
		$url .= "/" . $mission->getEndAddress();
		return Core_Html::GuiHyperlink( "Maps", $url ) . " " . Core_Html::GuiHyperlink("Dyn", $dynamic_url);
	}

	static function save_route($missions, $path) {
//    print "missions=$missions<br/>";
//    print "path=" . var_dump($path);
		! is_array( $missions ) or die ( "missions array" );

		SqlQuery( "update im_missions set path = \"" . EscapeString(CommaImplode($path, true)) . "\" where id = " . $missions );
	}

	static function do_get_distance( $a, $b ) {
		// $start = new DateTime();
		if ( $a == $b ) {
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
			InfoUpdate("mission_order_priority_" . $site_id . '_' .$order_id, $pri);
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

		$o = Fresh_Order::CreateOrder( $client, $mission_id, null, $the_shipping,
			" משלוח המכולת " . date( 'Y-m-d' ) . " " . $customer->getName(), Fresh_Pricing::addVat($fee));

		if (! $o)
			return false;
		$o->setStatus( 'wc-processing' );
//		$o->setMissionID($mission_id);

		return true;
	}

	static function print_deliveries( $mission_id, $selectable = false, $debug = false ) {
		$data = "";
		$query = "id in (select post_id from wp_postmeta " .
		         " WHERE meta_key = 'mission_id' " .
		         " AND meta_value = " . $mission_id . ") ";

		$sql  = 'SELECT posts.id, order_user(posts.id) '
		        . ' FROM `wp_posts` posts'
		        . ' WHERE ' . $query;
//
		$sql .= ' order by 1';
//
//		if ( $debug ) MyLog($sql);

		$sql .= " and `post_status` in ('wc-awaiting-shipment', 'wc-processing')";

		$orders    = SqlQuery( $sql );

		$prev_user = - 1;
		while ( $order = SqlFetchRow( $orders ) ) {
			$order_id   = $order[0];
			if ($debug) MyLog(__FUNCTION__ . ': $order_id');
			$o          = new Fresh_Order( $order_id );
			$is_group   = false; // $order[1];
			$order_user = $order[1];
			if ( $debug ) print "order " . $order_id . "<br/>";

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
				$o = new Fresh_Order( $id );
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

	static function order_update_driver_comment()
	{
		$order_id = GetParam("order_id", true);
		$comments = GetParam("comments", true);
//		print "$order_id \'$comments\'";
		$o = new Fresh_Order($order_id);
		return $o->UpdateDriverComments($comments);
	}
}
