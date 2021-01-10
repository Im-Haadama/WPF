<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class Flavor_Mission {

	static private $_instance;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( "Flavor" );
		}

		return self::$_instance;
	}

	function init_hooks($loader)
	{
		$loader->AddAction('get_local_anonymous', $this);
	}

	static function gui_select_mission( $id, $selected = 0, $args = null ) {
		$events = GetArg( $args, "events", null );

		$query = " date >= curdate() or date is null";
		if ($selected)
			$query .= " or (id = $selected)";

		$args = array(
			"events"   => $events,
			"selected" => $selected,
			"query"    => $query
		);

		return Core_Html::GuiSelectTable( $id, "missions", $args );
	}

	static function missions()
	{
		$id = GetParam("id");
		if ($operation = GetParam("operation", false, null))
		{
			Core_Hook_Handler::instance()->DoAction($operation, $id);
			return;
		}

		if ($id) {
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

	static function update_missions_from_master()
	{
		$multi = Core_Db_MultiSite::getInstance();
		if (! $multi->isMaster()) {
			$multi->UpdateFromRemote( "missions", "id", 0, "date >= curdate()" );
		}
//			$url = Flavor::getPost() . "?operation=sync_data_missions";
////			print $multi->getSiteURL($multi->getMaster()) . $url;
//
//			$html = $multi->Execute( $url, $multi->getMaster() );
//
//			if (! $html) print "Can't get data from master<br/>";
//
//			if ( strlen( $html ) > 100 ) {
//				//printbr($html);
//				$multi->UpdateTable( $html, "missions", "id" );
//			} else {
//				print "short response. Operation aborted <br/>";
//				print "url = $url";
//				print $html;
//
//				return;
//			}
//
//			dd("he?");
//			$multi->UpdateFromRemote( "missions", "id", 0, "date >= curdate()" );
//		}
	}
	static function show_missions($query = null, $edit = false)
	{
		if (! $query)
			$query = "date >= '" . date('Y-m-d', strtotime('last sunday') ). "'";

		$result = "";

		$sql = "select id from im_missions where " . $query; // FIRST_DAY_OF_WEEK(date) = " . quote_text($week);

		$missions = SqlQueryArrayScalar($sql);

		if ( count( $missions )  == 0) {
			$result .= ETranslate("No missions for given period");
			$result .= Core_Html::GuiHyperlink("Last week", AddToUrl("week" , date( "Y-m-d", strtotime( "last sunday" )))) . " ";
			$result .= Core_Html::GuiHyperlink("This week", AddToUrl("week" , date( "Y-m-d", strtotime( "sunday" )))) . " ";
			$result .= Core_Html::GuiHyperlink("Next week", AddToUrl("week", date( "Y-m-d", strtotime( "next sunday" ))));
			return $result;
		}

		$args = array();
		$args["edit"] = $edit;
		$args["add_checkbox"] = true;
		$args["post_file"] = Flavor::getPost();

		$sql = "select id, date, name, mission_type from im_missions where id in (" . CommaImplode($missions) . ") order by date";

		$args["links"] = array("id" => AddToUrl( "id", "%s"));

		$args["sql"] = $sql;
		$args["hide_cols"] = array("zones_times"=>1);
//		$args["class"] = "sortable";
//		$args["selectors"] = array("mission_type" => __CLASS__ . "::gui_select_mission_type");
		$args["actions"] = apply_filters("mission_actions", array());
		$result .= Core_Gem::GemTable("missions", $args);

		return $result;
	}

	static function get_local_anonymous()
	{
		FinanceLog(__FUNCTION__);
		$mission_ids = GetParam("mission_ids", true);
		$header = GetParam("header", false, false);
		print self::GetLocalMissions($mission_ids, $header);
		return true;
	}

	static function GetLocalMissions($mission_ids, $header = true)
	{
		$data = "";

//		FinanceLog(__FUNCTION__);
		if ($header) print self::delivery_table_header();
		if (! is_array($mission_ids)) $mission_ids = array($mission_ids);

		foreach ( $mission_ids as $mission_id ) {
			if ( $mission_id ) {
				if (class_exists('Freight_Mission_Manager'))
					$data .= Freight_Mission_Manager::print_deliveries( $mission_id, false);

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

	static function create_missions()
	{
		$types = SqlQueryArrayScalar("select id from im_mission_types");
		foreach ($types as $type) {
			Mission::CreateFromType($type);
		}
		return true;
	}

	static function mission($id)
	{
		if (GetParam("operation", false) == "dispatch") {
			print "doing";
			do_action("mission_dispatch", $id);
			print "done";
			return;
		}

		$args = array("post_file" => Flavor::getPost());
		$result = Core_Html::GuiHeader(1, "Mission $id");
		$result .= Core_Gem::GemElement("missions", $id, $args);
		return $result;
	}

	static function install_woocommerce()
	{
		print Core_Html::GuiHeader(1,"Deliveries and missions are using woocommerce. Install it!");
	}

}