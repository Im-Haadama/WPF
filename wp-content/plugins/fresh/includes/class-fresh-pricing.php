<?php


class Fresh_Pricing {

	static function calculate_price( $price, $supplier, $sale_price = '', $terms = null )
	{
		$factor = sql_query_single_scalar( "SELECT factor FROM im_suppliers WHERE id = " . $supplier);

		// Check for sale
		if ( is_numeric( $sale_price ) and $sale_price < $price and $sale_price > 0 ) $price = $sale_price;

//		print "$supplier $price $factor<br/>";
		if ( is_numeric( $factor ) ) return round( $price * ( 100 + $factor ) / 100, 1 );

		return 0;
	}

	// Fruits factor
//		if ($terms) foreach ($terms as $term){
//			if ($term->id == 11 and $price > 10 and MultiSite::LocalSiteID() == 1){
//				$factor = $factor * 0.8;
//				print "fruit factor";
//			}
//		}

	static function get_price_by_type( $prod_id, $client_type = "", $quantity = 1, $variation_id = null )
	{
		if (! ($prod_id > 0)){
			print "bad prod $prod_id<br/>";
			print debug_trace(6);
			return -1000;
		}
		static $configured = -1;
		if ($configured == -1) {
			$configured = table_exists("client_types");
		}
		if (! $configured) return get_postmeta_field( $prod_id, '_price' );
		$debug = 0;

		if ( strlen( $client_type ) < 1 ) {
			$client_type = "regular";
		}
		// client type can be:
		// null - regular price.
		// string - type name
		// number - type id
		// print "CT=" . $client_type;
		$p    = new Fresh_Product( $prod_id );
		$type = ( $p->isFresh() ) ? "rate" : "dry_rate";

		$sql = "SELECT min($type) FROM im_client_types WHERE type = '" . $client_type . "' AND (q_min <= " . $quantity . " OR q_min = 0)";
		//  print $sql . "<br/>";
		$rate = sql_query_single_scalar( $sql );

		$id = $variation_id ? $variation_id : $prod_id;

		// Nothing special. Return the price from the site.
		if ( is_null( $rate ) ) {
			return get_postmeta_field( $id, '_price' );
		}

//	 print "rate: " . $rate. "<br/>";

		$price = get_postmeta_field( $id, '_price' );

		if ($debug) print $prod_id ." " . $variation_id . "<br/>";

		$buy   = $p->getBuyPrice(  );
		if ( $buy == 0 ) {
			return $price;
		}
		//print "price: " .$price;
		// if (! $p->isFresh()) $rate
		return min( $price, round( ( $buy * ( 100 + $rate ) ) / 100, 1 ) );

		// Non fresh
//	return get_postmeta_field( $prod_id, '_price' );
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

	static function get_buy_price( $prod_id, $supplier_id = 0 ) {
		$p = new Fresh_Product($prod_id);
		if ($p->is_basket())
		{
			$buy = 0;
			$sql2 = 'SELECT DISTINCT product_id, quantity FROM im_baskets WHERE basket_id = ' . $prod_id;
			print $sql2;
			$result2 = sql_query( $sql2 );
			while ( $row2 = mysqli_fetch_assoc( $result2 ) ) {
				$prod_id = $row2["product_id"];
				$buy     += self::get_buy_price( $prod_id, $supplier_id );
			}
			return $buy;

//			foreach ($p-)
		}
		// print $supplier_id . "<br/>";
		if ( $prod_id > 0 ) {
			if ( $supplier_id > 0 ) {
//			print "supplier: " . $supplier_id . "<br/>";
				$a = Fresh_Catalog::alternatives( $prod_id );
				foreach ( $a as $s ) {
					//		print $s->getSupplierId() . "<br/>";
					if ( $s->getSupplierId() == $supplier_id ) {
						return $s->getPrice();
					}
				}
			}

			return get_postmeta_field( $prod_id, 'buy_price' );
		}

		return - 1;
	}


	static function get_regular_price( $prod_id ) {
		return get_postmeta_field( $prod_id, '_regular_price' );
	}

	static function set_regular_price( $product_id, $price ) {
		update_post_meta( $product_id, "_regular_price", $price );
		$sale_price = get_post_meta($product_id, "_sale_price");
		if ($sale_price > 0)
			update_post_meta($product_id, '_price', min($sale_price, $price));
	}

	static function set_saleprice( $product_id, $sale_price ) {
		update_post_meta( $product_id, "_sale_price", $sale_price );
		$price = get_post_meta($product_id, "_price");
		$new_price = (($sale_price > 0) ? $sale_price : $price);
		update_post_meta( $product_id, "_price", $new_price );
	}

}