<?php

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
		    updateWp_woocommerce_shipping_zone_methods($args);
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

/**
 * @param $stop_points
 * @param $point
 */

/**
 * @param $lines_per_station
 * @param $start_address
 * @param $stop_point
 * @param $line
 * @param $order_id
 */

/**
 * @param $mission_ids
 * @param $header
 * @param $debug
 *
 * @return string
 */

/**
 * @param $query
 * @param bool $selectable
 * @param bool $debug
 *
 * @return string
 */

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


/**
 * @param bool $edit
 *
 * @return string
 */

/**
 * @param $missions
 * @param $path
 */

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

/**
 * @param null $args
 *
 * @return string
 * @throws Exception
 */


/**
 * @param $path_id
 * @param bool $sorted
 *
 * @return string
 */


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

/**
 * @param $path_id
 *
 * @return string
 * @throws Exception
 */

/**
 * @param $path_id
 * @param $params
 *
 * @return bool|mysqli_result|null
 */


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
	if (count($missions) == 0) $result .= ImTranslate("No missions today");
	return $result;

}


