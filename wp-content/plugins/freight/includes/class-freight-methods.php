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
		add_action("show_mission", __CLASS__ . "::show_mission_wrap");
		add_action("toggle_shipment_enable", __CLASS__ . "::toggle_shipment_enable");

		$file = FLAVOR_INCLUDES_URL . 'js/sorttable.js';
		wp_enqueue_script( 'sorttable', $file, null, '1.0', false );

	}

	static function settings($args = null, $operation = null) {
		$result = Core_Html::GuiHeader(1, "Shipping methods");

		$header_row = array("id", "Zone name", "Shipping name");
		for ($day = 1; $day <=4; $day ++)
			array_push($header_row, DayName($day));

		$rows = array($header_row);
		$sql = "select * from wp_woocommerce_shipping_zone_methods order by zone_id";
		$query_result = sql_query($sql);
		// Zone name, instance name, workdays
		while ($method_info = sql_fetch_assoc($query_result))
		{
			$instance_id = $method_info['instance_id'];
			$data = get_wp_option("woocommerce_flat_rate_{$instance_id}_settings");
			$zone_id = $method_info['zone_id'];
			$zone_info = sql_query_single("select * from wp_woocommerce_shipping_zones where zone_id = $zone_id");
			$new_row = array(
				isset($data['instance_id']) ? $data['instance_id'] : 'no instance',
				$zone_info[1],
				$data['title']
			);
			$week_day = $method_info['week_day'];

			$enabled = $method_info['is_enabled'];
			for ($day = 1; $day <=4; $day ++) {
				if (! $week_day and strstr($data['title'], DayName($day))) $week_day = $day;
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
		$result .= Core_Html::gui_table_args($rows, "shipment_methos", $args);
//		$result .= Core_Gem::GemTable("woocommerce_shipping_zone_methods", $args);
		return $result;
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

	static function shipment_delete()
	{
		$id = GetParam("instance", true);
		$s = new Freight_Shipment($id);
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

	static function toggle_shipment_enable()
	{
		$enable = GetParam("enable", true);
		$instance = GetParam("instance", true);
		return sql_query("update wp_woocommerce_shipping_zone_methods set is_enabled = $enable where instance_id = $instance");
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

//function gui_select_method_name( $id, $instance_id, $args = null)
//{
//	$data = get_wp_option("woocommerce_flat_rate_{$instance_id}_settings");
//	return ($data? $data['title'] : 'not set');
//}