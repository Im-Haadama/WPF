<?php

$point_sites = [];
$point_orders = [];

class Freight_Mission_Manager 
{
	static function init_hooks()
	{
		add_action("order_save_pri", __CLASS__ . '::order_save_pri');
	}
	static function missions()
	{
		$id = GetParam("id");
		if ($id) {
			$args = array("post_file" => self::getPost());
			$result = ""; /// Core_Html::GuiHeader(1, "Mission $id");
//			$result .= Core_Gem::GemElement("missions", $id, $args);
			$path = array();
//			self::prepare_route($id, $path, $lines_per_station);
			$result .= self::show_mission_route($id);
			print $result;
			return;
		}

		$header = "Missions";
		$week = GetParam("week", false, null);
		if ($week)
			$header .= __("Missions of week") . " " . $week;


		$result = Core_Html::GuiHeader(1, $header);

		$result .= self::show_missions($week ? "first_day_of_week(date) = '$week'" : "date >= curdate()");

		print $result;
	}

	static function show_missions($query = "date >= curdate()")
	{
		$result = $query;

		if (! $query) $week = date('Y-m-d', strtotime('last sunday'));

		$sql = "select id from im_missions where " . $query; // FIRST_DAY_OF_WEEK(date) = " . quote_text($week);

		$missions = sql_query_array_scalar($sql);

		if ( count( $missions )  == 0) {
			$result .= ImTranslate("No missions for given period");
			$result .= Core_Html::GuiHyperlink("Last week", AddToUrl("week" , date( "Y-m-d", strtotime( "last sunday" )))) . " ";
			$result .= Core_Html::GuiHyperlink("This week", AddToUrl("week" , date( "Y-m-d", strtotime( "sunday" )))) . " ";
			$result .= Core_Html::GuiHyperlink("Next week", AddToUrl("week", date( "Y-m-d", strtotime( "next sunday" ))));
			return $result;
		}

		$args = array();
		$args["edit"] = false;
		$args["add_checkbox"] = true;
		$args["post_file"] = GetUrl(1);

		$sql = "select * from im_missions where id in (" . CommaImplode($missions) . ") order by date";

		$args["links"] = array("id" => AddToUrl( array("operation" => "show_mission", "id"=>"%s")));

		// $args["events"] = array("mission_id" => "mission_changed(order_id))
		$args["sql"] = $sql;
		$args["hide_cols"] = array("zones_times"=>1);
		$result .= Core_Gem::GemTable("missions", $args);

		return $result;
	}

	static function dispatcher_wrap()
	{
		$id = GetParam("id");
		if ($id) {
			$args = array("post_file" => self::getPost());
			$result = ""; /// Core_Html::GuiHeader(1, "Mission $id");
//			$result .= Core_Gem::GemElement("missions", $id, $args);
			$path = array();
//			self::prepare_route($id, $path, $lines_per_station);
			$result .= self::dispatcher($id);
			print $result;
			return;
		}

		$header = "Missions";
		$week = GetParam("week", false, null);
		if ($week)
			$header .= __("Missions of week") . " " . $week;


		$result = Core_Html::GuiHeader(1, $header);

		$result .= self::show_missions($week ? "first_day_of_week(date) = '$week'" : "date >= curdate()");

		print $result;

	}

	static function dispatcher($the_mission)
	{
		$lines_per_station = array();
		$supplies_to_collect = array();

		self::prepare_route($the_mission, $path, $lines_per_station, $supplies_to_collect);

		$path_info = [];
		for ( $i = 0; $i < count( $path ); $i ++ ) {
			$first = true;
			foreach ( $lines_per_station[ $path[ $i ] ] as $line_array ) {
				$order_info = $line_array[2];
				$order_id = $line_array[1];
				$order_pri = self::order_get_pri($order_id, $order_info['site_id']);
				$pri_input = Core_Html::GuiInput("pri". $order_id . "_" . $order_info['site_id'], $order_pri, array("size" => 5, "events"=>'onchange="update_order_pri(\'' . self::getPost() . '\',this)"')) .
				             Core_Html::GuiButton("btn_reset_reset", "R", array("action" => "reset_path('".self::getPost()."',". ($i + 1) . ")"));
				$type      = "orders";
				if ( $order_info['site'] == "supplies" ) $type = "supplies"; else if ( $order_info['site'] == "משימות" )$type = "tasklist";

				array_push($path_info,
					array($order_id,
						$order_info['customer'],
						$path[$i],
						$order_info['address_2'],
						$order_info['shipping_method'],
						$order_info['phone'],
						$pri_input,
						Core_Html::GuiCheckbox("chk_$order_id", false, array("events"=>'onchange="delivered(' . $order_info['site_id'] . "," . $order_id . ', \'' . $type . '\')"'))));
			}
		}

		$args =array("class" => "widefat");
		array_unshift($path_info, array(__("Order number"),
			__("Customer name"),
			__("Address 1"),
			__("Address 2"),
			__("Shipping method"),
			__('Phone'),
			__('Priority'),
			__('Actions')));

		$result = Core_Html::gui_table_args($path_info, "path", $args);

		return $result;
		// print self::show_mission_route($the_mission, false, false, false);
	}

	static function prepare_route($the_mission, &$path, &$lines_per_station, $supplies_to_collect) {
		$debug  = false;
		$update = false;
		require_once( ABSPATH . 'wp-content/plugins/flavor/includes/core/data/im_simple_html_dom.php' );
		$stop_points       = array();
		$m                 = Core_Db_MultiSite::getInstance();

		$prerequisite = array();

		$data_lines   = array();

		$data_url = "/routes/routes-post.php?operation=get_local&mission_ids=$the_mission";
		$output   = $m->GetAll( $data_url, false, $debug );
		$dom = im_str_get_html( $output );

		if ( strlen( $output ) < 10 ) {
			print $output . "<br/>";
			die ( "No routes information<br/>" . $data_url );
		}
		$table_header = null;

		// Collect data for building the path
		foreach ( $dom->find( 'tr' ) as $row ) {
			if ( ! $table_header ) {
				for ( $i = 0; $i < 8; $i ++ ) {
					if ( $i != 2 ) {
						$table_header .= $row->find( 'td', $i );
					}
				}
				$table_header .= Core_Html::GuiCell( Core_Html::gui_header( 3, "מספר ארגזים, קירור" ) );
				$table_header .= Core_Html::GuiCell( Core_Html::gui_header( 3, "נמסר" ) );
				$table_header .= Core_Html::GuiCell( Core_Html::gui_header( 3, "ק\"מ ליעד" ) );
				$table_header .= Core_Html::GuiCell( Core_Html::gui_header( 3, "דקות" ) );
				$table_header .= Core_Html::GuiCell( Core_Html::gui_header( 3, "דקות מצטבר" ) );
				continue;
			}
			// $key_fields = $row->find( 'td', 11 )->plaintext;
			try {
				$site = $row->find( 'td', 0 )->plaintext;
			} catch ( Exception $e ) {
				var_dump( $row );
				die ( 1 );
			}
			if ( $site == 'אתר' ) {
				continue;
			}
			$order_id               = $row->find( 'td', 1 )->plaintext;
			$user_id                = $row->find( 'td', 2 )->plaintext;
			$name                   = $row->find( 'td', 3 )->plaintext;
			$addresses[ $order_id ] = $row->find( 'td', 4 )->plaintext;
			$site_id                = $row->find( 'td', 9 )->plaintext;

			// Do we need to get somewhere to get something for this delivery.
//		if ($site_id != $m->getLocalSiteID()) $prerequisite = ImMultiSite::getPickupAddress( $site_id );

			$shipping_method = table_get_text($row, 10);

			$mission_id = $row->find( 'td', 8 )->plaintext;
			$line_data  = "<tr>";
			for ( $i = 0; $i < 7; $i ++ ) {
				if ( $i <> 2 ) {
					$line_data .= $row->find( 'td', $i );
				}
			}
			$line_data .= Core_Html::GuiCell( "" ); // #box
			$type      = "orders";
			if ( $site == "supplies" ) {
				$type = "supplies";
			}
			if ( $site == "משימות" ) {
				$type = "tasklist";
			}
			if ( ! is_numeric( $site_id ) ) {
				die ( $site_id . " not number" . $site_id . " order_id = " . $order_id . " name = " . $name . " <br/>" );
			}
			$line_data .= Core_Html::GuiCell( gui_checkbox( "chk_" . $order_id, "", "",
				'onchange="delivered(' . $site_id . "," . $order_id . ', \'' . $type . '\')"' ) ); // #delivered
			$line_data .= Core_Html::GuiCell( $site_id );
			$line_data .= Core_Html::GuiCell( $shipping_method );

			$line_data .= "</tr>";
			if ( ! isset( $data_lines[ $mission_id ] ) ) {
				$data_lines[ $mission_id ] = array();
			}
			array_push( $data_lines[ $mission_id ], array( $addresses[ $order_id ], $line_data ) );
		}

		foreach ( $data_lines as $mission_id => $data_line ) {
			$add_on_the_way      = "";

			//    $mission_id = 152;
			//    $data_line = $data_lines[152];1
			//    if (1){
			if ( ! ( $mission_id > 0 ) ) continue;
			//        die ("no mission id");

			$mission = Mission::getMission( $mission_id );

			if ( ! $update ) {
				print Core_Html::gui_header( 1, $mission->getMissionName(), true, true ) . "(" . Core_Html::gui_label( "mission_id", $mission_id ) . ")";

				$events = "onfocusout='update()'";
				$args   = array( "events" => $events );
				$time   = $mission->getStartTime();
				print Core_Html::gui_table_args( array(
					array( "Start time", Core_Html::gui_input_time( "start_time", "time", $time, $events ) ),
					array( "Start point", Core_Html::GuiInput( "start_location", $mission->getStartAddress(), $args ) )
				) );
			}
			self::collect_points( $data_lines, $mission_id, $prerequisite, $supplies_to_collect, $lines_per_station, $stop_points );
			// Collect the stop points
			//	foreach ($stop_points as $p) print $p . " ";
			$path = array();

			self::find_route_1( $mission->getStartAddress(), $stop_points, $path, false, $mission->getEndAddress(), $prerequisite );
		}
	}

	static function show_mission_route($the_mission, $update = false, $debug = false, $missing = false)
	{
		$data = '<div id="route_div">';
		$path = null;
		$lines_per_station = array();
		$supplies_to_collect = array();

		self::prepare_route($the_mission, $path, $lines_per_station, $supplies_to_collect);
		$mission = new Mission($the_mission);

		$data .= self::get_maps_url($mission, $path);

		$table_header = null;

		$data .= "<table>";
		$data .= Core_Html::GuiHyperlink("Edit route", AddToUrl(array( "edit_route" => 1, "id" => $the_mission)));
		$data .= Core_Html::gui_list( "באחריות הנהג להעמיס את הרכב ולסמן את מספר האריזות והאם יש קירור." );
		$data .= Core_Html::gui_list( "אם יש ללקוח מוצרים קפואים או בקירור, יש לבדוק זמינות לקבלת המסלול (לעדכן את יעקב)." );
		$data .= Core_Html::gui_list( "יש לוודא שכל המשלוחים הועמסו." );
		$data .= Core_Html::gui_list( "בעת קבלת כסף או המחאה יש לשלוח מיידית הודעה ליעקב, עם הסכום ושם הלקוח." );
		$data .= Core_Html::gui_list( "במידה והלקוח לא פותח את הדלת, יש ליידע את הלקוח שהמשלוח בדלת (טלפון או הודעה)." );
		$data .= "</table>";

		$prev           = $mission->getStartAddress();
		$total_distance = 0;
		$arrive_time = $mission->getStart();

		$data .= "<table>" . $table_header;
		for ( $i = 0; $i < count( $path ); $i ++ ) {
			$first = true;
			foreach ( $lines_per_station[ $path[ $i ] ] as $line_array ) {
				$line     = $line_array[0];
				$order_id = $line_array[1];
				$distance       = round( self::get_distance( $prev, $path[ $i ] ) / 1000, 1 );
				if ( $first ) {
					$total_distance += $distance;
					$duration       = round( self::get_distance_duration( $prev, $path[ $i ] ) / 60, 0 );
					$first          = false;
				} else {
					$duration = 5;
				}
				$arrive_time += $duration * 60;
				$data           .= substr( $line, 0, strpos( $line, "</tr>" ) ) . Core_Html::gui_cell( $distance . "km" ) .
				                   Core_Html::gui_cell( $duration . "ד'" ) . Core_Html::gui_cell( date( "G:i", $arrive_time ) ) . "</td>";

				if ( $missing )
					try {
						$o    = new Fresh_Order( $order_id );
						if ( $o->getDeliveryId() and strlen( $o->Missing() ) ) {
							$data .= Core_Html::gui_row( array(
								"חוסרים",
								$order_id,
								$o->CustomerName(),
								"נא לסמן מה הושלם:",
								$o->Missing(),
								"",
								"",
								"",
								"",
								"",
								"",
								""
							) );
						}
					} catch ( Exception $e ) {
						// probably from different site
					}

			}
			$prev = $path[ $i];
		}
		$total_distance += self::get_distance( $path[ count( $path ) - 1 ], $mission->getEndAddress() ) / 1000;

		$data .= "</table>";

		self::save_route($the_mission, $path);

		$data .= "סך הכל ק\"מ " . $total_distance . "<br/>";

		if ( count( $supplies_to_collect ) ) {
			// var_dump($supplies_to_collect);
			foreach ( $supplies_to_collect as $_supply_id ) {
				$supply_id = $_supply_id[0];
				$site_id   = $_supply_id[1];
				// print "sid= " . $site_id . "<br/>";
				if ( $site_id != $m->getLocalSiteID() ) {
					print $m->Run( "supplies/supplies-post.php?operation=print&id=" . $supply_id, $site_id );
				} else {
					$s = new Fresh_Supply( $supply_id );
					$data .= Core_Html::gui_header( 1, "אספקה  " . $supply_id . " מספק " . $s->getSupplierName() );
					$data .= $s->Html( true, 0 );
				}
			}
		}

		$data .= "</div>";
		return $data;
	}

	static function getPost()
	{
		return "/wp-content/plugins/freight/post.php";
	}

	static function collect_points($data_lines, $mission_id, &$prerequisite, &$supplies_to_collect, &$lines_per_station, &$stop_points)
	{
		$multisite = Core_Db_MultiSite::getInstance();
		$stop_points = array();

		$mission = new Mission($mission_id);

		for ( $i = 0; $i < count( $data_lines[ $mission_id ] ); $i ++ ) {
			$stop_point = $data_lines[ $mission_id ][ $i ][0];

			// print "<br/>sp=" . $stop_point; var_dump($prerequisite);
			$dom        = im_str_get_html( $data_lines[ $mission_id ][ $i ][1] );
			$row        = $dom->find( 'tr' );
			$order_info = [];
			$order_info['site']     = table_get_text( $row[0], 0 );
			$order_info['site_id']  = table_get_text( $row[0], 8 );
			$order_id               = table_get_text( $row[0], 1 );
			$order_info['customer'] = table_get_text( $row[0], 2 );
			$order_info['address_2'] = table_get_text($row[0], 4);
			$order_info['phone'] = table_get_text($row[0], 5);
			$order_info['shipping_method'] = table_get_text($row[0], 9);

			$pickup_address = Core_Db_MultiSite::getPickupAddress( $order_info['site_id'] );

			// Deliveries created in other place
			if ( $order_info['site'] != "משימות" and $order_info['site'] != "supplies" and $pickup_address != $mission->getStartAddress() ) {
				// print "adding $pickup_address<br/>";
				$prerequisite[$stop_point] = $pickup_address;
				// Add Pickup
				self::add_stop_point( $stop_points, $pickup_address, $order_id, $order_info['site_id'] );
				self::add_line_per_station($lines_per_station, $mission->getStartAddress(), $pickup_address, Core_Html::gui_row( array(
					$order_info['site'],
					$order_id,
					"<b>איסוף </b>" . $order_info['customer'],
					$pickup_address,
					"",
					"",
					"",
					"",
					""
				) ), $order_id, $order_info );
			}
			if ( $order_info['site'] == "supplies" ) array_push( $supplies_to_collect, array( $order_id, $order_info['site_id'] ) );

			self::add_stop_point($stop_points, $stop_point, $order_id, $order_info['site_id'] );
			if (! isset($prerequisite[$stop_point])) {
				$p = self::order_get_pri($order_id, $order_info['site_id']);
				if (strlen($p)) $prerequisite[$stop_point] = $p;
			}

			//		array_push( $stop_points, $stop_point );
			self::add_line_per_station($lines_per_station,
				$mission->getStartAddress(),
				$stop_point,
				$data_lines[ $mission_id ][ $i ][1], $order_id, $order_info );

			// Check if we need to collect something on the go
			if ($order_info['site_id'] == $multisite->getLocalSiteID() and $order_info['site'] != "supplies"){
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

	static function add_line_per_station(&$lines_per_station, $start_address, $stop_point, $line, $order_id, $order_info ) {
		if ( ! isset( $lines_per_station[ $stop_point ] ) ) {
			$lines_per_station[ $stop_point ] = array();
		}
		if ( self::get_distance( $start_address, $stop_point ) or ( $start_address == $stop_point ) ) {
			array_push( $lines_per_station[ $stop_point ], array( $line, $order_id, $order_info) );
		} else {
			print "לא מזהה את הכתובת של הזמנה " . $line . "<br/>";
		}
	}

	static function get_distance( $address_a, $address_b ) {
		if ( 0 ) {
			print "a: X" . $address_a . "X<br/>";
			print "b: X" . $address_b . "X<br/>";
		}
		if ( rtrim( $address_a ) == rtrim( $address_b ) ) {
			return 0;
		}
		$sql = "SELECT distance FROM im_distance WHERE address_a = '" . escape_string( $address_a ) . "' AND address_b = '" .
		       escape_string( $address_b ) . "'";
		// print $sql . " ";
		$ds  = sql_query_single_scalar( $sql );

		if ( $ds > 0 ) {
			return $ds;
		}
		$r = self::do_get_distance( $address_a, $address_b );
		if ( $r  == -1) {
			// One is invalid
			return -1;
		}
		$distance = $r[0];
		$duration = $r[1];
		if ( $distance > 0 ) {
			$sql1 = "insert into im_distance (address_a, address_b, distance, duration) VALUES 
				('" . escape_string( $address_a ) . "', '" .
			        escape_string(  $address_b ) . "', $distance, $duration)";
			sql_query( $sql1 );
			if ( sql_affected_rows( ) < 1 ) {
				print "fail: " . $sql1 . "<br/>";
			}

			return $distance;
		}

		return - 1;
	}

	static function find_route_1( $node, $rest, &$path, $print, $end, $prerequisite )
	{
		if (! $rest or ! is_array($rest))
		{
			die("invalid points");
		}
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
		$sql = "SELECT duration FROM im_distance WHERE address_a = '" . escape_string( $address_a ) .
		       "' AND address_b = '" . escape_string( $address_b ) . "'";

		return sql_query_single_scalar( $sql );
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

		sql_query( "update im_missions set path = \"" . escape_string(CommaImplode($path, true)) . "\" where id = " . $missions );
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
			print debug_trace(5);
			die (1);
		}
		$i = Core_Options::info_get("mission_order_priority_" . $site_id . '_' .$order_id);
//		print "OGP $order_id $site_id $i<Br/>";

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
			Core_Options::info_update("mission_order_priority_" . $site_id . '_' .$order_id, $pri);
	}
}
