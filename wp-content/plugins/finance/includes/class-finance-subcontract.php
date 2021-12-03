<?php


class Finance_Subcontract {

	function init_hooks($loader)
	{
		$loader->AddAction('makolet_create_invoice', $this, 'makolet_create_invoice');
		$loader->AddAction('wpf_accounts', $this, 'main', 10, 1);
	}

	function main($tabs)
	{
		$ms = Core_Db_MultiSite::getInstance();

		if ($ms->getLocalSiteID() == 2) { // Makolet
			array_push($tabs, array("subcontract", "עמלות", self::subcontract()));
		}
		return $tabs;
	}

	static function subcontract()
	{
		$result = "עמלות המכולת<br/>";

		$year_month = GetParam("year_month", false, date('Y-m'));
		$result .= $year_month;
		$year  = substr( $year_month, 0, 4 );
		$month = substr( $year_month, 5, 2 );
		$result .= Core_Html::GuiHeader( 1, "משלוחים שבוצעו" );
		$result .= Core_Html::GuiHyperlink("חודש קודם", AddToUrl("year_month", date ('Y-m',
			strtotime($year_month . "-1 -1 month"))));

		$sums  = array(
			"סה\"כ",
			'',
			'',
			'',
			array( 0, 'SumNumbers' ),
			array( 0, 'SumNumbers' ),
			array( 0, 'SumNumbers' ),
			array( 0, 'SumNumbers' )
		);
		$table = self::calc_makolet( $year, $month );
//		$result .= Core_Html::gui_table( $table, "", true, true, $sums );
		$args = [];
		$args["hide_cols"] = array(9 => 1);
		$args["accumulation_row"] = &$sums;
		$result .= Core_Html::gui_table_args( $table, "", $args );

//	foreach ( $driver_array as $key => $value ) {
//		$result .= $key . " " . $value . "<br/>";
//	}

		$result .= Core_Html::GuiHeader( 1, "ריכוז חיוב" );
		$result .= "עמלה: " . $sums[7][0] * 0.2 . "<br/>";
		$result .= "משלוחים: " . $sums[6][0] . "<br/>";

		$sql = "select invoice from im_subcontract_invoice where month = " . $month .
		       " and year = " . $year;

		$doc_id = SqlQuerySingleScalar( $sql );

		if ( $doc_id ) {
			$result .= "הופקה חשבונית מספר  " . $doc_id . "<br/>";
		} else {
			$result .= Core_Html::GuiButton( "btn_create_invoice",  "הפק חשבונית", "makolet_create_invoice('" . Finance::getPostFile() . "', '$year_month')");
		}

		return $result;
	}

	static function calc_makolet( $year, $month ) {
		$db_prefix = GetTablePrefix("delivery_lines");

		$sql = "select d.id, client_from_delivery(d.id), d.date, order_id, total, vat, round(reduce_vat(fee),2), " .
		       " round(total-vat-reduce_vat(fee),2), payment_receipt, internal " .
		       " from ${db_prefix}delivery d" .
		       " join ${db_prefix}business_info i " .
		       " where month(d.date)=" . $month . " and year(d.date)=" . $year . " and i.ref = d.id and i.is_active = 1 ";

		$result = SqlQuery( $sql );
		$table  = array();

		$header = array(
			"מספר משלוח",
			"לקוח",
			"תאריך הזנה",
			"מספר הזמנה",
			"סה\"כ שולם",
			"מע\"מ עסקה",
			"דמי משלוח",
			"עסקה נטו",
			"חשבונית קבלה"
//		"נהג"
		);

		array_push( $table, $header );
		$driver_array = array();

		while ( $row = mysqli_fetch_row( $result ) ) {
			$del_id = $row[0];
			if ($row[7] == 0 or $row[7] == $row[4]) continue;
			$row[0] = Core_Html::GuiHyperlink( $row[0], "/fresh/delivery/get-delivery.php?id=" . $row[0] );
			if (! $row[5]) { // TEMP: VAT BUG
				$row[5] = round(SqlQuerySingleScalar("select sum(vat) from im_delivery_lines where delivery_id = $del_id and product_name != 'משלוח'"), 2);
			}
			$internal = true; // $row[9];
			if ($internal) $row[6] = 0;
//			if ( $row[6] == 0 ) {
//				print $row[0] . "<br/>";
//				$sql1   = "SELECT round(reduce_vat(line_price),2) FROM ${db_prefix}delivery_lines WHERE delivery_id = " . $row[0] . " AND product_name LIKE '%משלוח%'";
//				$row[6] = SqlQuerySingleScalar( $sql1 );
//				$row[7] = $row[4] - $row[5] - $row[6];
//			}
//		$id =  $row[0];

			// $row = array($id, $row[1]);
			array_push( $table, $row );
			// $driver_array[ $row[8] ] += $row[6];
		}

		return $table;
	}

	function makolet_create_invoice()
	{
		$year_month = GetParam("month", true);
		print "מפיק חשבונית לחודש " . $year_month . "<br/>";

		$year  = substr( $year_month, 0, 4 );
		$month = substr( $year_month, 5, 2 );
		$sums = array(
			"סה\"כ",
			'',
			'',
			'',
			array( 0, 'SumNumbers' ),
			array( 0, 'SumNumbers' ),
			array( 0, 'SumNumbers' ),
			array( 0, 'SumNumbers' )
		);

		$table = self::calc_makolet( $year, $month );

		// Just for summing
		Core_Html::gui_table( $table, "", true, true, $sums );

		$net_sell = $sums[7][0];
		$delivery = $sums[6][0];
		print "עמלה: " . $net_sell * 0.2 . "<br/>";
		print "משלוחים: " . $delivery . "<br/>";

		$doc_id = self::create_subcontract_invoice( $month, $year, $net_sell, $delivery );

		if ( $doc_id ) {
			print "חשבונית מספר " . $doc_id . " הופקה בהצלחה";
		}

		$sql = "INSERT INTO im_subcontract_invoice (month, year, invoice) VALUES (" .
		       $month . ", " .
		       $year . ", " .
		       $doc_id . ")";

		return SqlQuery( $sql );
	}

	function create_subcontract_invoice( $month, $year, $net_sell, $net_delivery ) {
		// Connect to invoice of subcontractor and create invoice for this site
		if (! defined('INVOICE_USER_SUB'))
			return "define INVOICE_USER_SUB";
		if (! defined('INVOICE_PASSWORD_SUB'))
			return "define INVOICE_PASSWORD_SUB";
		if (! defined('CLIENT_CUSTOMER_SUB'))
			return "define CLIENT_CUSTOMER_SUB";

		$percent_sub = 0.2;

		$type = "i";

//	print "user: " . $invoice_user_sub . "<br/>";
//	print "password: " . $invoice_password_sub . "<br/>";

		$invoice = new Finance_Invoice4u(INVOICE_USER_SUB, INVOICE_PASSWORD_SUB);

		// print "customer id : " . $customer_id . "<br/>";

//	$invoice_client_id = $invoice->GetInvoiceUserId( $client_customer_sub );

		// print "invoice client id " . $invoice_client_id . "<br/>";

//		$client = $invoice->GetCustomerById( 0, '', CLIENT_CUSTOMER_SUB );
		$client = $invoice->GetCustomerByName( "המכולת האורגנית" );

		if ( ! ( $client->ID ?? 0 ) > 0 ) {
			print "Client not found " . CLIENT_CUSTOMER_SUB . "<br>";

			// var_dump( $client );

			return 0;
		}
		$email = $client->Email;
		// print "user mail: " . $email . "<br/>";
		$doc = new InvoiceDocument();

		$iEmail                = new InvoiceEmail();
		$iEmail->Mail          = $email;
		$doc->AssociatedEmails = Array( $iEmail );
		//var_dump($client->ID);

		$doc->ClientID = $client->ID;
		switch ( $type ) {
			case "r":
				$doc->DocumentType = InvoiceDocumentType::InvoiceReceipt;
				break;
			case "i":
				$doc->DocumentType = InvoiceDocumentType::Invoice;
				break;
		}

		// Set the subject
		$subject = " עמלה ודמי משלוח לחודש " . $month . "/" . $year;

		$doc->Subject = $subject;

		// Add the deliveries
		$doc->Items = Array();

		$total_lines = 0;

		// Add the selling commission
		$item                  = new InvoiceItem();
		$item->Name            = "עמלה";
		$item->Price           = round( $net_sell, 2 ) * 1.17;
		$item->Quantity        = $percent_sub;
		$item->TaxPercentage   = 17;
		$item->TotalWithoutTax = round( $net_delivery * $percent_sub, 2 );
		$item->Total           = round( $item->Price * $item->Quantity, 2 );
		array_push( $doc->Items, $item );

		// Add the delivery fees
		$item                  = new InvoiceItem();
		$item->Name            = "דמי משלוח שניגבו";
		$item->Price           = round( $net_delivery, 2 ) * 1.17;
		$item->Quantity        = 1;
		$item->TaxPercentage   = 17;
		$item->TotalWithoutTax = round( $net_delivery * $percent_sub, 2 );
		$item->Total           = round( $item->Price * $item->Quantity, 2 );
		array_push( $doc->Items, $item );
		$total_lines += $item->Total;

//	if ( $type == "r" ) {
//		if ( is_numeric( $cash ) and $cash <> 0 ) {
//			$pay         = new PaymentCash();
//			$pay->Amount = $cash;
//			array_push( $doc->Payments, $pay );
//		}
//		if ( $bank > 0 ) {
//			$pay         = new PaymentBank();
//			$pay->Amount = $bank;
//			$pay->Date   = $date;
//			array_push( $doc->Payments, $pay );
//		}
//		if ( $credit > 0 ) {
//			$pay         = new PaymentCredit();
//			$pay->Amount = $credit;
//			array_push( $doc->Payments, $pay );
//		}
//		if ( $check > 0 ) {
//			$pay         = new PaymentCheck();
//			$pay->Amount = $check;
//			array_push( $doc->Payments, $pay );
//		}
//
////        if ($total_lines <> ($cash + $bank + $credit + $check)){
////            print "total lines " . $total_lines . "<br/>";
////            print "cash " . $cash . "<br/>";
////            print "bank " . $bank . "<br/>";
////            print "credit " . $credit . "<br/>";
////        }
//		//$pay->Amount = $doc->Total;
//		// print "Amount: " . $pay->Amount . "<br/>";
//		// $doc->RoundAmount = 69;
//		// $doc->Total = 69;
//		// $doc->TaxPercentage = 17;
//		$doc->Total = $credit + $bank + $cash + $check;
//		// $doc->RoundAmount = round($total_lines - $doc->Total, 2);
//		$doc->ToRoundAmount = false;
//		// print "round = " . $doc->RoundAmount . "<br/>";
//		// print "total = " . $doc->Total . "<br/>";
//	}

		// print "create<br/>";
		$doc_id = $invoice->CreateInvoiceDocument( $doc );

		// var_dump($doc);
		return $doc_id;

	}
}
