<?php


class Freight_Zones {

	static function settings()
	{
		$operation = null;
		$result                = "";
		$args["post_file"] = Freight::getPost();

		if ($operation) {
			$id = GetParam( "id", false, null );
			$args["operation"] = $operation;

			$output = apply_filters( $operation, "", $id, $args, null );
			if ($output)
				return $result . $output;
		}
		$result                .= Core_Html::gui_header( 1, "Shipping zones" );

		$args["edit"] = true;
//		$args["selectors"]     = array("zones" => "GuiSelectZones"
////			"week_days" => "Core_Html::gui_select_days"
//		);
//		$args["id_field"]      = "id";
//		$args["links"]         = array( "id" => AddToUrl( array( "operation" => "show_path", "path_id" => "%s" ) ) );
//		$args["add_checkbox"]  = true;
//		$args["header_fields"] = array( "checkbox"    => "select",
//		                                "id"          => "Id",
//		                                "path_code"   => "Path code",
//		                                "description" => "Description",
//		                                "zones_times" => "Zones",
//		                                "week_days"   => "Week days",
//		);
//		$args["class"] = "widefat";
////		$args["events"] = 'onchange="changed(this)"';
//
////		$paths_data   = Core_Data::TableData( "select * from ${db_prefix}paths", $args );
//////		$args["edit"] = false;
////		if ( $paths_data ) {
////			foreach ( $paths_data as $path_id => &$path_info ) {
////				if ( $path_id == "header" ) {
////					continue;
////				}
////				//$path_info['zones_times'] = path_get_zones( $path_id, $args );
////			}
////		}
//
//		$result .= Core_Gem::GemTable("paths", $args);
////		$result .= Core_Html::GuiButton("btn_save", "save", array("action" => "save_paths()"));
//
//
//		$result .= "<br/>";
//		$result .= Core_Html::GuiButton("btn_instance", "Create Missions", array("action" => "create_missions('" . Freight::getPost() . "')"));
//
//		$result .= Core_Html::gui_header(2, "Coming missions");


//		$result .= "<br/>";
//		$result .= Core_Html::GuiHyperlink("עדכון שיטות משלוח", AddToUrl("operation", "update_shipping_methods"));
		$args["sql"] = "select * from wp_woocommerce_shipping_zones";
		$args["id_field"] = "zone_id";
		$args["multiple"] = false;

		$result .= Core_Gem::GemTable("woocommerce_shipping_zones", $args);
		return $result;

	}
}