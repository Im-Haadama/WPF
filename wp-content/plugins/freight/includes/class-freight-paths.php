<?php


class Freight_Paths {

	/**
	 * Freight_Zones constructor.
	 */
	static public function init() {
		Core_Gem::AddTable("path_shipments");
		Core_Gem::AddTable("paths");
		add_action("show_path", __CLASS__ . "::show_path_wrap");
		add_action("add_zone_times", __CLASS__ . "::add_zone_times");
		add_action("path_remove_times", __CLASS__ . "::remove_zone_times");
		add_action("create_missions", __CLASS__ . "::create_missions");
		add_action("update_shipping_methods",  "Fresh_Delivery_Manager::update_shipping_methods");
		add_action("create_shipping_method", __CLASS__ . "::create_shipping_method");
		add_action("path_save_days", __CLASS__ . "::path_save_days");
		add_action("path_create_instance", __CLASS__ . "::path_create_instance");
		add_action("path_create_instance", __CLASS__ . "::show_path_wrap");
		add_action("update_shipment_instance", __CLASS__ . "::update_shipment_instance");

		// Delete instance and show the path
		add_action("delete_shipment_instance", __CLASS__ . "::delete_shipment_instance");
		add_action("delete_shipment_instance", __CLASS__ . "::show_path_wrap");

		add_action("update_zone_missions", __CLASS__ . "::update_zones_missions");
		add_action("show_mission", __CLASS__ . "::show_mission_wrap");
	}

	static function settings($args = null, $operation = null) {
		return "lalalal";
		print 1/0;
		$result                = "";
		$args["post_file"] = Freight::getPost();

		if ($operation) {
			$id = GetParam( "id", false, null );
			$args["operation"] = $operation;

			$output = apply_filters( $operation, "", $id, $args, null );
			if ($output)
				return $result . $output;
		}
		$result                .= Core_Html::gui_header( 1, "Shipping paths" );

		$args["edit"] = true;
		$args["selectors"]     = array("zones" => "GuiSelectZones"
//			"week_days" => "Core_Html::gui_select_days"
		);
		$args["id_field"]      = "id";
		$args["links"]         = array( "id" => AddToUrl( array( "operation" => "show_path", "path_id" => "%s" ) ) );
		$args["add_checkbox"]  = true;
		$args["header_fields"] = array( "checkbox"    => "select",
		                                "id"          => "Id",
		                                "path_code"   => "Path code",
		                                "description" => "Description",
		                                "zones_times" => "Zones",
		                                "week_days"   => "Week days",
		);
		$args["class"] = "widefat";
//		$args["events"] = 'onchange="changed(this)"';

//		$paths_data   = Core_Data::TableData( "select * from ${db_prefix}paths", $args );
////		$args["edit"] = false;
//		if ( $paths_data ) {
//			foreach ( $paths_data as $path_id => &$path_info ) {
//				if ( $path_id == "header" ) {
//					continue;
//				}
//				//$path_info['zones_times'] = path_get_zones( $path_id, $args );
//			}
//		}

		$result .= Core_Gem::GemTable("paths", $args);
//		$result .= Core_Html::GuiButton("btn_save", "save", array("action" => "save_paths()"));


		$result .= "<br/>";
		$result .= Core_Html::GuiButton("btn_instance", "Create Missions", array("action" => "create_missions('" . Freight::getPost() . "')"));

//		$result .= Core_Html::gui_header(2, "Coming missions");

		$result .= "<br/>";
		$result .= Core_Html::GuiHyperlink("עדכון שיטות משלוח", AddToUrl("operation", "update_shipping_methods"));

		return $result;
	}

	static function prepare_path_line($line)
	{
		$id = $line['id'];
		if (! $line['instance']) {
			$line['instance'] = Core_Html::GuiHyperlink("Add", AddToUrl(array("operation" => "path_create_instance", "id"=> $line['id'])));
		} else {
			$line['hours'] = Core_Html::GuiInput("hours_" . $line['id'], $line['hours'], 
				array("events" => 'onchange="' . Core_Data::UpdateTableFieldEvent(Freight::getPost(), "path_shipments", $line['id'], "hours") .
					';update_shipment_instance(\'' . Freight::getPost() . "', ". $line['id'] .")\""));

			$line['instance'] = Core_Html::GuiHyperlink($line['instance'], "/wp-admin/admin.php?page=wc-settings&tab=shipping&instance_id=" . $line['instance']) . "<br/>" .
			                    Core_Html::GuiHyperlink("delete", AddToUrl(array("operation" =>"delete_shipment_instance", "id" => $line['id'])));
		}

		return $line;
	}

	static function path_create_instance()
	{
		$method_id = GetParam("id", true);
		$P = Freight_Shipment::LoadFromDB($method_id);

		$P->CreateInstance();
	}

	static function show_path_wrap()
	{
		$path_id = GetParam("path_id", true);
		return self::show_path($path_id);
	}

//	static function add_zone_times()
//	{
//		$zones = GetParamArray("zones", true, null, ":");
//		$time = GetParam("time");
//		$path_id = GetParam("path_id");
//
//		path_add_zone($path_id, $zones, $time);
//	}

	static function remove_zone_times()
	{
		$path_id = GetParam("path_id");
		$params = GetParam("params");
		$zone_times = path_get_zone_times($path_id);
		foreach (explode(",", $params) as $zone_name)
			unset($zone_times[$zone_name]);
			//print "removing $zone_name<br/>";
		path_save_times($path_id, $zone_times);
	}
	static function create_missions()
	{
		$path_ids = GetParamArray("path_ids", true); // Path ids.
		return Fresh_Delivery_Manager::create_missions($path_ids);
	}

	static function update_shipment_instance()
	{
		$id = GetParam("id", true);
		$s = Freight_Shipment::LoadFromDB($id);
		$s->update_instance();
	}

	static function delete_shipment_instance()
	{
		$id = GetParam("id", true);
		$s = Freight_Shipment::LoadFromDb($id);
		$s->delete_instance();
	}

	static function path_save_days()
	{
		$days = explode(":", GetParam("day", true));
		$path = GetParam("path_id", true);
		$P = new Freight_Path($path);
//		var_dump($days);
		$P->setDays($days);
//		foreach ($days as $weekday)
//			foreach ($P->getZones() as $zone) {
//				if (Freight_Shipment::exists($path, $zone, $weekday)) continue;
//
//				Freight_Shipment::AddMethod($path, $zone, $weekday);
//			}
	}

	static function show_path($path_id)
	{
		$P = new Freight_Path($path_id);
		$result = "";
		$args = [];
		$args["selectors"] = array("week_days" => "Core_Html::gui_select_days");
		$args["hide_cols"] = array("zones_times" => 1);
		$args["post_file"] = Freight::getPost();
		$args["selectors"] = array("week_day" => "Core_Html::gui_select_days", "zone" => "GuiSelectZones");
		$args["query"] = "path_id = $path_id";
		$args["fields"] = array("id", "zone", "week_day", "hours", "instance");
		$args["prepare_plug"] = array("Freight_Paths", "prepare_path_line");

		$result .= Core_Html::GuiHeader(1, __("Path") . " " . $P->getDescription());

		$result .= Core_Gem::GemTable("path_shipments", $args);

		/////////////
		// Add day //
		/////////////
		$result .= "<div>";
		$args = [];
		$args["edit"] = true;
		$result .= Core_Html::GuiHeader(1, "Path days");
		$days = sql_query_single_scalar("select days from im_paths where id = " . $path_id);
		$result .= Core_Html::gui_select_days("path_days", $days, $args);
		$result .= Core_Html::GuiButton("btn_add", "Save", array("action" => "path_save_days('" . Freight::getPost() . "', $path_id)"));

		$result .="</div>";
//	$result .= Core_Gem::GemElement("path_shipments", $path_id, $args);

//	$result .= path_get_zone_time_table($path_id, $args);
//	$result .= Core_Html::GuiButton("btn_save", "Save", array("action" => "save_path_times(" . $path_id .")"));
//	$result .= Core_Html::GuiButton("btn_delete", "Delete", array("action"=>"delete_path_times(" . $path_id .", '" . Freight::getPost() . "')"));

//	$result .= Core_Html::Br();

//	$result .= Core_Html::GuiHeader(2, "Add zones (with times)");

//	$result .= Core_Html::gui_table_args(array("header" => array("zone_id" => "Zone", "zone_times" => "Times"),
//		array("zone_id" => GuiSelectZones("zone_id", null, array( "edit" => true)),
//		      "zone_times" => Core_Html::GuiInput("zone_time", "13-16"))));

//	$result .= Core_Html::GuiButton("btn_add_zone_times", "Add", array("action"=>"add_zone_times(" . $path_id . ", '" . GetUrl() . "')"));

		return $result;
	}

	function update_zone_missions()
	{
		$result = "Updating...<br/>";

		return $result;
	}

	static function show_mission_wrap()
	{
		print 1/0;
		$result = "";
		$mission_id = GetParam("id", true);
		$args = [];
		$result .= Core_Gem::GemElement("mission", $mission_id, $args);
		$result .= self::show_mission($mission_id);
		print $result;
	}

	static function show_mission($mission_id)
	{
		print 1/0;
		if (! ($mission_id > 0)) die ("bad mission_id " .$mission_id);
//		$result = Core_Html::gui_header(1, ImTranslate("mission") . " $mission_id");
$result = "XXX";
		$args = [];
		$args["selectors"] = array("path_code" => __CLASS__ . "::gui_select_path");
		$args["edit"] = true;
		$args["post_file"] = Freight::getPost();
		$result .= Core_Gem::GemElement("missions", $mission_id, $args);
		$zone_table = array();
		$zone_table["header"] = array("Zone id", "shipping method");
		$mission = new Mission($mission_id);

		$shipping_ids = $mission->getShippingMethods();
		foreach ($shipping_ids as $zone_id => $shipping) {
			$tog = ($shipping->enabled == "yes") ? "disable" : "enable";
			$args["action"] = AddToUrl(array( "operation" => $tog . "_shipping_method&zone_id=" . $zone_id . "&instance_id=" . $shipping->instance_id)) . ";location_reload";

			$args["text"] = $tog;
			$en_dis = Core_Html::GuiButtonOrHyperlink("btn_" . $zone_id, null, $args);

			array_push($zone_table, array(ZoneGetName($zone_id), $shipping->title, $shipping->enabled, $en_dis));
		}
//	 $args["actions"] = array(array("enable", add_to_url(array("operation" => ))));
//	$zone_table[ $zone_id ] = array(
//		zone_get_name( $zone_id ),
//		( $mission_method ? $mission_method->title : "none" )
//	);

		$result .= Core_Html::gui_table_args($zone_table, "", $args);

		$result .= Core_Html::GuiHyperlink("update", AddToUrl(array( "operation" => "update_shipping_methods")));
		return $result;
	}

	static function gui_select_path( $id, $selected = 0, $args = null )
	{
//	return gui_select_table( $id, "im_missions", $selected, $events, null,
//		"path_code", "where date > CURDATE()", true, false, null, "path_code" );
		$args["selected"] = $selected;
		$args["name"] = "description";
		return Core_Html::GuiSelectTable($id, "paths", $args);
	}

}

//function path_get_zones($path_id, $sorted = true)
//{
//	$zone_times = path_get_zone_times($path_id);
//
//	$result = "";
//	if ($zone_times)
//		foreach ($zone_times as $zone_id => $zone)
//			$result .= ZoneGetName($zone_id) . ", ";
//
//	return rtrim($result, ", ");
//}

//function path_add_zone($path_id, $zone_id, $week_day)
//{
//	$db_prefix = get_table_prefix();
//
//	return sql_query("insert into ${db_prefix}path_shipments (path_id, zone, week_day) values ($path_id, $zone_id, $week_day)");
//}

//function path_get_zone_times($path_id, $sorted = true)
//{
//	if (! ($path_id > 0))
//		return null;
//
//	// $zones =
//	$zone_times = unserialize(sql_query_single_scalar("select zones_times from im_paths where id = $path_id"));
//	if (! $zone_times) return null;
////	if (! $zone_times) { // Backward compatibility
////		$zone_times = array();
////		$zone = strtok($raw, ":");
////		while ($zone)
////		{
////			$zone_times[$zone] = "9-13";
////			$zone = strtok(":");
////		}
////	}
//	if ($sorted) uasort($zone_times,
//		function($a, $b) {
//			$start_a = strtok($a, "-");
//			$start_b = strtok($b, "-");
//			return $start_a <=> $start_b;
//		});
//
//	return $zone_times;
//}


//function path_get_zone_time_table($path_id, $args)
//{
//	$table = [];
//	$sorted = GetArg($args, "sort", true);
//	$zone_times = path_get_zone_times($path_id, $sorted);
//	foreach (path_get_zone_times($path_id) as $zone_id => $zone_info) { // Zone_info: start-end,instance_id
//		$time = strtok($zone_info, ",");
//		$instance = strtok(null);
//		$table[$zone_id] = array("id" => $zone_id,
//		                         "name" => zone_get_name($zone_id),
//		                         "times" => GuiInput("times_" . $zone_id, $time, $args),
//		                         "shipping_method" => gui_select_shipping_methods($zone_id, $instance));
//	}
//	array_unshift($table, array("Id", "Zone name", "Zone times"));
//	$args["add_checkbox"] = true;
//	return GemArray($table, $args, "zone_times");
//}

function PathGet_zone_time_table($path_id, $args)
{
	$table = [];
	$sorted = GetArg($args, "sort", true);
	$zone_times = path_get_zone_times($path_id, $sorted);
	if ($zone_times)
	foreach (path_get_zone_times($path_id) as $zone_id => $zone_info) {
		$time = strtok($zone_info, ",");
		$instance = strtok(null);
		// $row_event = sprintf($events, $zone_id);
		// $args["events"] = $row_event;
		$table[$zone_id] = array("id" => $zone_id,
		                         "name" => ZoneGetName($zone_id),
		                         "times" => Core_Html::GuiInput("times_" . $zone_id, $zone_times[$zone_id], $args),
		                         "shipping_method" => Guielect_shipping_methods($zone_id, $instance));
	}
	array_unshift($table, array("Id", "Zone name", "Zone times"));
	$args["add_checkbox"] = true;
	$args["add_button"] = false;
	return Core_Gem::GemArray($table, $args, "zone_times");
}

function GuiSelectZones($id, $selected, $args)
{
	$edit = GetArg($args, "edit", false);

	if (! $edit) {
		$f = strtok($selected, ":");
		$result = ZoneGetName($f);
		while ($z = strtok( ":")) $result .= ", " . ZoneGetName($z);
		return $result;
	}
	$wc_zones = WC_Shipping_Zones::get_zones();
//	var_dump($wc_zones);

	$args["values"] = $wc_zones;
	$events = GetArg($args, "events", null);
//	$args["multiple"] = true;

	return Core_Html::gui_select( $id, "zone_name", $wc_zones, $events, $selected, "id", "class", GetArg($args, "multiple", true) );
}

function ZoneGetName( $id ) {
	if (! ($id > 0)){
		return "bad zone id $id";
	}
	return sql_query_single_scalar( "SELECT zone_name FROM wp_woocommerce_shipping_zones WHERE zone_id = " . $id );
}

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

function Guielect_shipping_methods($zone_id, $selected)
{
	$wc_zones = WC_Shipping_Zones::get_zones();

	$table = [];
	foreach($wc_zones as $wc_zone) {
		if ($wc_zone['id'] != $zone_id) continue;
		foreach ($wc_zone['shipping_methods'] as $shipping){
			array_push($table, array("id"=>$shipping->instance_id, "name" => $shipping->title));
		}
	}

	return Core_Html::gui_select("ship_" . $zone_id, "name", $table, "", $selected, "id") .
	       Core_Html::GuiHyperlink("Create", AddToUrl(array("operation" => "create_shipping_method", "zone_id"=>$zone_id)));
}

//function gui_select_zones($id, $selected, $args)
//{
//	$edit = GetArg($args, "edit", false);
//
//	if (! $edit) {
//		$f = strtok($selected, ":");
//		$result = ZoneGetName($f);
//		while ($z = strtok( ":")) $result .= ", " . ZoneGetName($z);
//		return $result;
//	}
//	$wc_zones = WC_Shipping_Zones::get_zones();
//
//	$args["values"] = $wc_zones;
//	$events = GetArg($args, "events", null);
//	$args["multiple"] = true;
//
//	return Core_Html::gui_select( $id, "zone_name", $wc_zones, $events, $selected, "id", "class", true );
//}

