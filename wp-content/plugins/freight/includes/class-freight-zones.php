<?php


class Freight_Zones {

	static function settings($args, $operation)
	{

		$result                = "";
		// print "is_master=$isMaster<br/>";
		$isMaster = Core_Db_MultiSite::getInstance()->isMaster();

		$operation = null;
		$args["post_file"] = Freight::getPost();

		if ($operation) {
			$id = GetParam( "id", false, null );
			$args["operation"] = $operation;

			$output = apply_filters( $operation, "", $id, $args, null );
			if ($output)
				return $result . $output;
		}
		$result                .= Core_Html::GuiHeader( 1, "Shipping zones" );

		$args["edit"] = true;
		$args["sql"] = "select * from wp_woocommerce_shipping_zones";
		$args["id_field"] = "zone_id";
		$args["multiple"] = false;
		$args["edit"] = $isMaster;
		$args["add_checkbox"] = false; // Don't allow delete

		$result .= Core_Gem::GemTable("woocommerce_shipping_zones", $args);
		$result .= "<br/><h></h>Add/Remove shipping zones in woocommerce page</h2>";
		return $result;
	}

}