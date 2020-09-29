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

	static function init_hooks()
	{
//		print debug_trace(10); print "---------------------<br/>";
		add_action("order_save_pri", __CLASS__ . '::order_save_pri');
		add_action("mission_update_type", __CLASS__ . '::mission_update_type');
		add_action("mission_details", __CLASS__ . '::mission_details');
		add_action("freight_do_add_delivery", __CLASS__ . "::do_add_delivery");
		add_action('get_local_anonymous', __CLASS__ . "::get_local_missions");
		add_action('delivered', array(__CLASS__, "delivered_wrap"));
		add_action('download_mission', array(__CLASS__, 'download_mission'));
		add_action('print_mission', array(__CLASS__, 'print_mission'));
		AddAction('order_update_driver_comment', array(__CLASS__, 'order_update_driver_comment'));
	}

	static function print_mission()
	{
		$id = GetParam("id", true);
		print Core_Html::HeaderText();
		print self::dispatcher($id, false);
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

	static function getCSV($the_mission)
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

		self::prepare_route($the_mission, $path, $lines_per_station, $supplies_to_collect, false);

		if (! $path or ! count($path))
			return;

		for ( $i = 0; $i < count( $path ); $i ++ ) {
			if (isset($lines_per_station[$path[$i]]))
				foreach ( $lines_per_station[ $path[ $i ] ] as $order_info ) {
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

	static function missions()
	{
		if ($id = GetParam("id")) {
			print self::mission($id);
			return;
		}

		$header = "";
		$week = GetParam("week", false, date('Y-m-d', strtotime('last sunday')));
		if ($week)
			$header .= __("Missions of week") . " " . $week;


		$result = Core_Html::GuiHeader(1, $header);

		$multi = Core_Db_MultiSite::getInstance();

		if (! $multi->isMaster()) $result .= __("Edit missions in site "). $multi->getSiteName($multi->getMaster());

			if ($multi->isMaster())
			self::create_missions();
		else
			self::update_missions_from_master();

		$result .= self::show_missions($week ? " first_day_of_week(date) = '$week'" : null, $multi->isMaster());
		$result .= Core_Html::GuiHyperlink("last week", AddToUrl("week", date('Y-m-d', strtotime("$week -1 week"))));

		print $result;
	}

	static function mission($id)
	{
		if (GetParam("operation", false) == "dispatch") return self::dispatcher($id);

		$args = array("post_file" => self::getPost());
		$result = Core_Html::GuiHeader(1, "Mission $id");
		$result .= Core_Gem::GemElement("missions", $id, $args);
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

	static function show_missions($query = null, $edit = false)
	{
		if (! $query)
			$query = "date >= '" . date('Y-m-d', strtotime('last sunday') ). "'";

		$result = "";

		$sql = "select id from im_missions where " . $query; // FIRST_DAY_OF_WEEK(date) = " . quote_text($week);

		$missions = SqlQueryArrayScalar($sql);

		if ( count( $missions )  == 0) {
			$result .= ImTranslate("No missions for given period");
			$result .= Core_Html::GuiHyperlink("Last week", AddToUrl("week" , date( "Y-m-d", strtotime( "last sunday" )))) . " ";
			$result .= Core_Html::GuiHyperlink("This week", AddToUrl("week" , date( "Y-m-d", strtotime( "sunday" )))) . " ";
			$result .= Core_Html::GuiHyperlink("Next week", AddToUrl("week", date( "Y-m-d", strtotime( "next sunday" ))));
			return $result;
		}

		$args = array();
		$args["edit"] = $edit;
		$args["add_checkbox"] = true;
		$args["post_file"] = Freight::getPost();

		$sql = "select id, date, name, mission_type from im_missions where id in (" . CommaImplode($missions) . ") order by date";

		$args["links"] = array("id" => AddToUrl( "id", "%s"));

		$args["sql"] = $sql;
		$args["hide_cols"] = array("zones_times"=>1);
//		$args["class"] = "sortable";
//		$args["selectors"] = array("mission_type" => __CLASS__ . "::gui_select_mission_type");
		$args["actions"] = array("Dispatch" => Core_Html::GuiHyperlink("Dispatch", AddToUrl("operation", "dispatch&id=%d")));
		$result .= Core_Gem::GemTable("missions", $args);

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

	static function dispatcher($the_mission, $edit = true)
	{
		$lines_per_station = array();
		$supplies_to_collect = array();
		$multi_site = Core_Db_MultiSite::getInstance();
		$result = "";

		self::prepare_route($the_mission, $path, $lines_per_station, $supplies_to_collect);
		$m = new Mission($the_mission);

		if ($path and count($path)) {
			$result .= Core_Html::GuiHeader(1, __("Details for dispatch number") . " " . $the_mission);
			$result .= Core_Html::GuiHeader(2, $m->getDate());
			$path_info = [];
			for ( $i = 0; $i < count( $path ); $i ++ ) {
				if ( isset( $lines_per_station[ $path[ $i ] ] ) ) {
					foreach ( $lines_per_station[ $path[ $i ] ] as $order_info ) {
//						print $order_info[ OrderTableFields::order_number ] . "<br/>";
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

						$edit_user = Core_Html::GuiHyperlink( $user_id, self::$multi_site->getSiteURL( $site_id ) . "/wp-admin/user-edit.php?user_id=" . $user_id );
						$comments  = ( ( $site_id == $multi_site->getLocalSiteID() ) ?
							Core_Html::GuiInput( "comments_$order_id",
								$order_info[ OrderTableFields::comments ],
								array( "events" => "onchange=\"order_update_driver_comment('" . Freight::getPost() . "', $order_id)\"" )
							) : $order_info[ OrderTableFields::comments ] );
						array_push( $path_info,
							array(
								Core_Html::GuiHyperlink( $order_id, "/wp-admin/post.php?post=$order_id&action=edit" ),
								$edit_user,
								Core_Html::GuiHyperlink( $user_name, $multi_site->getSiteURL( $site_id ) . "/wp-admin/user-edit.php?user_id=$user_id" ),
								$path[ $i ],
								$order_info[ OrderTableFields::address_2 ],
								$comments,
								$order_info[ OrderTableFields::shipping ],
								$order_info[ OrderTableFields::phone ],
								$pri_input,
								Core_Html::GuiCheckbox( "chk_$order_id", false,
									array( "events" => 'onchange="delivered(\'' . Freight::getPost() . "', " . $site_id . "," . $order_id . ', \'' . $type . '\')"' ) )
							) );
					}
				}
			}

			$args = array( "class" => "sortable" );
			array_unshift( $path_info, array(
				__( "Order number" ),
				__( "Customer name" ),
				__( "Address 1" ),
				__( "Address 2" ),
				__( "Comments" ),
				__( "Shipping method" ),
				__( 'Phone' ),
				__( 'Priority' ),
				__( 'Actions' )
			) );

//		$args["links"] = array(1 => self::$multi_site->getSiteURL($site_id) . "/wp-admin/user-edit.php?user_id=%d");
//		var_dump($path_info[1]);
			$args["class"] = "widefat";

			$args["hide_cols"] = array( OrderTableFields::client_nubmer - 1 => 1 );
			$result            .= Core_Html::gui_table_args( $path_info, "dispatch_" . $the_mission, $args );
		}
		if ($edit) {
			$result .= self::add_delivery( $m->getDefaultFee() ); // , array("style" => "border:1px solid #000;")
			$result .= Core_Html::GuiHyperlink( "Download CSV", Freight::getPost() . "?operation=download_mission&id=$the_mission" ) . "<br/>";
			$result .= Core_Html::GuiHyperlink( "Print", Freight::getPost() . "?operation=print_mission&id=$the_mission" ) ."</br>";
			$result .= self::get_maps_url($m, $path);
		}


		return $result;
	}

	static function prepare_route($mission_id, &$path, &$lines_per_station, $supplies_to_collect, $headers = true) {
		$mission = new Mission($mission_id);
		$debug = false;
		$stop_points      = array();
		self::$multi_site = Core_Db_MultiSite::getInstance();

		$prerequisite = array();

		$data_url = "wp-content/plugins/freight/post.php?operation=get_local_anonymous&mission_ids=$mission_id";
		$output   = self::$multi_site->GetAll( $data_url, false, $debug );

		$rows = self::parse_output($output);

//		var_dump($rows);

		self::collect_points( $rows, $mission_id, $prerequisite, $supplies_to_collect, $lines_per_station, $stop_points );
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

	static function update_missions_from_master()
	{
		$multi = Core_Db_MultiSite::getInstance();
		if (! $multi->isMaster()){
			$url = Freight::getPost() . "?operation=sync_data_missions";
//			print $multi->getSiteURL($multi->getMaster()) . $url;

			$html = $multi->Execute( $url, $multi->getMaster() );

			if (! $html) print "Can't get data from master<br/>";

			if ( strlen( $html ) > 100 ) {
				//printbr($html);
				$multi->UpdateTable( $html, "missions", "id" );
			} else {
				print "short response. Operation aborted <br/>";
				print "url = $url";
				print $html;

				return;
			}

			$multi->UpdateFromRemote( "missions", "id" );
		}
	}

	static function show_mission_route($the_mission, $update = false, $debug = false, $missing = false)
	{
		$post_file = Freight::getPost();

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
				if ( $site_id != $m->getLocalSiteID() ) {
					print $m->Run( $post_file . "?operation=print&id=" . $supply_id, $site_id );
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

	static function mission_details()
    {
    	$id = GetParam("id", true);
    }

	static function getPost()
	{
		return "/wp-content/plugins/freight/post.php";
	}

	static function collect_points($data_lines, $mission_id, &$prerequisite, &$supplies_to_collect, &$lines_per_station, &$stop_points)
	{
//		print "-----------" . debug_trace() . "<br/>";
		$multisite = Core_Db_MultiSite::getInstance();
		$stop_points = array();

		$mission = new Mission($mission_id);

//		print "count: " . count($data_lines) . "<br/>";
		for ( $i = 0; $i < count( $data_lines); $i ++ ) {
//			print "handling " . $data_lines[$i][OrderTableFields::order_number] . "<br/>";
			$order_info = $data_lines[$i];
			$stop_point = str_replace('-', ' ', $order_info[OrderTableFields::address_1] . " " . $order_info[OrderTableFields::city]);
			$order_id = $order_info[OrderTableFields::order_number];
			$order_site = $order_info[OrderTableFields::site_name];
			$site_id = $order_info[OrderTableFields::site_id];

			$pickup_address = Core_Db_MultiSite::getPickupAddress( $site_id );

			// Deliveries created in other place
//			if ( ($order_info['site'] != "משימות") and ($order_info['site'] != "supplies") and ($pickup_address != $mission->getStartAddress()) ) {
			if (1) {
				// print "adding $pickup_address<br/>";
				$prerequisite[$stop_point] = $pickup_address;
				// Add Pickup
				self::add_stop_point( $stop_points, $pickup_address, $order_id, $site_id );
//				$pickup_order_info = $order_info;
//				$pickup_order_info['customer'] = "<b>איסוף</b>" . $order_info["customer"];
//				self::add_line_per_station($lines_per_station, $mission->getStartAddress(), $pickup_address, Core_Html::gui_row( array(
//					$order_info['site'],
//					$order_id,
//					"<b>איסוף </b>" . $order_info['customer'],
//					$pickup_address,
//					"",
//					"",
//					"",
//					"",
//					""
//				) ), $order_id, $pickup_order_info );
			}
//			if ( $order_info['site'] == "supplies" ) array_push( $supplies_to_collect, array( $order_id, $order_info['site_id'] ) );

			self::add_stop_point($stop_points, $stop_point, $order_id, $site_id );
			if (! isset($prerequisite[$stop_point])) {
				$p = self::order_get_pri($order_id, $site_id);
				if (strlen($p)) $prerequisite[$stop_point] = $p;
			}

			//		array_push( $stop_points, $stop_point );
			self::add_line_per_station($lines_per_station,
				$mission->getStartAddress(),
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

	static function add_line_per_station(&$lines_per_station, $start_address, $stop_point, $order_info, $order_id ) {
//		print "sa=$start_address sp=$stop_point<br/>";
		if ( ! isset( $lines_per_station[ $stop_point ] ) ) {
			$lines_per_station[ $stop_point ] = array();
		}
		if ( self::get_distance( $start_address, $stop_point ) or ( $start_address == $stop_point ) ) {
			array_push( $lines_per_station[ $stop_point ], $order_info );
		} else {
			print "לא מזהה את הכתובת של הזמנה " . $order_id . "<br/>";
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
		if (! $rest or ! is_array($rest))
		{
			return;
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
			print "No shipping method to zone " . $zone->get_zone_name();
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

	static function get_local_missions()
	{
		$mission_ids = GetParam("mission_ids", true);
		$header = GetParam("header", false, false);
		print self::get_missions($mission_ids, $header);
		return true;
	}

	static function get_missions($mission_ids, $header)
	{
		$data = "";

		if ($header) print self::delivery_table_header();
		if (! is_array($mission_ids)) $mission_ids = array($mission_ids);

		foreach ( $mission_ids as $mission_id ) {
			if ( $mission_id ) {
				$sql = "id in (select post_id from wp_postmeta " .
				       " WHERE meta_key = 'mission_id' " .
				       " AND meta_value = " . $mission_id . ") ";
				$sql .= " and `post_status` in ('wc-awaiting-shipment', 'wc-processing')";

				$data .= self::print_deliveries( $sql, false);

				if (class_exists("Fresh_Supplies"))
					$data .= Fresh_Supplies::print_driver_supplies( $mission_id );
//				else
//					print "NNN";

				if (class_exists("Focus"))
					$data .= Focus::print_driver_tasks( $mission_id );
			}
		}

		return $data;
	}

	static function delivery_table_header( $edit = false ) {
		$data = "";
		$data .= "<table id='done_deliveries'><tr>";
		$data .= "<td><h3>" . Core_Html::GuiCheckbox("done_select_all", false, array("events" => "onchange = \"select_all_toggle(done_select_all, 'deliveries')\"") ) . "</h3></td>";
		$data .= "<td><h3>אתר</h3></td>";
		$data .= "<td><h3>מספר </br>/הזמנה<br/>אספקה</h3></td>";
		$data .= "<td><h3>מספר </br>לקוח</h3></td>";
//	$data .= "<td><h3>שם המזמין</h3></td>";
		$data .= "<td><h3>שם המקבל</h3></td>";
		$data .= "<td><h3>עיר</h3></td>";
		$data .= "<td><h3>כתובת</h3></td>";
		$data .= "<td><h3>כתובת-2</h3></td>";
		$data .= "<td><h3>טלפון</h3></td>";
		// $data .= "<td><h3></h3></td>";
		$data .= "<td><h3>מזומן/המחאה</h3></td>";
		$data .= "<td><h3>משימה</h3></td>";
		$data .= "<td><h3>אתר</h3></td>";
		$data .= "<td><h3>דמי משלוח</h3></td>";

		// $data .= "<td><h3>מיקום</h3></td>";
		return $data;
	}

	static function print_deliveries( $query, $selectable = false, $debug = false ) {
		$data = "";
//		$sql  = 'SELECT posts.id, order_is_group(posts.id), order_user(posts.id) '
		$sql  = 'SELECT posts.id, order_user(posts.id) '
		        . ' FROM `wp_posts` posts'
		        . ' WHERE ' . $query;

		$sql .= ' order by 1';

		if ( $debug ) MyLog($sql);

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
