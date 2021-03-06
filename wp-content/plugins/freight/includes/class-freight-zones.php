<?php


class Freight_Zones {

	static function settings($args, $operation)
	{
		$isMaster = Core_Db_MultiSite::getInstance()->isMaster();

		$result                = "";
		// print "is_master=$isMaster<br/>";

		if (! $isMaster) {
//			$result .= "Updating from master";
			Finance_Delivery_Manager::sync_from_master();
		}

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

		$result .= Core_Gem::GemTable("woocommerce_shipping_zones", $args);
		return $result;
	}

}