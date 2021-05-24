<?php
/*
 * Copyright (c) 2021. Lorem ipsum dolor sit amet, consectetur adipiscing elit.
 * Morbi non lorem porttitor neque feugiat blandit. Ut vitae ipsum eget quam lacinia accumsan.
 * Etiam sed turpis ac ipsum condimentum fringilla. Maecenas magna.
 * Proin dapibus sapien vel ante. Aliquam erat volutpat. Pellentesque sagittis ligula eget metus.
 * Vestibulum commodo. Ut rhoncus gravida arcu.
 */

class Flavor_Mission_Views {
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

	static function install_woocommerce()
	{
		print Core_Html::GuiHeader(1,"Deliveries and missions are using woocommerce. Install it!");
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

	static function show_missions()
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
		$week = GetParam("week", false, first_day_of_week());
		if ($week)
			$header .= __("Missions of week:") . " " . $week;

		$result = Core_Html::GuiHeader(1, $header);

		$multi = Core_Db_MultiSite::getInstance();

//		if (! $multi->isMaster()) $result .= __("Edit missions in site "). $multi->getSiteName($multi->getMaster());

//		if ($multi->isMaster())
//			Flavor_Mission_Manager::create_missions();
//		else {
//			Flavor_Mission_Manager::update_missions_from_master();
//			Finance_Delivery_Manager::update_shipping_methods();
//		}

		$result .= self::do_show_missions($week ? " first_day_of_week(date) = '$week'" : null, $multi->isMaster());
		$result .= "<br/>" . Core_Html::GuiHyperlink("last week", AddToUrl("week", date('Y-m-d', strtotime("$week -1 week"))));

		$result = apply_filters("show_missions", $result);

		print $result;
	}
	static function do_show_missions($query = null, $edit = false)
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
			$result .= Core_Html::GuiHyperlink("[Create]", AddToUrl("operation", "gem_add_missions"));
			return $result;
		}

		$args = array();
		$args["edit"] = $edit;
//		$args["add_button"] = $edit;
		$args["add_checkbox"] = true;
		$args["post_file"] = WPF_Flavor::getPost();

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

}