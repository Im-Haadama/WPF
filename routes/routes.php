<?php

require_once(ROOT_DIR . "/niver/data/dom.php");
require_once(ROOT_DIR . "/fresh/orders/Order.php");
require_once (ROOT_DIR . "/fresh/suppliers/Supplier.php");
require_once(ROOT_DIR . "/focus/Tasklist.php");
require_once(ROOT_DIR . "/fresh/catalog/gui.php");

function handle_routes_operation($operation, $debug = false) {
	if ( $debug ) {
		print "operation: " . $operation . "<br/>";
	}
	switch ( $operation ) {
		case "show_routes":
		    $edit_route = get_param("edit_route", false, false);
		    if ($edit_route) {
		        edit_route(get_param("id", true));
		        return;
            }
			if ($id = get_param("id", false)) {
				print show_route($id, false, false, false);
				return;
			}
		    $week = get_param("week");
			show_missions($week);
			break;

        case "update_mission_preq":
            $mission = get_param("id", true);
            $point = get_param("point", true);
            $preq = get_param("preq", true);
            $key = "mission_preq_" . $mission . "." . $point;
            if ($preq == "select")
                info_update($key, null);
            else
                info_update($key, $preq);
            print "done";
            break;

        case "get_local":
            $mission_ids = get_param("mission_ids", true);
            $header = get_param("header", false, false);
            print get_missions($mission_ids, $header, $debug);
            break;

        case "update_mission":
            $id = get_param("id");
            $start = get_param("start");
            $start_point = get_param("start_point");
            $m = new Mission($id);
            $m->setStartTime($start);
            $m->setStartAddress($start_point);
            print show_route($id, true);  // in update don't show header (logo, time, etc);
            break;

		case "delivered":
			$site_id = get_param( "site_id" );
			$type    = get_param( "type" );
			$id      = get_param( "id" );
			if (delivered($site_id, $type, $id, $debug))
			    print "delivered";
			break;

        default:
            die ("operation $operation not handled");
	}
}

function show_missions($week)
{
    // print __FUNCTION__ . "<br/>";
    $debug = 0;

    if ($debug) print "week: $week <br/>";

    if ($week){
       $sql = "select id from im_missions where FIRST_DAY_OF_WEEK(date) = " . quote_text($week);
   } else {
	    $sql = "SELECT id FROM im_missions WHERE date = curdate()";
    }

	$missing = false;
	$missions = sql_query_array_scalar($sql);

	if ($debug){
	    print "sql: $sql<br/>";
	    print "missions: "; var_dump($missing); print "<br/>";
    }

	print gui_hyperlink("This week", get_url(1) . "?operation=show_routes&week=" . date( "Y-m-d", strtotime( "last sunday" ))) . " ";
	print gui_hyperlink("Next week", get_url(1) . "?operation=show_routes&week=" . date( "Y-m-d", strtotime( "next sunday" )));

	if ( ! count( $missions ) ) {
		print "No deliveries for today ";
		return;
	}
	if (count($missions) == 1) {
		print show_route($missions[0], false, $debug, $missing);
		return;
	}
	do_show_missions($missions);
}

function do_show_missions($missions)
{
	$args = array();
	$args["edit"] = true;

	$sql = "select * from im_missions where id in (". comma_implode($missions) . ")";

	$args["links"] = array("id" => get_url(true) . "?operation=show_routes&id=%s");
	$args ["edit_cols"] = array(0, 1,1,1,1,1);
	// $args["events"] = array("mission_id" => "mission_changed(order_id))

	print GuiTableContent("missions", $sql, $args);
}

//print gui_hyperlink( "שבוע קודם", "get-driver-multi.php?week=" . date( 'Y-m-d', strtotime( $week . " -1 week" ) ) );

function delivered($site_id, $type, $id, $debug = false)
{
    if ( $debug ) {
        print "start<br/>";
    }
    if ( $site_id != ImMultiSite::LocalSiteID() ) {
        if ( $debug ) {
            print "remote.. ";
        }
        $request = "/routes/routes-post.php?site_id=" . $site_id .
                   "&type=" . $type . "&id=" . $id . "&operation=delivered";
        if ( $debug ) {
            $request .= "&debug=1";
            print $request;
        }
        if (ImMultiSite::sExecute( $request, $site_id, $debug ) == "delivered")  return true;
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
            $t = new Tasklist( $id );
            $t->Ended();
            return true;
            break;
        case "supplies":
            $s = new Supply( $id );
            $s->picked();
            return true;
            break;
    }
    return false;
}

?>
<?php

//show_path($debug, $missing);

// Start collecting data
function show_route($missions, $update = false, $debug = false, $missing = false)
{
	$stop_points = array();
	$lines_per_station = array();
	$m      = ImMultiSite::getInstance();

	$prerequisite = array();
	$data = '<div id="route_div">';

	$data_lines = array();
	$table_header     = null;

	$data_url = "/routes/routes-post.php?operation=get_local&mission_ids=" . comma_implode( $missions );
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
			$table_header .= gui_cell( gui_header( 3, "מספר ארגזים, קירור" ) );
			$table_header .= gui_cell( gui_header( 3, "נמסר" ));
			$table_header .= gui_cell( gui_header( 3, "ק\"מ ליעד" ) );
			$table_header .= gui_cell( gui_header( 3, "דקות" ) );
			$table_header .= gui_cell( gui_header( 3, "דקות מצטבר" ));
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
			print gui_header( 1, get_mission_name( $mission_id ), true, true ) . "(" . gui_label("mission_id", $mission_id) . ")";

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
        $data .= gui_hyperlink("Edit route", add_to_url(array("edit_route" => 1, "id" => $mission_id)));
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

		save_route($missions, $path);

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
					$s = new Supply( $supply_id );
					$data .= gui_header( 1, "אספקה  " . $supply_id . " מספק " . $s->getSupplierName() );
					$data .= $s->Html( true, 0 );
				}
			}
		}
	}
	$data .= "</div>";
	return $data;
}

function add_stop_point( &$stop_points, $point ) {
	if ( ! in_array( $point, $stop_points ) ) {
		array_push( $stop_points, $point );
	}
//	print "adding $point<br/>";
//	var_dump($stop_points);
}

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

function delivery_table_line( $ref, $fields, $edit = false ) {
	//"onclick=\"close_orders()\""
	$row_text = "";
	if ( $edit ) {
		$row_text = gui_cell( gui_checkbox( "chk_" . $ref, "", "", null ) );
	}

	foreach ( $fields as $field ) // display customer name
	{
		$row_text .= gui_cell( $field );
	}

	return $row_text;
}

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


function print_supply( $id ) {
    $s = new Supply($id);
	if ( ! ( $id > 0 ) ) {
		throw new Exception( "bad id: " . $id );
	}

	$fields = array();
	array_push( $fields, "supplies" );

	$supplier_id = supply_get_supplier_id( $id );
	$ref         = gui_hyperlink( $id, "../supplies/supply-get.php?id=" . $id );
	$address     = $s->getAddress();

	array_push( $fields, $ref );
	array_push( $fields, $supplier_id );
	array_push( $fields, "<b>איסוף</b> " . get_supplier_name( $supplier_id ) );
	array_push( $fields, "<a href='waze://?q=$address'>$address</a>" );
	array_push( $fields, "" );
	array_push( $fields, sql_query_single_scalar( "select supplier_contact_phone from im_suppliers where id = " . $supplier_id ) );
	array_push( $fields, "" );
	array_push( $fields, sql_query_single_scalar( "select mission_id from im_supplies where id = " . $id ) );
	array_push( $fields, imMultiSite::LocalSiteID() );

	$line = "<tr> " . delivery_table_line( 1, $fields ) . "</tr>";

	return $line;

}

function print_task( $id ) {
	$fields = array();
	array_push( $fields, "משימות" );
	$m = ImMultiSite::getInstance();

	$ref = gui_hyperlink( $id, $m->LocalSiteTools() . "/focus/focus-page.php?operation=show_task&id=" . $id );

	array_push( $fields, $ref );

	$T = new Tasklist( $id );

	array_push( $fields, "" ); // client number
	array_push( $fields, $T->getLocationName() ); // name
	array_push( $fields, $T->getLocationAddress() ); // address
	array_push( $fields, $T->getTaskDescription() ); // address 2
	array_push( $fields, "" ); // phone
	array_push( $fields, "" ); // payment
	array_push( $fields, $T->getMissionId() ); // payment
	array_push( $fields, ImMultiSite::LocalSiteID() );

	$line = gui_row( $fields );

	return $line;

}

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

function save_route($missions, $path) {
//    print "missions=$missions<br/>";
//    print "path=" . var_dump($path);
	! is_array( $missions ) or die ( "missions array" );

	sql_query( "update im_missions set path = \"" . comma_implode($path, true) . "\" where id = " . $missions );
}

function edit_route($mission)
{
    if (! $mission) die ("no mission");

    print gui_header(1, "Mission", true, true); print gui_label("mission_id", $mission);
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

function get_maps_url($mission, $path)
{
	$url = "https://www.google.com/maps/dir/" . $mission->getStartAddress();
	$dynamic_url = "https://www.google.com/maps/dir/My+Location";

	for ( $i = 0; $i < count( $path ); $i ++ ) {
		$url .= "/" . $path[ $i ];
		$dynamic_url .= "/" . $path[ $i ];
	}
	$url .= "/" . $mission->getEndAddress();
	return gui_hyperlink( "Maps", $url ) . " " . gui_hyperlink("Dyn", $dynamic_url);
}

function collect_points($data_lines, $mission_id, &$prerequisite, &$supplies_to_collect, &$lines_per_station, &$stop_points)
{
    $multisite = ImMultiSite::getInstance();
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
		$pickup_address = ImMultiSite::getPickupAddress( $site_id );

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
				$supplier = new Supplier($supply_points[0]);
				$prerequisite[$stop_point] = $supplier->getAddress();
			}
		}
		// print $stop_point . " preq: " . $prerequisite[$stop_point] . "<br/>";
	}
}

return;
?>



<!--	<style>-->
<!--		@media print {-->
<!--			h1 {-->
<!--				page-break-before: always;-->
<!--			}-->
<!--		}-->
<!--	</style>-->


