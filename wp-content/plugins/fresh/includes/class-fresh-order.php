<?php


class Fresh_Order extends Finance_Order {

	public function removeFromBasket($item_id, $prod_id)
	{
		$current_removal = self::basketRemoved($item_id);

		if (! in_array($prod_id, $current_removal))
			array_push($current_removal, $prod_id);

		Fresh_Packing::set_order_itemmeta($item_id, 'basket_removed', serialize($current_removal));
		self::update_basket_comment($item_id);
	}

	public function addToBasket($item_id, $prod_id)
	{
		$current_added = self::basketAdded($item_id);

		if (! in_array($prod_id, $current_added))
			array_push($current_added, $prod_id);

		Fresh_Packing::set_order_itemmeta($item_id, 'basket_added', serialize($current_added));
		self::update_basket_comment($item_id);
	}

	public function update_basket_comment($item_id)
	{
		$comment = "";
		$current_removal = self::basketRemoved($item_id);
		if ($current_removal) {
			$comment .= "הוסרו: ";
			foreach ( $current_removal as $prod ) {
				$p       = new Fresh_Product( $prod );
				$comment .= $p->getName() . ", ";
			}
			$comment = trim( $comment, ", " ) . ".<br/>";
		}

		$current_added = self::basketAdded($item_id);
		if ($current_added) {
			$comment .= "הוספו: ";
			foreach ( $current_added as $prod ) {
				$p       = new Fresh_Product( $prod );
				$comment .= $p->getName() . ", ";
			}
			$comment = trim( $comment, ", " ) . ". ";
		}

		Fresh_Packing::set_order_itemmeta($item_id, "product_comment", EscapeString($comment));
	}

	static function basketRemoved($item_id)
	{
		$current_removal = unserialize(Fresh_Packing::get_order_itemmeta($item_id, "basket_removed"));
		if (! $current_removal) $current_removal = array();
		return $current_removal;
	}

	static function basketAdded($item_id)
	{
		$current_addon = unserialize(Fresh_Packing::get_order_itemmeta($item_id, "basket_added"));
		if (! $current_addon) $current_addon = array();
		return $current_addon;
	}

	public function SuppliersOnTheGo()
	{
		$needed = array();
		$suppliers = null;
		$this->CalculateNeeded( $needed, $this->getCustomerId() );
		foreach ( $needed as $prod_id => $p ) {
			$P = new Fresh_Product($prod_id);
			if ($s = $P->PendingSupplies()){
//				print "s:"; var_dump($s); print "<br/>";
				if (!$suppliers) $suppliers = array();
				foreach ($s as $supplies){
					if ($supplies[3] and ! $supplies[4] and ! in_array($supplies[2], $suppliers)){ // Self collect and not picked
//						print "order " . $this->order_id . " supplier " . get_supplier_name($supplies[2]) . "<br/>";
						array_push($suppliers, $supplies[2]);
					}
				}
			}
		}
		return $suppliers;
	}

	function GetMissionId( $debug = false )
	{
		if ( ! is_numeric( $this->order_id ) ) {
			print "Bad order id: $this->order_id<br/>";
			die( 1 );
		}
		$mission = get_post_meta( $this->order_id, 'mission_id', true );
		if ( $debug ) {
			var_dump( $mission );
			print "<br/>";
		}
		if ( is_array( $mission ) ) {
			$mission_id = $mission[0];
		} else {
			$mission_id = $mission;
		}
		if ( ! is_numeric( $mission_id ) ) {
			return 0;
		}

		return $mission_id;
	}


}