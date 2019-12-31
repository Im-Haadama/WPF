<?php


class Fresh_Control {
	static public function handle($att)
	{
		$operation = get_param("operation", false, "control_report");

		switch ($operation)
		{
			case "control_report":
				$missions =  sql_query_array_scalar("select id from im_missions where date = " . quote_text(date('Y-m-d')));
				if (count($missions) == 1) {
					print self::control_mission($missions[0]);
					return;
				}
		}
	}

	static function control_mission($mission_id)
	{
		$sql = "select post_id from wp_postmeta where meta_key = 'mission_id' and meta_value = $mission_id";
		$orders = sql_query_array_scalar($sql);

		$result = "<style>" .
		"@media print {" .
		"h1 {page-break-before: always;}" .
		"}" .
		          "</style>";
		$result .= '<div class="row">';
		$count = 0;
		$cols = [];
		$col_number = 3;
		for($i = 0; $i < $col_number; $i ++)
			$cols[$i] = '<div class ="column">';
//		$cols[1] = '<div class ="column">';

		foreach ($orders as $order_id) {
			$o       = new Fresh_Order( $order_id );
			$result .= $o->checkInfoBox( true );

			$d      = Fresh_Delivery::CreateFromOrder( $order_id );
			$rows   = sql_query_array( "select product_name, quantity_ordered, quantity from im_delivery_lines where delivery_id = " . $d->getID() );
//			print "row count: " . count($rows) ."<br/>";
			$result .= "<table>";
			$result .= gui_row( array(
				"Product name",
				"Ordered",
				"Delivered",
				"Product name",
				"Ordered",
				"Delivered"
			) );

			for ( $i = 0; $i < count( $rows ); $i += $col_number ) {
				$row = [];
				for ( $j = 0; $j < $col_number; $j ++ ) {
					if ( $i + $j < count( $rows ) ) {
						$row[ $j * 3 ]     = $rows[ $i + $j ][0]; // name
						$row[ $j * 3 + 1 ] = $rows[ $i + $j][1]; // ordreded
						$row[ $j * 3 + 2 ] = $rows[ $i + $j][2]; // supplied
					}
				}
				$result .= gui_row($row);
			}
			$result .= "</table>";
//			for ( $i = 0; $i < $col_number; $i ++ ) {
//				$cols[ $i ] .= "</div>";
//				$result     .= $cols[ $i ];
//			}
		}

//			array_unshift($rows, array("Product name", "Ordered", "Delivered", "Product name", "Ordered", "Delivered"));
////1			$current .= gui_table_args($rows, "delivery_" . $d->getUserId(), $args);
////			$current .= $d->delivery_text(ImDocumentType::delivery, ImDocumentOperation::check );
//
////			print "CCC=" . $count % $col_number ."<br/>";
//			$cols[$count % $col_number] .= $current;
//			$count ++;

//		for($i = 0; $i < $col_number; $i ++){
//			$cols[$i] .= "</div>";
//			$result .= $cols[$i];
//		}
		$result .= '</div>';
//		$cols[1] .= "</div>";


		print $result;
	}

}