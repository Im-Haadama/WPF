<?php


class Freight_Zones {

	static function settings($args, $operation)
	{
		$isMaster = Core_Db_MultiSite::getInstance()->isMaster();
		// print "is_master=$isMaster<br/>";

		if (! $isMaster) {
			$m = Core_Db_MultiSite::getInstance();
			$m->UpdateFromRemote("woocommerce_shipping_zones", "zone_id");
		}

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
		$args["sql"] = "select * from wp_woocommerce_shipping_zones";
		$args["id_field"] = "zone_id";
		$args["multiple"] = false;
		$args["edit"] = $isMaster;

		$result .= Core_Gem::GemTable("woocommerce_shipping_zones", $args);
		return $result;

	}
}