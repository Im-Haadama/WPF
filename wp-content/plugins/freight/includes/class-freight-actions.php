<?php
/*
 * Copyright (c) 2021. Lorem ipsum dolor sit amet, consectetur adipiscing elit.
 * Morbi non lorem porttitor neque feugiat blandit. Ut vitae ipsum eget quam lacinia accumsan.
 * Etiam sed turpis ac ipsum condimentum fringilla. Maecenas magna.
 * Proin dapibus sapien vel ante. Aliquam erat volutpat. Pellentesque sagittis ligula eget metus.
 * Vestibulum commodo. Ut rhoncus gravida arcu.
 */

class Freight_Actions {

	static private $_instance;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	function init_hooks(Core_Hook_Handler $loader)
	{
//		print debug_trace(10); print "---------------------<br/>";
		$loader->AddAction("order_save_pri", $this, 'order_save_pri');
		$loader->AddAction("mission_update_type", $this, 'mission_update_type');
//		$loader->AddAction("mission_details", $this, 'mission_details');
		$loader->AddAction("freight_do_add_delivery", $this);
		$loader->AddAction('delivered', $this, "delivered_wrap");
		$loader->AddAction('download_mission',$this, 'download_mission');
		$loader->AddAction('print_mission', $this, 'print_mission');
		$loader->AddAction('order_update_driver_comment', $this, 'order_update_driver_comment');
		$loader->AddAction('order_update_field', $this);
		$loader->AddAction("freight_do_import", $this);
		$loader->AddAction("freight_do_import_baldar", $this);
		$loader->AddAction("update_switch_time", $this);

		$loader->AddAction("mission_clean", $this);
		$loader->AddAction("show_import", $this);
	}

	static function order_save_pri()
	{
		$order_id = GetParam("order_id", true);
		$site_id = GetParam("site_id", true);
		$pri = GetParam("pri", true);

		//			print info_get("mission_order_priority_" . $site_id . '_' .$order_id);
		// TEMP: Remove duplicates.
		InfoDelete("mission_order_priority_" . $site_id . '_' .$order_id);

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

	function freight_do_add_delivery() : bool
	{
		$client = GetParam("client", true);
		$fee = GetParam("fee", true);
		$mission_id = GetParam("mission_id", true);

		$customer = new Fresh_Client($client);
		$zone = $customer->getZone();
		if (! $zone) {
			print "Failed: zone not found";
			return false;
		}
		$the_shipping = null;
		foreach ($zone->get_shipping_methods(true) as $shipping_method) {
			// Take the first option.
			$the_shipping = $shipping_method;
			break;
		}
		if (! $the_shipping) {
			print "Failed: no shipping method to zone " . $zone->get_zone_name();
			return false;
		}

		$o = Finance_Order::CreateOrder( $client, $mission_id, null, $the_shipping,
			" משלוח המכולת " . date( 'Y-m-d' ) . " " . $customer->getName(), Israel_Shop::addVat($fee));
		$o->setField("legacy", 1);

		if (! $o)
			return false;
		$o->setStatus( 'wc-processing' );
//		$o->setMissionID($mission_id);

		return true;
	}

	static function delivered_wrap()
	{
		$site_id = GetParam("site_id", false, Core_Db_MultiSite::LocalSiteId());
		$type = GetParam("type", false, "orders");
		$ids = GetParamArray("id", true);

		foreach ($ids as $id)
			if (! Freight_Mission_Manager::delivered($site_id, $type, $id)) return false;
		return true;
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

	function print_mission()
	{
		$multi_site = Core_Db_MultiSite::getInstance();
		$id = GetParam("id", true);
		print Core_Html::HeaderText();
		// The route stops.
		$args = array("print" => true, "edit" => false, "sort"=>true);
		$m = Freight_Mission_Manager::get_mission_manager($id);
		print $m->dispatcher($args);

		print $multi_site->GetAll(WPF_Flavor::getPost() . "?operation=get_local_supplies_anonymous&mission_ids=$id");

//		// Supplies to collect
//		if (class_exists(('Fresh_Supplies'))) {
//			$supplies = Fresh_Supplies::mission_supplies( $id );
//			foreach ( $supplies as $supply_id ) {
//				$s = new Fresh_Supply( $supply_id );
//				print $s->Html( $args );
//			}
//		}
////		die(0);
	}

	static function order_update_driver_comment()
	{
		$order_id = GetParam("order_id", true);
		$comments = GetParam("comments", true);
//		print "$order_id \'$comments\'";
		$o = new Finance_Order($order_id);
		return $o->UpdateDriverComments($comments);
	}

	static function order_update_field()
	{
		$order_id = GetParam("order_id", true);
		$field = GetParam("field", true);
		$field_value = GetParam("field_value", true);
		$o = new Finance_Order($order_id);
		$o->UpdateField($field, urldecode($field_value));
		Freight_Mission_Manager::clean($o->getField('mission_id'));
	}

	function the_import($mission_id, $rows)
	{

	}

	function mission_clean()
	{
		$id = GetParam("id", true);

		Freight_Mission_Manager::clean($id);
	}

	function freight_do_import_wrap($mission_id) {
		if (! isset($_FILES["fileToUpload"]["tmp_name"])) {
			print "No file selected";

			return;
		}

		//		$file_name = '/var/www/html/21-1-2021.html';
//		if (! $file_name and !isset($_FILES["fileToUpload"]["tmp_name"])) {
//			$file_name = '/var/www/html/28-1-2021.csv';
//			// print "No file selected";
//			// return;
//		}

		$file_name = $_FILES["fileToUpload"]["tmp_name"];

		$importer = new Freight_Importer();
		$importer->import_csv( $file_name, $mission_id );
	}

	function freight_do_import_baldar($mission_id, $file_name = null) {
		//		$file_name = '/var/www/html/21-1-2021.html';
		if (! $file_name and !isset($_FILES["fileToUpload"]["tmp_name"])) {
			$file_name = '/var/www/html/21-1-2021.html';
			// print "No file selected";
		}

		if (! $file_name) $file_name = $_FILES["fileToUpload"]["tmp_name"];

		$importer = new Freight_Importer();
		$importer->import_baldar( $file_name, $mission_id );
	}

	function show_import()
	{
		$id = GetParam("id", true);
		$info = SqlQuerySingleAssoc("select * from im_info where id = $id");
		if (substr($info['info_key'], 0, 13) != 'import_result') die("bad key");

		print Core_Html::GuiHeader(1, "Import date: " . date('Y-m-d h:i', substr($info['info_key'], 14)));
		print $info['info_data'];
	}

	function update_switch_time($params)
	{
		InfoUpdate("freight_switching_time", $params["value"]);
	}
}
