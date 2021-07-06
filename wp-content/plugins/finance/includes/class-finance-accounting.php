<?php

//require ABSPATH . '/vendor/autoload.php';
//
//use Laminas\Mail\Storage\Pop3;

class Finance_Accounting {

	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			return new self();
		}
		return self::$_instance;
	}

	function init_hooks($loader)
	{
		$menu = Core_Admin_Menu::instance();

		$menu->AddMenu("הנהלת חשבונות", "הנהלת חשבונות", "edit_shop_orders", "accounting", array(__CLASS__, 'accounting'));
		Core_Gem::getInstance()->AddTable( "business_info" );
	}

	static function monthly_report($month)
	{
		$report = "";
		$report .= Core_Html::GuiHeader( 1, "מציג תוצאות לחודש המתחיל ביום " . $month );
		$report .= Core_Html::GuiHeader(2, "הכנסות");
		$next_month = date( 'Y-m-d', strtotime('+1 month', strtotime($month)));
		$sql = "select sum(total), sum(fee) from im_delivery where date >= '$month' and date < '$next_month'";
		$data = SqlQuerySingle($sql);

		$report .= 'Deliveries (include vat): ' . $data[1] . "<br/>";
		$report .= 'Goods : ' . $data[0] . "<br/>";
		$report .= Core_Html::GuiHeader(2, "הוצאות");

		return $report;
	}

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

		$sql = "SELECT id, ref, date, amount, delivery_fee as 'delivery fee', client_from_delivery(ref) as client,
		delivery_receipt(ref) AS קבלה
		FROM im_business_info WHERE " .
		       " is_active = 1 AND week = '" . $week . "' AND amount > 0 ORDER BY 1";

		$sums_in = array(  "id" => '', "ref" => "סה\"כ",
		                   "date" => '',
		                   "amount" => array( 0, 'SumNumbers' ),
		                   'delivery fee' => array(0, 'SumNumbers'),
			'client' => '',
			'קבלה' => '',
			               'due_vat' => array(0, 'SumNumbers'),
			'fresh' => array(0, 'SumNumbers'));

		$in_args = array("links" => array("id"=>AddToUrl(array("operation"=>'invoice_show', 'id'=>"%d")),
			"ref" => Finance_Delivery::getLink('%s')),
		                 "accumulation_row" => &$sums_in,
		                 "id_field" => "ref");
		$rows_data = Core_Data::TableData( $sql, $in_args);
		$due_vat = 0;

		// Add due vat, fresh total
		if (! $rows_data) return $report . "No data!<br/>";
		foreach ($rows_data as $delivery_id => $row)
		{
//			print "del: $delivery_id<br/>";
			if ($delivery_id == 'header') {
				$rows_data[ $delivery_id ]['due_vat'] = 'מוצרים חייבי מע"מ';// ;;__("Vat");
				$rows_data[$delivery_id] ['fresh'] = 'סה"כ מוצרים טריים';
			}
			else {
				$rows_data[$delivery_id] = apply_filters("finance_weekly_update_row", $rows_data[$delivery_id], $delivery_id);
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
		$db_prefix = GetTablePrefix("delivery_lines");

		$result = "";
		$result .=Core_Html::GuiHeader( 1, "מציג תוצאות לשבוע המתחיל ביום " . $week );

		$result .= "<br/>";

		$sql = "SELECT product_name, round(sum(quantity), 1), max(prod_id), product_name FROM ${db_prefix}delivery_lines " .
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

	static function accounting() {
//		Fresh_Catalog::draft_no_picture();

//		Finance_Business_Logic::CreateTokens();
		$result = "";
		$hook_manager = Core_Hook_Handler::instance();

		$selected_tab = GetParam("st_suppliers", false, "weekly");

		$tabs = array(array("weekly", "Weekly summary", "ws"),
			array("monthly", "Monthly summary", "ms"),
			array("supplier_transactions", "Suppliers accounts", "st"),
			array("supplier_invoices", "Supply invoices", "si"));

		// Put the tab names inside the array.
		if ($operation = GetParam("operation", false, null, true)) {
			$args = [];
			$args["operation"] = $operation;
			$args["post_file"] = WPF_Flavor::getPost();
			$hook_manager->DoAction($operation, $args);
			return;
		}

		switch ($selected_tab)
		{
			case "weekly":
				$tab_content = "";
				$week = GetParam("week", false, date( "Y-m-d", strtotime( "last sunday" )));
				if ( date( 'Y-m-d' ) > date( 'Y-m-d', strtotime( $week . "+1 week" ) ) ) {
					$tab_content .= Core_Html::GuiHyperlink( "שבוע הבא", AddToUrl("week", date( 'Y-m-d', strtotime( $week . " +1 week" ) ) )) . " ";
				}

				$tab_content .= Core_Html::GuiHyperlink( "שבוע קודם", AddToUrl("week",  date( 'Y-m-d', strtotime( $week . " -1 week" ) ) ));
				$tab_content .= Finance_Accounting::weekly_report($week);
				$tabs[0][2] = $tab_content;
				break;

			case "monthly":
				$tab_content = "";
				$month = GetParam("month", false, date( "Y-m-d", strtotime('1-' . date('m') . '-' . date('Y'))) );
				if ( date( 'Y-m-d' ) > date( 'Y-m-d', strtotime( $month . "+1 month" ) ) ) {
					$tab_content .= Core_Html::GuiHyperlink( "חודש הבא", AddToUrl("month", date( 'Y-m-d', strtotime( $month . " +1 month" ) ) )) . " ";
				}

				$tab_content .= Core_Html::GuiHyperlink( "חודש קודם", AddToUrl("month",  date( 'Y-m-d', strtotime( $month . " -1 month" ) ) ));
				$tab_content .= Finance_Accounting::monthly_report($month);
				$tabs[1][2] = $tab_content;
				break;


			case "supplier_transactions":
				$tab_content = self::SupplierTransactions(GetParam("include_zero", false, false));
				$tabs[2][2] = $tab_content;
//				if ($ms->getLocalSiteID() != 2) { // Makolet
//					ardray_push( $tabs, array( "", "",  ) );
//					array_push( $tabs, array(
//						"supplier_invoices",
//						"Suppliers invoices",
//						Finance_Invoices::Table( AddToUrl( "selected_tab", "supplier_invoices" ) )
//					) );
//				}
				break;

			case "supplier_invoices":
				$tabs[3][2] = Finance_Invoices::Table( GetUrl());
				break;

			case "subcontract": // Added by different class. Todo: add a filter(?)
				break;

			default:
				die ("Failed: $selected_tab not handled");
		}

//		array_push($tabs, array("potato", "test", self::test_price()));
		$tabs = apply_filters('wpf_accounts', $tabs);

		$args = array("st_suppliers" => $selected_tab);
		$args["tabs_load_all"] = false;
		$args["url"] = "/wp-admin/admin.php?page=accounting";

		$result .= Core_Html::gui_div("logging");
		$result .= Core_Html::GuiTabs( "suppliers", $tabs, $args );

		print  $result;
	}

	static function SupplierTransactions($include_zero = false)
	{
		$supplier_id = GetParam( "supplier_id", false, null );
		if ( $supplier_id ) return Fresh_Supplier_Balance::get_supplier_transactions($supplier_id);

		$result = "<table>";

		$sql = "SELECT supplier_balance(part_id, curdate()) as balance, part_id, supplier_displayname(part_id) \n"
		       . "FROM `im_business_info`\n"
		       . "where part_id > 10000\n"
		       . " and is_active = 1\n"
		       //		       " and balance > 0 "
		       . " and document_type in (" . Finance_DocumentType::invoice . ", " . Finance_DocumentType::bank . ", " .
		       Finance_DocumentType::invoice_refund . ")\n"
		       //       . " and document_type in (" . Finance_DocumentType::invoice . ", " . Finance_DocumentType::bank . ")\n"
		       . "group by part_id";

		if ( ! $include_zero ) $sql .= " having balance < 0";


		$sql_result = SqlQuery( $sql );

		$data_lines         = array();
		$data_lines_credits = array();

		while ( $row = SqlFetchRow( $sql_result ) ) {
			$supplier_total = $row[0];
//			print "st=$supplier_total" . abs($supplier_total);
			if (! $include_zero and (! (abs($supplier_total)> 10))) continue;
//			print "cocccc<br/>";
			$supplier_id    = $row[1];
			$supplier_name  = $row[2];

			$line = Core_Html::gui_cell( gui_checkbox( "chk_" . $supplier_id, "supplier_chk" ) );
			// $line .= "<td><a href = \"get-supplier-account.php?supplier_id=" . $supplier_id . "\">" . $supplier_name . "</a></td>";
			$line .= Core_Html::gui_cell( Core_Html::GuiHyperlink( $supplier_name, AddToUrl("supplier_id", $supplier_id )));

			$line .= "<td>" . $supplier_total . "</td>";
			array_push( $data_lines, $line );
		}

// sort( $data_lines );

		for ( $i = 0; $i < count( $data_lines ); $i ++ ) {
			$line = $data_lines[ $i ];
			$result .= "<tr> " . trim( $line ) . "</tr>";
		}

		$result = str_replace( "\r", "", $result );

		$result .= "<center><h1>יתרות לתשלום</h1></center>";

		$result .= "</table>";

		$result .= Core_html::GuiHyperlink("הצג גם מאופסים", AddToUrl("include_zero", true));

		return $result;
	}

	static function manage_inbox()
	{
		$result = Core_Html::GuiHeader(1, "inbox");
		foreach (array('FINANCE_HOST', 'FINANCE_USER', 'FINANCE_PASSWORD') as $var)
		if (! defined($var)) {
			$result .= "Define $var in config file";
			print $result;
			return;
		}
		$result .= self::read_inbox(FINANCE_HOST, FINANCE_USER, FINANCE_PASSWORD);

		print $result;
	}

	static function read_inbox( $host, $user, $pass, $debug = false) {
		$count  = 0;
		$result = "";

		echo "trying $host<br/>";

		$mail = new Pop3( [
			'host'     => $host,
			'user'     => $user,
			'password' => $pass,
//			'port' => 995,
			'ssl'      => 'SSL'
		] );

		$msg_cnt = $mail->countMessages();
		echo $msg_cnt . " messages found\n";
//		foreach ($mail as $message) {
//			printf("Mail from '%s': %s\n", $message->from, $message->subject);
//		}

//		if ( ! $inbox ) {
//			print "can't open mailbox<br/>";
//		 print $host . "<br/>";
//		 print $user . "<br/>";
//		 print $pass . "<br/>";
//			return false;
//		}

		print $msg_cnt . " messages in the inbox<br/>";

//		$emails = imap_search( $inbox, 'ALL', SE_UID );

		print "<table>";
// for($i = 1; $i <= $msg_cnt; $i++) {
		foreach ( $mail as $email ) {
			$count ++;

//		if ($count > 10) {
//			print "debug<br/>";
//			die(3);
//		}
//			$i       = imap_msgno( $inbox, $email );
//			$header  = imap_headerinfo( $inbox, $i );
			$subject = $email->subject;
			$date = $email->date;
			if (strtotime($date) < (strtotime('today') - 365*24*60*60)) continue;
			if (! strstr($subject, "חשבונית")) continue;
			print "<tr><td>$subject</td></tr>";
//			$sender  = $email->
		}
		print "</table>";

//		$date    = header_date( $header );
//		$handled = false;
//
////	 print "Sender: $sender. Subject: $subject<br/>";
//		$l = substr( $sender, 0, 4 );
//		// print "\n" . $l . " " . $subject . "\n";
	}

	function nop() {
		{
			switch ( strtolower( $sender ) ) {
				case "yab02@orange.net.il": // Amir ben yehuda
					if ( strstr( $subject, "רשימה" ) ) {
						handle_pricelist( $subject, $inbox, "amir", $date, $i, true );
						$handled = true;
					}
					break;

				case "batya_l@maabarot.com":
				case "yaakov.aglamaz@gmail.com":
				case "yaakov@im-haadama.co.il":
				case "limor_s@maabarot.com": // Sadot
					if ( strstr( $subject, "מחירון" ) ) {
						$handled = handle_pricelist( $subject, $inbox, "sadot", $date, $i );
						break;
					}
					if ( strstr( $subject, "הזמנה" ) ) {
						$handled = handle_supply( $subject, $inbox, "sadot", $date, $i );
						break;
					}
					break;
				case "office@yevulebar.co.il": // yb
					//    print "start yb $subject<br/>";
					if ( strstr( $subject, "מלאי" ) ) {
						$handled = handle_pricelist( $subject, $inbox, "yb", $date, $i, null, $debug );
					}
					break;

				case "info@im-haadama.co.il":
				case "yaakov@im-haadama.co.il":
					if ( strstr( $subject, "מספר" ) and strstr( $subject, "משלוח" ) ) {
						handle_automail( $subject, $inbox, "delivery", $i );
						$handled = true;
					}
					if ( strstr( $subject, "מלקוח" ) ) {
						handle_automail( $subject, $inbox, "הזמנות", $i );
						$handled = true;
					}
					break;
				case "notify@google.com‏": // Doesn't catch. See below
					if ( strstr( $subject, "שורשים" ) ) {
						handle_google( $subject, $inbox, "yosef", $date, $i );
						$handled = true;
					}
					if ( strstr( $subject, "בן יהודה" ) ) {
						handle_google( $subject, $inbox, "amir", $date, $i );
						$handled = true;
					}
					break;
//		case "office@organya.co.il":
//			if (strstr($subject, "מחירון")){
//				$handled = handle_pricelist($subject, $inbox, "organya", $date, $i);
//			}
//			break;

				default:

			}
			if ( $sender == "notify@google.com" ) {
				if ( strstr( $subject, "שורשים" ) ) {
					// handle_google( $subject, $inbox, "yosef", $date, $i );

					// print "<tr><td>" . $subject . "</td>";
					$handled = true;
				}
				if ( strstr( $subject, "בן יהודה" ) ) {
					// print "<tr><td>" . $subject . "</td>";
					// print "amir<br/>";
					handle_google( $subject, $inbox, "amir", $date, $i );
					$handled = true;
				}
			}
			if ( ! $handled ) {
				print "<td>" . $sender . "</td><td>" . $subject . "</td><td>not handled<td/></tr>";
			}
		}
		print "</table>";


// Since we update manually on site 2, auto update just site 1.
//for ($site_id = 1; $site_id <= 1; $site_id++)
//	if ($changed_price[$site_id]) {
//		$html = run_in_server($site_id, "/catalog/catalog-auto-update.php?no_header");
//		print $html;
//	}
		imap_expunge( $inbox );
	}

}
