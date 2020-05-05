<?php


class Freight_Methods {

	private $version = '1.0';
	/**
	 * Freight_Zones constructor.
	 */
	static public function init() {
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
		add_action("shipment_delete", __CLASS__ . "::shipment_delete");

		add_action("update_zone_missions", __CLASS__ . "::update_zones_missions");
//		add_action("show_mission", __CLASS__ . "::show_mission_wrap");
		add_action("toggle_shipment_enable", __CLASS__ . "::toggle_shipment_enable");
		add_action("shipment_update_mc", __CLASS__ . "::shipment_update_mc");

		$file = FLAVOR_INCLUDES_URL . 'js/sorttable.js';
		wp_enqueue_script( 'sorttable', $file, null, '1.0', false );

		Core_gem::AddTable("mission_types");
	}

	static function settings($args = null, $operation = null)
	{
		$result = Core_Html::GuiHeader(1, "Shipping methods");

		if ($operation)
			$result = apply_filters( $operation, $result, "", null, null );

		$header_row = array("id", "Zone name", "Shipping name", "mission code");
		for ($day = 1; $day <=4; $day ++)
			array_push($header_row, DayName($day));

		$table_name = "wp_woocommerce_shipping_zone_methods";

		$rows = array($header_row);
		$sql = "select * from $table_name order by zone_id";
		$query_result = SqlQuery($sql);
		// Zone name, instance name, workdays
		while ($method_info = SqlFetchAssoc($query_result))
		{
			$instance_id = $method_info['instance_id'];
			$data = get_wp_option("woocommerce_flat_rate_{$instance_id}_settings");
			$zone_id = $method_info['zone_id'];
			$zone_info = SqlQuerySingle("select * from wp_woocommerce_shipping_zones where zone_id = $zone_id");
			$args["events"] = sprintf('onchange="shipment_update_mc(\'%s\', \'%d\')"', Fresh::getPost(), $instance_id);

			$new_row = array(
				isset($data['instance_id']) ? $data['instance_id'] : 'no instance',
				isset($zone_info[1]) ? $zone_info[1] : "not set",
				$data['title'],
				self::SelectMissionType("mis_" . $instance_id, $method_info['mission_code'], $args)
			);
			$week_day = $method_info['week_day'];

			$enabled = $method_info['is_enabled'];
			for ($day = 1; $day <=4; $day ++) {
				if (! $week_day and strstr($data['title'], DayName($day))) {
					$week_day = $day;
					SqlQuery("update wp_woocommerce_shipping_zone_methods set week_day = $week_day where instance_id = $instance_id");
				}
				if ($week_day == $day)
					array_push( $new_row, Core_Html::GuiCheckbox( "chk_shipment_$instance_id", $enabled,
						array( "events" => ("onchange=\"toggle_shipment_enable('". Freight::getPost() . "', $instance_id)\"") ) ) );
				else
					array_push($new_row, "");
			}

			if ($instance_id) array_push($new_row, Core_Html::GuiButton("btn_delete_$instance_id", "X", array("action"=>"shipment_delete('".Freight::getPost()."', $instance_id)")));
			array_push($rows, $new_row);
		}

		$args["class"] = "sortable";
		$result .= Core_Html::gui_table_args($rows, "shipment_methods", $args);
		$result .= Core_Html::GuiHyperlink("Update!", AddToUrl("operation", "update_shipping_methods"));
//		$result .= Core_Gem::GemTable("woocommerce_shipping_zone_methods", $args);
		return $result;
	}

	static function SelectMissionType($id, $selected, $args)
	{
		$args["name"] = 'mission_name';
		$args["selected"] = $selected;
		return Core_Html::GuiSelectTable($id, "mission_types", $args);
	}

	static function shipment_delete()
	{
		$id = GetParam("instance", true);
		$s = new Freight_Shipment($id);
		$s->delete_instance();
	}

	static function toggle_shipment_enable()
	{
		$enable = GetParam("enable", true);
		$instance = GetParam("instance", true);
		return SqlQuery("update wp_woocommerce_shipping_zone_methods set is_enabled = $enable where instance_id = $instance");
	}

	function update_zone_missions()
	{
		$result = "Updating...<br/>";

		return $result;
	}

	static function shipment_update_mc()
	{
		$instance = GetParam("instance", true);
		$mc = GetParam("mc", true);
		return SqlQuery( "update wp_woocommerce_shipping_zone_methods set mission_code = " . QuoteText($mc) . " where instance_id = $instance");
	}

	static function gui_select_path( $id, $selected = 0, $args = null )
	{
//	return gui_select_table( $id, "im_missions", $selected, $events, null,
//		"path_code", "where date > CURDATE()", true, false, null, "path_code" );
		$args["selected"] = $selected;
		$args["name"] = "description";
		return Core_Html::GuiSelectTable($id, "paths", $args);
	}

	static function mission_types($args, $operation)
	{
		$result = "";
		$args["post_file"] = Fresh::getPost();
		$args["operation"] = $operation;
		$args["edit"] = true;
		if ($operation)
			$result = apply_filters( $operation, $result, "", $args, null );

		$result .= Core_Gem::GemTable("mission_types", $args);
		return $result;
	}
}


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
	return SqlQuerySingleScalar( "SELECT zone_name FROM wp_woocommerce_shipping_zones WHERE zone_id = " . $id );
}

function path_save_times($path_id, $params)
{
	$path_times = array();
	for ($i = 0; $i < count($params); $i += 2) {
		$zone_id = $params[$i];
		$times = $params[$i + 1];
		$path_times[$zone_id] = $times;
	}
	$sql = "update im_paths set zones_times = " . QuoteText(EscapeString(serialize($path_times))) . ' where id = ' . $path_id ;
	return SqlQuery($sql);
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

//function gui_select_method_name( $id, $instance_id, $args = null)
//{
//	$data = get_wp_option("woocommerce_flat_rate_{$instance_id}_settings");
//	return ($data? $data['title'] : 'not set');
//}