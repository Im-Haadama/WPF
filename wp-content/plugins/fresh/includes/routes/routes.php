<?php

require_once( FRESH_INCLUDES . "/core/data/dom.php" );
require_once( FRESH_INCLUDES . "/fresh/orders/Order.php" );
require_once( FRESH_INCLUDES . "/fresh/suppliers/Supplier.php" );
require_once( FRESH_INCLUDES . "/focus/Tasklist.php" );
require_once( FRESH_INCLUDES . "/fresh/catalog/gui.php" );
require_once( FRESH_INCLUDES . "/routes/gui.php" );

/**
 * @param $operation
 *
 * @return string|void
 * @throws Exception
 */
function handle_routes_do($operation)
{
	$allowed_tables = array( "im_missions");
	switch ($operation)
	{
		case "add_zone_times":
			$path_id= GetParam("path_id", true);
			$zones = GetParamArray("zones", true, null, ":");
			$time = GetParam("time", true);
			if (path_add_zone($path_id, $zones, $time))
				return "done";
			return;

		case "delete":
			$type = GetParam("type");
			switch ($type)
			{
				case "missions":
					$ids = GetParamArray("ids", true);
					if (!data_delete("im_missions", $ids)) return "fail";
					return "done";
			}

			return;
		case "create_missions":
			$path_ids = GetParamArray("path_ids", true); // Path ids.
			return create_missions($path_ids);
	    case "save_path_times":
	    	$path_id = GetParam("path_id", true);
	    	if (path_save_times($path_id, GetParamArray("params", true))) print "done";
	    	return "done";
        case "save_new";
            $table_name = GetParam("table_name", true);
            if (! in_array($table_name, array("im_paths")))
                die ("invalid table $table_name");
            if (data_save_new($table_name)) print "done";
            return "done";
//	    case "save_new":
//		    $id = data_save_new("im_missions");
//		    if ($id > 0) return "done.$id";
//		    return "done";
	    case "update":
		    $table_name = GetParam("table_name", true);
		    if (! in_array($table_name, $allowed_tables))
			    die ("invalid table operation");
		    return update_data($table_name) ? "done" : "failed";
	    case "enable_shipping_method":
	    case "disable_shipping_method":
		    $args = [ "zone_id"    => GetParam("zone_id", true), "instance_id" => GetParam("instance_id", true),
		              "is_enabled" => (substr($operation, 0, 3) == "ena" ? '1' : '0')];
		    update_wp_woocommerce_shipping_zone_methods($args);
		    return "done";
		case "get_local": // Is a do action because is called from show_route. (no header).
			$mission_ids = GetParam("mission_ids", true);
			$header = GetParam("header", false, false);
			return get_missions($mission_ids, $header, false);
	}
	return "not handled";
}

/**
 * @param $operation
 * @param bool $debug
 *
 * @return string|void|null
 * @throws Exception
 */
function handle_routes_show($operation, $debug = false)
{
	$result = "";
	switch ( $operation ) {
		case "show_add_zone_times":
			$result .= show_add_zone_times();
			return $result;
        case "show_add_im_paths":
            $result .= show_add_paths();
            return $result;

        case "show_paths":
            $result .= show_paths();
            return $result;

        case "show_path":
            $path_id = GetParam("path_id", true);
            $result .= show_path($path_id);
            return $result;

		case "show_today_routes":
			return show_today_routes();

		case "show_missions":
			$id = GetParam("id", true, null);
			if ($id) $query = "id = $id";
			else $query = 'date = ' . QuoteText(date('Y-m-d'));
			return show_missions($query);
		case "show_mission_route":
		    $edit_route = GetParam("edit_route", false, false);
		    if ($edit_route) {
		        edit_route(GetParam("id", true));
		        return;
            }
			if ($id = GetParam("id", false)) {
				// $result .= show_mission($id);
				$result .= show_mission_route($id);
				return $result;
			}
			return show_missions( "FIRST_DAY_OF_WEEK(date) = " . QuoteText(GetParam("week", date('Y-m-d', strtotime('last sunday')))));
			break;

        case "update_mission_preq":
            $mission = GetParam("id", true);
            $point = GetParam("point", true);
            $preq = GetParam("preq", true);
            $key = "mission_preq_" . $mission . "." . $point;
            if ($preq == "select")
                InfoUpdate($key, null);
            else
                InfoUpdate($key, $preq);
            print "done";
            break;

        case "update_mission":
            $id = GetParam("id");
            $start = GetParam("start");
            $start_point = GetParam("start_point");
            $m = new Mission($id);
            $m->setStartTime($start);
            $m->setStartAddress($start_point);
            print show_route($id, true);  // in update don't show header (logo, time, etc);
            break;

		case "delivered":
			$site_id = GetParam( "site_id" );
			$type    = GetParam( "type" );
			$id      = GetParam( "id" );
			if (delivered($site_id, $type, $id, $debug))
			    print "delivered";
			break;

		case "show_add_im_missions":
			return show_add_mission();
		case "show_active_missions":
			return show_active_missions();
		case "show_mission":
			$mission_id = GetParam("id", true);
			return show_mission($mission_id);
		case "update_shipping_methods":
			return update_shipping_methods();

		default:
            die ("operation $operation not handled");
	}
}

/**
 * @param null $query
 *
 * @return string
 * @throws Exception
 */
function show_missions($query = null)
{
    $result = "";

    if (! $query) $week = date('Y-m-d', strtotime('last sunday'));

    $sql = "select id from im_missions where " . $query; // FIRST_DAY_OF_WEEK(date) = " . quote_text($week);

	$missions = sql_query_array_scalar($sql);

	if ( count( $missions )  == 0) {
	    $result .= im_translate("No missions for given period");
		$result .= Core_Html::GuiHyperlink("Last week", AddToUrl("week" , date( "Y-m-d", strtotime( "last sunday" )))) . " ";
		$result .= Core_Html::GuiHyperlink("This week", AddToUrl("week" , date( "Y-m-d", strtotime( "sunday" )))) . " ";
		$result .= Core_Html::GuiHyperlink("Next week", AddToUrl("week", date( "Y-m-d", strtotime( "next sunday" ))));
		return $result;
	}

	$args = array();
	$args["edit"] = false;
	$args["add_checkbox"] = true;
	$args["post_file"] = GetUrl(1);

	$sql = "select * from im_missions where id in (" . CommaImplode($missions) . ")";

	$args["links"] = array("id" => GetUrl(true) . "?operation=show_mission&id=%s");

	// $args["events"] = array("mission_id" => "mission_changed(order_id))
	$args["sql"] = $sql;
	$args["hide_cols"] = array("zones_times"=>1);
	$result .= GemTable("missions", $args);

	return $result;
}

/**
 * @param $site_id
 * @param $type
 * @param $id
 * @param bool $debug
 *
 * @return bool
 */
function delivered($site_id, $type, $id, $debug = false)
{
    if ( $debug ) {
        print "start<br/>";
    }
    if ( $site_id != Core_Db_MultiSite::LocalSiteID() ) {
        if ( $debug ) {
            print "remote.. ";
        }
        $request = "/routes/routes-post.php?site_id=" . $site_id .
                   "&type=" . $type . "&id=" . $id . "&operation=delivered";
        if ( $debug ) {
            $request .= "&debug=1";
            print $request;
        }
        if ( Core_Db_MultiSite::sExecute( $request, $site_id, $debug ) == "delivered")  return true;
        print "failed:<br/>";
        print $request;
        return false;
    }
    // Running local. Let's do it.
    // print "type=" . $type . "<br/>";
    switch ( $type ) {
        case "orders":
            $o = new Order( $id );
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

// Start collecting data
/**
 * @param $missions
 * @param bool $update
 * @param bool $debug
 * @param bool $missing
 *
 * @return string
 * @throws Exception
 */
function show_mission_route($the_mission, $update = false, $debug = false, $missing = false)
{
	$stop_points = array();
	$lines_per_station = array();
	$m      = Core_Db_MultiSite::getInstance();

	$prerequisite = array();
	$data = '<div id="route_div">';

	$data_lines = array();
	$table_header     = null;

	$data_url = "/routes/routes-post.php?operation=get_local&mission_ids=$the_mission";
	$output   = $m->GetAll( $data_url, false, $debug );
	if ( $debug ) {
		print "o= " . $output . "<br/>";
	}
	$dom    = im_str_get_html( $output );

	if ( strlen( $output ) < 10 ) {
		print $output . "<br/>";
		die ( "No routes information<br/>" . $data_url );
	}

	// Collect data for building the path
	foreach ( $dom->find( 'tr' ) as $row ) {
		if ( ! $table_header ) {
			for ( $i = 0; $i < 7; $i ++ ) {
				if ( $i != 2 ) {
					$table_header .= $row->find( 'td', $i );
				}
			}
			$table_header .= gui_cell( Core_Html::gui_header( 3, "מספר ארגזים, קירור" ) );
			$table_header .= gui_cell( Core_Html::gui_header( 3, "נמסר" ));
			$table_header .= gui_cell( Core_Html::gui_header( 3, "ק\"מ ליעד" ) );
			$table_header .= gui_cell( Core_Html::gui_header( 3, "דקות" ) );
			$table_header .= gui_cell( Core_Html::gui_header( 3, "דקות מצטבר" ));
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

		$delivery_id            = table_get_text( $row, 10 );
		// print "name = " . $name . " key= "  . $key . "<br/>";
		$mission_id = $row->find( 'td', 8 )->plaintext;
		$line_data  = "<tr>";
		for ( $i = 0; $i < 7; $i ++ ) {
			if ( $i <> 2 )
				$line_data .= $row->find( 'td', $i );
		}
		$line_data .= gui_cell( "" ); // #box
		$type      = "orders";
		if ( $site == "supplies" ) {
			$type = "supplies";
		}
		if ( $site == "משימות" )
			$type = "tasklist";
		if ( ! is_numeric( $site_id ) ) {
			die ( $site_id . " not number" . $site_id . " order_id = " . $order_id . " name = " . $name . " <br/>" );
		}
		$line_data .= gui_cell( gui_checkbox( "chk_" . $order_id, "", "",
			'onchange="delivered(' . $site_id . "," . $order_id . ', \'' . $type . '\')"' ) ); // #delivered
		$line_data .= gui_cell( $site_id );

		$line_data .= "</tr>";
		if ( ! isset( $data_lines[ $mission_id ] ) ) {
			$data_lines[ $mission_id ] = array();
			/// print "new: " . $mission_id . "<br/>";
		}
		array_push( $data_lines[ $mission_id ], array( $addresses[ $order_id ], $line_data) );
		// var_dump($line_data); print "<br/>";
	}

	foreach ( $data_lines as $mission_id => $data_line ) {
		$supplies_to_collect = array();
		$add_on_the_way      = "";

		//    $mission_id = 152;
		//    $data_line = $data_lines[152];1
		//    if (1){
		if ( ! ( $mission_id > 0 ) ) {
			// print "mission 0 skipped<br/>";
			continue;
		}
		//        die ("no mission id");

		$mission = Mission::getMission( $mission_id);

		if (! $update) {
			print Core_Html::gui_header( 1, get_mission_name( $mission_id ), true, true ) . "(" . gui_label("mission_id", $mission_id) . ")";

			$events = "onfocusout='update()'";
			$args   = array( "events" => $events );
			$time   = $mission->getStartTime();
			print gui_table_args( array(
				array( "Start time", gui_input_time( "start_time", "time", $time, $events ) ),
				array( "Start point", GuiInput( "start_location", $mission->getStartAddress(), $args ) )
			) );
		}
		if ( $debug ) {
			print_time( "start handle mission " . $mission_id, true );
		}

		collect_points($data_lines, $mission_id, $prerequisite, $supplies_to_collect, $lines_per_station, $stop_points);

		// Collect the stop points
		//	foreach ($stop_points as $p) print $p . " ";
		if ( $debug )
			print_time( "start path ", true);
		// var_dump($mission);
        $path = array();

		find_route_1( $mission->getStartAddress(), $stop_points, $path, false, $mission->getEndAddress(), $prerequisite );

		$data .= get_maps_url($mission, $path);
//		$data .= $header;

        $data .= "<table>";
        $data .= Core_Html::GuiHyperlink("Edit route", AddToUrl(array( "edit_route" => 1, "id" => $mission_id)));
        $data .= gui_list( "באחריות הנהג להעמיס את הרכב ולסמן את מספר האריזות והאם יש קירור." );
        $data .= gui_list( "אם יש ללקוח מוצרים קפואים או בקירור, יש לבדוק זמינות לקבלת המסלול (לעדכן את יעקב)." );
        $data .= gui_list( "יש לוודא שכל המשלוחים הועמסו." );
        $data .= gui_list( "בעת קבלת כסף או המחאה יש לשלוח מיידית הודעה ליעקב, עם הסכום ושם הלקוח." );
        $data .= gui_list( "במידה והלקוח לא פותח את הדלת, יש ליידע את הלקוח שהמשלוח בדלת (טלפון או הודעה)." );
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
//				 print "oid=" . $order_id ."<br/>";
				$distance       = round( get_distance( $prev, $path[ $i ] ) / 1000, 1 );
				if ( $first ) {
					$total_distance += $distance;
					$duration       = round( get_distance_duration( $prev, $path[ $i ] ) / 60, 0 );
					$first          = false;
				} else {
					$duration = 5;
				}
				$arrive_time += $duration * 60;
//				print "arrive: $arrive_time dur=$duration<br/>";
				$data           .= substr( $line, 0, strpos( $line, "</tr>" ) ) . gui_cell( $distance . "km" ) .
				                   gui_cell( $duration . "ד'" ) . gui_cell( date( "G:i", $arrive_time ) ) . "</td>";

				if ( $missing )
					try {
						$o    = new Order( $order_id );
						if ( $o->getDeliveryId() and strlen( $o->Missing() ) ) {
							$data .= gui_row( array(
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
		$total_distance += get_distance( $path[ count( $path ) - 1 ], $mission->getEndAddress() ) / 1000;

		//	foreach ($path as $id => $stop_point){
		//		print $id ."<br/>";
		//	for ( $i = 0; $i < count( $data_lines[ $mission_id ] ); $i ++ ) {
		//		$line = $data_line[ $i ][1];
		//		$data .= trim( $line );
		//	}


		$data .= "</table>";

		save_route($the_mission, $path);

		$data .= "סך הכל ק\"מ " . $total_distance . "<br/>";

		if ( $debug )
			print_time( "end handle mission " . $mission_id, true);

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
	}
	$data .= "</div>";
	return $data;
}

/**
 * @param $stop_points
 * @param $point
 */
function add_stop_point( &$stop_points, $point ) {
	if ( ! in_array( $point, $stop_points ) ) {
		array_push( $stop_points, $point );
	}
//	print "adding $point<br/>";
//	var_dump($stop_points);
}

/**
 * @param $lines_per_station
 * @param $start_address
 * @param $stop_point
 * @param $line
 * @param $order_id
 */
function add_line_per_station(&$lines_per_station, $start_address, $stop_point, $line, $order_id ) {
	if ( ! isset( $lines_per_station[ $stop_point ] ) ) {
		$lines_per_station[ $stop_point ] = array();
	}
	if ( get_distance( $start_address, $stop_point ) or ( $start_address == $stop_point ) ) {
		array_push( $lines_per_station[ $stop_point ], array( $line, $order_id) );
	} else {
		print "לא מזהה את הכתובת של הזמנה " . $line . "<br/>";
	}
}

/**
 * @param $mission_ids
 * @param $header
 * @param $debug
 *
 * @return string
 */
function get_missions($mission_ids, $header, $debug)
{
    // $debug = 2;
    $data = "";

    if ($header){
        print delivery_table_header();
    }
	if (! is_array($mission_ids)) $mission_ids = array($mission_ids);
    if ($debug){
        print "missions_ids: "; var_dump($mission_ids); print "<br/>";
    }

    foreach ( $mission_ids as $mission_id ) {
        if ( $mission_id ) {
            $sql = "id in (select post_id from wp_postmeta " .
                   " WHERE meta_key = 'mission_id' " .
                   " AND meta_value = " . $mission_id . ") ";
            if ($debug != 2) $sql .= " and `post_status` in ('wc-awaiting-shipment', 'wc-processing')";
            // print $sql . "<br/>";
            $data .= print_deliveries( $sql, false, $debug );

            $data .= print_driver_supplies( $mission_id );

            $data .= print_driver_tasks( $mission_id );
        }
    }

    return $data;
}

/**
 * @param $query
 * @param bool $selectable
 * @param bool $debug
 *
 * @return string
 */
function print_deliveries( $query, $selectable = false, $debug = false ) {
	$data = "";
	$sql  = 'SELECT posts.id, order_is_group(posts.id), order_user(posts.id) '
	        . ' FROM `wp_posts` posts'
	        . ' WHERE ' . $query;

	$sql .= ' order by 1';

	if ( $debug ) {
		print $sql;
	}
	$orders    = sql_query( $sql );
	$prev_user = - 1;
	while ( $order = sql_fetch_row( $orders ) ) {
		$order_id   = $order[0];
		$o          = new Order( $order_id );
		$is_group   = $order[1];
		$order_user = $order[2];
		if ( $debug )
			print "order " . $order_id . "<br/>";

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

/**
 * @param $ref
 * @param $fields
 * @param bool $edit
 *
 * @return string
 */

/**
 * @param int $mission_id
 *
 * @return string
 * @throws Exception
 */
function print_driver_supplies( $mission_id = 0 ) {
	// Self collect supplies
	$data = "";
	$sql  = "SELECT s.id FROM im_supplies s
          JOIN im_suppliers r
          WHERE r.self_collect = 1
          AND s.supplier = r.id
          AND s.status IN (1, 3)" .
	        " AND (s.picked = 0 or isnull(s.picked))";

	// print $sql;

	if ( $mission_id ) {
		$sql .= " AND s.mission_id = " . $mission_id;
	}
	// DEBUG $data .= $sql;

	$supplies = sql_query_array_scalar( $sql );

	if ( count( $supplies ) ) {
		foreach ( $supplies as $supply ) {
//			   print "id: " . $supply . "<br/>";
			$data .= print_supply( $supply );
		}
	}

	return $data;
}


/**
 * @param $id
 *
 * @return string
 * @throws Exception
 */
function print_supply( $id ) {
    $s = new Fresh_Supply($id);
	if ( ! ( $id > 0 ) ) {
		throw new Exception( "bad id: " . $id );
	}

	$fields = array();
	array_push( $fields, "supplies" );

	$supplier_id = supply_get_supplier_id( $id );
	$ref         = Core_Html::GuiHyperlink( $id, "../supplies/supply-get.php?id=" . $id );
	$address     = $s->getAddress();

	array_push( $fields, $ref );
	array_push( $fields, $supplier_id );
	array_push( $fields, "<b>איסוף</b> " . get_supplier_name( $supplier_id ) );
	array_push( $fields, "<a href='waze://?q=$address'>$address</a>" );
	array_push( $fields, "" );
	array_push( $fields, sql_query_single_scalar( "select supplier_contact_phone from im_suppliers where id = " . $supplier_id ) );
	array_push( $fields, "" );
	array_push( $fields, sql_query_single_scalar( "select mission_id from im_supplies where id = " . $id ) );
	array_push( $fields, Core_Db_MultiSite::LocalSiteID() );

	$line = "<tr> " . delivery_table_line( 1, $fields ) . "</tr>";

	return $line;

}

/**
 * @param $id
 *
 * @return string
 */
function print_task( $id ) {
	$fields = array();
	array_push( $fields, "משימות" );
	$m = Core_Db_MultiSite::getInstance();

	$ref = Core_Html::GuiHyperlink( $id, $m->LocalSiteTools() . "/focus/focus-page.php?operation=show_task&id=" . $id );

	array_push( $fields, $ref );

	$T = new Focus_Tasklist( $id );

	array_push( $fields, "" ); // client number
	array_push( $fields, $T->getLocationName() ); // name
	array_push( $fields, $T->getLocationAddress() ); // address
	array_push( $fields, $T->getTaskDescription() ); // address 2
	array_push( $fields, "" ); // phone
	array_push( $fields, "" ); // payment
	array_push( $fields, $T->getMissionId() ); // payment
	array_push( $fields, Core_Db_MultiSite::LocalSiteID() );

	$line = gui_row( $fields );

	return $line;

}

/**
 * @param int $mission_id
 *
 * @return string
 * @throws Exception
 */
function print_driver_tasks( $mission_id = 0 ) {
	$data = "";
	if ( ! table_exists( 'im_tasklist' ) ) {
		return "";
	}

	// Self collect supplies
	$sql = "SELECT t.id FROM im_tasklist t " .
	       "WHERE (status < 2)";

	if ( $mission_id ) {
		$sql .= " and t.mission_id = " . $mission_id;
	}

	$tasks = sql_query_array_scalar( $sql );
	foreach ( $tasks as $task ) {
		$data .= print_task( $task );
	}

	return $data;
}


/**
 * @param bool $edit
 *
 * @return string
 */
function delivery_table_header( $edit = false ) {
	$data = "";
	$data .= "<table><tr>";
	$data .= "<td><h3>אתר</h3></td>";
	$data .= "<td><h3>מספר </br>/הזמנה<br/>אספקה</h3></td>";
	$data .= "<td><h3>מספר </br>לקוח</h3></td>";
//	$data .= "<td><h3>שם המזמין</h3></td>";
	$data .= "<td><h3>שם המקבל</h3></td>";
	$data .= "<td><h3>כתובת</h3></td>";
	$data .= "<td><h3>כתובת-2</h3></td>";
	$data .= "<td><h3>טלפון</h3></td>";
	// $data .= "<td><h3></h3></td>";
	$data .= "<td><h3>מזומן/המחאה</h3></td>";
	$data .= "<td><h3>משימה</h3></td>";
	$data .= "<td><h3>אתר</h3></td>";
	$data .= "<td><h3>מספר משלוח</h3></td>";

	// $data .= "<td><h3>מיקום</h3></td>";
	return $data;
}

/**
 * @param $missions
 * @param $path
 */
function save_route($missions, $path) {
//    print "missions=$missions<br/>";
//    print "path=" . var_dump($path);
	! is_array( $missions ) or die ( "missions array" );

	sql_query( "update im_missions set path = \"" . CommaImplode($path, true) . "\" where id = " . $missions );
}

/**
 * @param $mission
 *
 * @throws Exception
 */
function edit_route($mission)
{
    if (! $mission) die ("no mission");

    print Core_Html::gui_header(1, "Mission", true, true); print gui_label("mission_id", $mission);
	$m = new Mission($mission);
    $path = sql_query_single_scalar("select path from im_missions where id = $mission");

    $points = explode("," , $path); foreach ($points as $key => $point) $points[$key] = trim($point, "' ");
	$list = $points;
	array_unshift($list, "select");
    $table = array(array("point", "preq"));
    $prerequisite = array();
    foreach ($points as $point)
	    $prerequisite[$point] = info_get("mission_preq_" . $mission . "." . $point, false, "select");

	$new_path = array();
	find_route_1( $m->getStartAddress(), $points, $new_path, false, $m->getEndAddress(), $prerequisite );

	foreach ($new_path as $point)
		array_push($table, array($point, gui_simple_select($point, $list, "onchange=\"update_path_preq('" . $point . "')\"", null, $prerequisite[$point])));

    print gui_table_args($table);

}

/**
 * @param $mission
 * @param $path
 *
 * @return string
 */
function get_maps_url($mission, $path)
{
	$url = "https://www.google.com/maps/dir/" . $mission->getStartAddress();
	print $mission->getStartAddress();
	$dynamic_url = "https://www.google.com/maps/dir/My+Location";

	for ( $i = 0; $i < count( $path ); $i ++ ) {
		$url .= "/" . $path[ $i ];
		$dynamic_url .= "/" . $path[ $i ];
	}
	$url .= "/" . $mission->getEndAddress();
	return Core_Html::GuiHyperlink( "Maps", $url ) . " " . Core_Html::GuiHyperlink("Dyn", $dynamic_url);
}

/**
 * @param $data_lines
 * @param $mission_id
 * @param $prerequisite
 * @param $supplies_to_collect
 * @param $lines_per_station
 * @param $stop_points
 *
 * @throws Exception
 */
function collect_points($data_lines, $mission_id, &$prerequisite, &$supplies_to_collect, &$lines_per_station, &$stop_points)
{
    $multisite = Core_Db_MultiSite::getInstance();
    $stop_points = array();

	$mission = new Mission($mission_id);

	for ( $i = 0; $i < count( $data_lines[ $mission_id ] ); $i ++ ) {
	    print "<br/>";

		$stop_point = $data_lines[ $mission_id ][ $i ][0];

		// print "<br/>sp=" . $stop_point; var_dump($prerequisite);
		$dom        = im_str_get_html( $data_lines[ $mission_id ][ $i ][1] );
		$row        = $dom->find( 'tr' );
		$site       = table_get_text( $row[0], 0 );
		$site_id    = table_get_text( $row[0], 8 );
		$order_id   = table_get_text( $row[0], 1 );
		$customer   = table_get_text( $row[0], 2 );
		$pickup_address = Core_Db_MultiSite::getPickupAddress( $site_id );

		// Deliveries created in other place
		if ( $site != "משימות" and $site != "supplies" and $pickup_address != $mission->getStartAddress() ) {
		    // print "adding $pickup_address<br/>";
			$prerequisite[$stop_point] = $pickup_address;
			// Add Pickup
			add_stop_point( $stop_points, $pickup_address );
			add_line_per_station($lines_per_station, $mission->getStartAddress(), $pickup_address, gui_row( array(
				$site,
				$order_id,
				"<b>איסוף </b>" . $customer,
				$pickup_address,
				"",
				"",
				"",
				"",
				""
			) ), $order_id );
		}
		if ( $site == "supplies" ) {
			array_push( $supplies_to_collect, array( $order_id, $site_id ) );
		}

		// print "stop point: " . $stop_point . "<br/>";

		add_stop_point($stop_points, $stop_point );
		if (! isset($prerequisite[$stop_point])) {
			$p = info_get("mission_preq_" . $mission_id . "." . $stop_point, false, null);
			// print "adding $p<br/>";
		    if (strlen($p)) $prerequisite[$stop_point] = $p;
		}

		//		array_push( $stop_points, $stop_point );
		add_line_per_station($lines_per_station, $mission->getStartAddress(), $stop_point, $data_lines[ $mission_id ][ $i ][1], $order_id );

		// Check if we need to collect something on the go
		if ($site_id == $multisite->getLocalSiteID() and $site != "supplies"){
//                print "handle $order_id<br/>";
			$order = new Order($order_id);
			if ($supply_points = $order->SuppliersOnTheGo()){
				$supplier = new Fresh_Supplier($supply_points[0]);
				$prerequisite[$stop_point] = $supplier->getAddress();
			}
		}
		// print $stop_point . " preq: " . $prerequisite[$stop_point] . "<br/>";
	}
}

/**
 * @param null $args
 *
 * @return string
 * @throws Exception
 */
function show_paths($args = null)
{
    $result = "";
    $result .= Core_Html::gui_header(1, "Shipping paths");
    $args["selectors"] = array(/* "zones" => "gui_select_zones", */"week_days" => "gui_select_days");
    $args["id_field"] = "id";
    $args["links"] = array("id" => AddToUrl(array( "operation" => "show_path", "path_id" => "%s")));
    $args["add_checkbox"] = true;
    $args["header_fields"] = array("checkbox" => "select", "id" => "Id", "path_code" => "Path code", "description" => "Description", "zones_times" => "Zones", "week_days" => "Week days");

	$paths_data = Core_Data::TableData("select * from im_paths", $args);
	$args["edit"] = false;
	foreach ($paths_data as $path_id => &$path_info){
		if ($path_id == "header") continue;
		$path_info['zones_times'] = path_get_zones($path_id, $args);
	}
	$result .= GemArray($paths_data, $args, "im_paths");
    $result .= Core_Html::GuiButton("btn_instance", "create_missions()", "create missions");

    $result .= Core_Html::gui_header(2, "Coming missions");
    $result .= show_missions( "date > " . QuoteText(date('Y-m-d')));

    $result .= "<br/>";
    $result .= GuiHyperlink("עדכון שיטות משלוח", "/routes/routes-page.php?operation=update_shipping_methods");

    return $result;
}


/**
 * @param $path_id
 * @param bool $sorted
 *
 * @return string
 */
function path_get_zones($path_id, $sorted = true)
{
	$zone_times = path_get_zone_times($path_id);

	$result = "";
	foreach ($zone_times as $zone_id => $zone)
		$result .= zone_get_name($zone_id) . ", ";

	return rtrim($result, ", ");
}

function path_add_zone($path_id, $zones, $time)
{
	$time_zones = path_get_zone_times($path_id);
	foreach($zones as $zone)
		$time_zones[$zone] = $time;
	$s = serialize($time_zones);
	$sql = "update im_paths set zones_times = '" . $s . "' where id = " . $path_id;
	return sql_query($sql);
}

/**
 * @param $path_id
 * @param bool $sorted
 *
 * @return array|mixed
 */

/**
 * @param $path_id
 * @param $args
 *
 * @return string|null
 * @throws Exception
 */
function path_get_zone_time_table($path_id, $args)
{
	$table = [];
	$sorted = GetArg($args, "sort", true);
	$zone_times = path_get_zone_times($path_id, $sorted);
	foreach (path_get_zone_times($path_id) as $zone_id => $zone_time) {
		// $row_event = sprintf($events, $zone_id);
		// $args["events"] = $row_event;
		$table[$zone_id] = array("id" => $zone_id,
		                         "name" => zone_get_name($zone_id),
		                         "times" => GuiInput("times_" . $zone_id, $zone_times[$zone_id], $args));
	}
	array_unshift($table, array("Id", "Zone name", "Zone times"));
	$args["add_checkbox"] = true;
	$args["add_button"] = false;
	return GemArray($table, $args, "zone_times");
}

/**
 * @param $path_id
 *
 * @return string
 * @throws Exception
 */
function show_path($path_id)
{
    $result = "";
    $args = [];
	$args["selectors"] = array("week_days" => "gui_select_days");
	$args["hide_cols"] = array("zones_times" => 1);
    $result .= GemElement("im_paths", $path_id, $args);
    $table = array();
    // $events = 'onchange=onchange=changed_field(%s)';
	$result .= path_get_zone_time_table($path_id, $args);
    $result .= Core_Html::GuiButton("btn_save", "save_path_times(" . $path_id .")", "Save");
	$result .= Core_Html::GuiButton("btn_delete", "delete_path_times(" . $path_id .")", "Delete");

    print Core_Html::Br();

    $result .= gui_table_args(array("header" => array("zone_id" => "Zone", "zone_times" => "Times"),
	    array("zone_id" => gui_select_zones("zone_id", null, array("edit"=> true)),
	          "zone_times" => GuiInput("zone_time", "13-16"))));

    $result .= Core_Html::GuiButton("btn_add_zone_times", "add_zone_times(" . $path_id . ")", "Add");

    return $result;
}

/**
 * @param $path_id
 * @param $params
 *
 * @return bool|mysqli_result|null
 */
function path_save_times($path_id, $params)
{
	$path_times = array();
	for ($i = 0; $i < count($params); $i += 2) {
		$zone_id = $params[$i];
		$times = $params[$i + 1];
		$path_times[$zone_id] = $times;
	}
	$sql = "update im_paths set zones_times = " . QuoteText(escape_string(serialize($path_times))) . ' where id = ' . $path_id ;
	return sql_query($sql);
}


/**
 * @return string
 */
function show_add_paths()
{
	$args = [];
	$args["selectors"] = array("zones" => "gui_select_zones");
	$args["mandatory_fields"] = array("description", "zones");
	return GemAddRow("im_paths", "Add", $args);
}

function show_today_routes()
{
	$result = Core_Html::gui_header(1, "today routes");
	$sql = "select id from im_missions where date = '" . date("Y-m-d") . "'";
	$missions = sql_query_array_scalar($sql);

	if (count($missions) == 1) $result .= show_mission_route($missions[0]);
	if (count($missions) == 0) $result .= im_translate("No missions today");
	return $result;

}

function zone_get_name( $id ) {
	if (! ($id > 0)){
		print sql_trace();
		die ("bad zone id");
	}
	return sql_query_single_scalar( "SELECT zone_name FROM wp_woocommerce_shipping_zones WHERE zone_id = " . $id );
}