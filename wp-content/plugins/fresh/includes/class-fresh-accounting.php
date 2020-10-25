<?php


class Fresh_Accounting {
	static function weekly_report( $week ) {
		$report = "";
		$report .= Core_Html::GuiHeader( 1, "מציג תוצאות לשבוע המתחיל ביום " . $week );
// $report .= date('Y-m-d', strtotime($week . " -1 week")) . "<br/>";
//		if ( date( 'Y-m-d' ) > date( 'Y-m-d', strtotime( $week . "+1 week" ) ) ) {
//			$report .=  Core_Html::GuiHyperlink( "שבוע הבא", "report.php?week=" . date( 'Y-m-d', strtotime( $week . " +1 week" ) ) ) . " ";
//		}
//
//		$report .=  Core_Html::GuiHyperlink( "שבוע קודם", "report.php?week=" . date( 'Y-m-d', strtotime( $week . " -1 week" ) ) );

		$report .= Core_Html::GuiHeader(2, "הכנסות");

		$sql = "SELECT ref, date, amount, delivery_fee as 'delivery fee', client_from_delivery(ref) as client,
		delivery_receipt(ref) AS קבלה
		FROM im_business_info WHERE " .
		       " is_active = 1 AND week = '" . $week . "' AND amount > 0 ORDER BY 1";

		$sums_in = array(  "ref" => "סה\"כ",
		                   "date" => '',
		                   "amount" => array( 0, 'SumNumbers' ),
		                   'delivery fee' => array(0, 'SumNumbers'),
			'client' => '',
			'קבלה' => '',
			               'due_vat' => array(0, 'SumNumbers'),
			'fresh' => array(0, 'SumNumbers'));
		$in_args = array("links" => array("ref" => "../delivery/get-delivery.php?id=%s"),
		                 "accumulation_row" => &$sums_in,
		                 "id_field" => "ref");
		$rows_data = Core_Data::TableData( $sql, $in_args);
		$due_vat = 0;

		// Add due vat, fresh total
		foreach ($rows_data as $key => $row)
		{
			if ($key == 'header') {
				$rows_data[ $key ]['due_vat'] = 'מוצרים חייבי מע"מ';// ;;__("Vat");
				$rows_data[$key] ['fresh'] = 'סה"כ מוצרים טריים';
			}
			else {
				$delivery = new Fresh_Delivery($key);
				$rows_data[ $key ]['due_vat'] = $delivery->getDeliveryDueVat() - $delivery->DeliveryFee();
				$due_vat +=$rows_data[ $key ]['due_vat'];
				$rows_data[$key]['fresh'] = $rows_data[ $key ]['amount'] - $rows_data[ $key ]['due_vat'];
			}
		}

//		$report .= Core_Html::GuiTableContent("business", $sql, $in_args);
//			 Core_Html::gui_table_args("table", $sql, $in_args);

		$report .= Core_Html::gui_table_args( $rows_data, "table", $in_args );

		$report .= Core_Html::GuiHeader(2, "מוצרים יבשים");

//		$report .= 'סה"כ נמכרו (כולל מע"מ) ' . $due_vat . "<br/>";
		$report .= ' רווח מחושב ' . round($due_vat / 1.17 - $due_vat / 1.4, 0) . "<br/>";

		$report .= Core_Html::GuiHeader(2, "הוצאות");

		$sql = "SELECT supply_from_business(id) as supply_id, id, ref, date, amount, " .
		       "supplier_from_business(id), pay_date" .
		       " FROM im_business_info WHERE " .
		       " week = '" . $week . "' AND is_active = 1 AND amount < 0 " .
		       " and document_type = 5 " .
		       " ORDER BY 3 DESC";

		$sums_supplies = array( "סה\"כ", "", "", "", "amount" => array( 0, 'SumNumbers' ), "", "" );
		$supplies_args = array("links" => array("supply_id" => Fresh_Supply::getLink('%s')), "accumulation_row" => &$sums_supplies);

		$report .= Core_Html::GuiTableContent("table", $sql, $supplies_args);

//		$salary_text = ImMultiSite::sExecute( "people/report-trans.php?week=" . $week . "&project=3", 1 );
//
//		$dom         = im_str_get_html( $salary_text );
//		$row         = "";
//		foreach ( $dom->find( 'tr' ) as $row ) {
//			;
//		}
//		$salary_fruity = - (int) $row->find( 'td', 11 )->plaintext;
//		$travel        = - (int) $row->find( 'td', 13 )->plaintext;
//		$extra         = - (int) $row->find( 'td', 12 )->plaintext;

//		$salary_text .= sExecute( "people/report-trans.php?week=" . $week . "&project=11", 1 );
//		$dom         = im_str_get_html( $salary_text );
//		$row         = "";
//		foreach ( $dom->find( 'tr' ) as $row ) {
//			;
//		}
//		$salary_delivery = - (int) $row->find( 'td', 11 )->plaintext;
//		$travel          -= (int) $row->find( 'td', 13 )->plaintext;
//		$extra           -= (int) $row->find( 'td', 12 )->plaintext;
//
//		$report .= Core_Html::GuiHeader( 1, "סיכום" );
//		$total_sums = array( "סיכום", array( 0, 'sum_numbers' ) );
//		$report .= gui_table( array(
//			array( "סעיף", "סכום" ),
//			array( "תוצרת פרוטי", $sums_in['amount'][0] ),
//			array( "דמי משלוח פרוטי", $sums_in['delivery fee'][0] ),
//			array( "גלם", $sums_supplies[4][0] ),
//			array( "שכר אריזה", $salary_fruity ),
//			array( "שכר משלוחים", $salary_delivery ),
//			array( "הוצ' נסיעה", $travel ),
//			array( "הוצ עובדים נוספות", $extra)
//		), "totals", true, true, $total_sums );
//
//		$report .= Core_Html::GuiHeader( 2, "הכנסות" );
//		$report .= $inputs;
//
//		$report .= Core_Html::GuiHeader( 2, "אספקות" );
//		$report .= $outputs;
//
//		$report .= Core_Html::GuiHeader( 2, "שכר" );
//		$report .= $salary_text;
		return $report;
	}

	static function weekly_product_report( $week, $sort = 4 ) {
		$result = "";
		$result .=Core_Html::GuiHeader( 1, "מציג תוצאות לשבוע המתחיל ביום " . $week );

		$result .= "<br/>";

		$sql = "SELECT product_name, round(sum(quantity), 1), max(prod_id), product_name FROM im_delivery_lines " .
		       " WHERE delivery_id IN (SELECT id FROM im_delivery WHERE first_day_of_week(date) = '" . $week . "')" .
		       " GROUP BY prod_id order by " . $sort;

		// $result .= $sql;
		// $result .= $sql;1
		$sql_result = SqlQuery( $sql );

		$lines = array();
		while ( $row = mysqli_fetch_row( $sql_result ) ) {
			$quantity = $row[1];
			if ( ! ( $quantity > 0 ) ) {
				continue;
			}
			$prod_id   = $row[2];
			$prod_name = $row[0];
			$suppliers = self::archive_get_supplier( $prod_id, $week );
			$q         =Core_Html::GuiHyperlink( $quantity, "report.php?prod_id=" . $prod_id . "&week=" . $week );
			array_push( $lines, array( $prod_id, $suppliers, $prod_name, $q ) );
		}

		// sort( $lines );

		$actual_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

		array_unshift( $lines, array(
			"מזהה מוצר",
			"ספקים",
			"שם מוצר",
			Core_Html::GuiHyperlink( "כמות", $actual_link . "&sort=2d" )
		) );

//		$result .= Core_Html::gui_table_args( $lines );

		return $result;

//	$sql = "SELECT ref as 'תעודת משלוח', date AS תאריך, amount AS סכום, delivery_fee AS 'דמי משלוח', client_from_delivery(ref) AS לקוח FROM im_business_info WHERE " .
//	       " week = '" . $week . "' AND amount > 0 ORDER BY 1";
//
//	$sums_in = array( 0, 0, array( 0, sums ), array( 0, sums ), 0 );
//	$inputs  = table_content( $sql, true, true, array( "../delivery/get-delivery.php?id=%s" ) , $sums_in );
//
//	$sql = "SELECT ref as 'תעודת משלוח', date, amount AS סכום, supplier_from_business(id) AS ספק FROM im_business_info WHERE " .
//	       " week = '" . $week . "' AND is_active = 1 AND amount < 0 ORDER BY 3 DESC";
//
//	$sums_supplies = array( 0, 0, array( 0, sums ), 0, 0 );
//	$outputs       = table_content( $sql, true, true, null, $sums_supplies );
//
//	$salary      = 0;
//	$salary_text = MultiSite::Execute("people/report-trans.php?week=" . $week . "&project=3", 1);
//	$salary      = - $salary;
//
//
//	printCore_Html::GuiHeader( 1, "סיכום" );
//	$total_sums = array( "סיכום", array( 0, sums ) );
//	print gui_table( array(
//		array( "סעיף", "סכום" ),
//		array( "תוצרת", $sums_in[2][0] ),
//		array( "דמי משלוח", $sums_in[3][0] ),
//		array( "גלם", $sums_supplies[2][0] ),
//		array( "שכר", $salary )
//	), "totals", true, true, $total_sums );
//
//	printCore_Html::GuiHeader( 2, "הכנסות" );
//	print $inputs;
//
//	printCore_Html::GuiHeader( 2, "הוצאות" );
//	print $outputs;
//
//	printCore_Html::GuiHeader( 2, "שכר" );
//	print $salary_text;
	}

	static function archive_get_supplier( $prod_id, $week ) {
		$sql = "SELECT DISTINCT s.supplier
	FROM im_supplies s
	JOIN im_supplies_lines l
	WHERE l.supply_id = s.id
		AND first_day_of_week(date) = '" . $week . "'
			AND s.status = 5
		AND product_id = " . $prod_id;

//	print $sql; die(1);
		$result = SqlQuery( $sql );
		$s      = "";
		while ( $row = mysqli_fetch_row( $result ) ) {
			$s .= get_supplier_name( $row[0] ) . ", ";
		}
		$s = rtrim( $s, ", " );

		// var_dump($supps);

		return $s;
	}
}
