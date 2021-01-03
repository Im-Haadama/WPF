<?php


class Fresh_Pricing {

	static function calculate_price( $price, $supplier, $sale_price = '', $terms = null )
	{
		$factor = SqlQuerySingleScalar( "SELECT factor FROM im_suppliers WHERE id = " . $supplier);

		// Check for sale
		if ( is_numeric( $sale_price ) and $sale_price < $price and $sale_price > 0 ) $price = $sale_price;

//		print "$supplier $price $factor<br/>";

		//		Legacy function:
		// if ( is_numeric( $factor ) ) return round( $price * ( 100 + $factor ) / 100, 1 );

		// New function:
		if ( is_numeric( $factor ) ) return ceil($price * ( 100 + $factor ) / 100 );

		return 0;
	}

	// Fruits factor
//		if ($terms) foreach ($terms as $term){
//			if ($term->id == 11 and $price > 10 and MultiSite::LocalSiteID() == 1){
//				$factor = $factor * 0.8;
//				print "fruit factor";
//			}
//		}

	static function get_price_by_type( $prod_id, $client_type_id = 0, $quantity = 1, $variation_id = null )
	{
		$debug = 0;
		if ($debug) MyLog(__FUNCTION__ . " $prod_id $client_type_id");
		if (!TableExists("client_rates")) return get_postmeta_field( $prod_id, '_price' );
		if (! ($prod_id > 0)){
			print "bad prod $prod_id<br/>";
			print debug_trace(6);
			return -1000;
		}
		static $configured = -1;
		if ($configured == -1) {
			$configured = TableExists("client_types");
		}
		$p    = new Fresh_Product( $prod_id );

		if (! $configured or $p->is_basket() or $p->is_bundle()) return get_postmeta_field( $prod_id, '_price' );

		if ( strlen( $client_type_id ) < 1 ) {
			$client_type = "regular";
		}
		// client type can be:
		// null - regular price.
		// string - type name
		// number - type id
		// print "CT=" . $client_type;
//		if ($p->is_bundle()) return $p->getPrice();
		$product_type = ( $p->isFresh() ) ? "rate" : "dry_rate";

		$sql = "SELECT min($product_type) FROM im_client_rates WHERE type = '" . $client_type_id . "' AND (q_min <= " . $quantity . " OR q_min = 0) and is_group = 0";
//		MyLog($sql);
		$rate = SqlQuerySingleScalar( $sql );
		if (null != $rate) {
			if ( $debug ) {
				MyLog( "regular prices" );
			}

			if ( $debug ) {
				MyLog( "rate = $rate" );
			}
			if ( $rate >= 0 ) {
				$buy = $p->getBuyPrice();
				if ( $buy ) {
					MyLog( "buy : $buy rate: $rate" );

					return round( ( $buy * ( 100 + $rate ) ) / 100, 1 );
				}
			}
		}

		$id = $variation_id ? $variation_id : $prod_id;

		// Nothing special. Return the price from the site.
		return get_postmeta_field( $id, '_price' );
	}

	static function get_price( $prod_id) {
		return self::get_price_by_type( $prod_id );
	}

//		switch ( $client_type ) {
//			case 0:
//				if ( $quantity >= 8 ) {
//					return round( self::get_buy_price( $prod_id ) * 1.4, 1 );
//				}
//
//				return get_postmeta_field( $prod_id, '_price' );
//			case 1:
//				return siton_price( $prod_id );
//			case 2:
//				return self::get_buy_price( $prod_id );
//			case 5:
//				return min( self::get_price( $prod_id ), round( self::get_buy_price( $prod_id ) * 1.3, 1 ) );
//		}
//	}

	static function get_buy_price( $prod_id, $supplier_id = 0 )
	{
		$debug_product = null;
		if (! ($prod_id > 0)) return 0;
		$p = new Fresh_Product($prod_id);

		// If it's a basket, sum basket items.
		if ($p->is_basket())
		{
			$buy = 0;
			$sql2 = 'SELECT DISTINCT product_id, quantity FROM im_baskets WHERE basket_id = ' . $prod_id;
//			print $sql2 . "<br/>";
			$result2 = SqlQuery( $sql2 );
			while ( $row2 = mysqli_fetch_assoc( $result2 ) ) {
				$prod_id = $row2["product_id"];
				if ($prod_id == $p->getId()) continue;
				$buy     += self::get_buy_price( $prod_id, $supplier_id );
			}
			return $buy;
		}

		// If supplier know get price thru mapping.
		if ( $supplier_id > 0 ) {
			$sql = "select pricelist_id from im_supplier_mapping
			where product_id = $prod_id and supplier_id = $supplier_id";
//			print $sql . "<br/>";
			$pl_id = SqlQuerySingleScalar( $sql );
			if ( $pl_id ) {
				$pl_item = new Fresh_Pricelist_Item( $pl_id );

				return $pl_item->getPrice();
			}
		}
		// Try thru mapping
		$a = Fresh_Catalog::best_alternative( $prod_id );
		if ($prod_id == $debug_product) var_dump($a);
		if ($a)
			return $a->getPrice();

		$p = get_postmeta_field( $prod_id, 'buy_price' );
		if ($p > 0) return $p;

		return 0;
	}


	static function get_regular_price( $prod_id ) {
		return get_postmeta_field( $prod_id, '_regular_price' );
	}

	static function set_regular_price( $product_id, $price ) {
		MyLog("setiing price for $product_id $price " . get_user_id());
		update_post_meta( $product_id, "_regular_price", $price );
		$sale_price = get_post_meta($product_id, "_sale_price");
		if ($sale_price > 0)
			update_post_meta($product_id, '_price', min($sale_price, $price));
	}

	static function set_saleprice( $product_id, $sale_price ) {
		update_post_meta( $product_id, "_sale_price", $sale_price );
		$regular_price = get_post_meta($product_id, "_regular_price", true);
		$new_price = (($sale_price > 0) ? $sale_price : $regular_price);
		update_post_meta( $product_id, "_price", $new_price );
		MyLog("setiing sale price for $product_id sp=$sale_price pr=$regular_price (user: " . get_user_id() . ")");

	}
}

function my_custom_show_sale_price_at_cart( $old_display, $cart_item, $cart_item_key ) {

	/** @var WC_Product $product */
	$product = $cart_item['data'];

	if ( $product ) {
		return $product->get_price_html();
	}

	return $old_display;

}
add_filter( 'woocommerce_cart_item_price', 'my_custom_show_sale_price_at_cart', 10, 3 );