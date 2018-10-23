<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 08/06/17
 * Time: 18:02
 */
require_once( "../r-shop_manager.php" );
require_once( ROOT_DIR . "/agla/gui/sql_table.php" );

print header_text();

if ( isset( $_GET["week"] ) and isset( $_GET["prod_id"] ) ) {
	print_prod_report( $_GET["prod_id"], $_GET["week"] );

	return;
}

if ( isset( $_GET["prod_id"] ) ) {
	print_prod_report( $_GET["prod_id"], null, isset( $_GET["user_id"] ) ? $_GET["user_id"] : null );

	return;
}

if ( isset( $_GET["week"] ) ) {
	print_weekly_report( $_GET["week"] );

	return;
}

if ( isset( $_GET["project"] ) ) {
	print_project_report( $_GET["project"] );
	die( 0 );
}

print_weekly_report( date( "Y-m-d", strtotime( "last sunday" ) ) );

function sums( &$s, $a ) {
//	var_dump($s); print "<br/>";
//	var_dump($a); print "<br/>";
	if ( is_numeric( $s ) and is_numeric( $a ) ) {
		$s += $a;
		// print $s . "<br/>";
	}
}

function print_prod_report( $prod_id, $week = null, $user_id = null ) {
	if ( $week ) {
		print gui_header( 1, "מציג תוצאות לשבוע המתחיל ביום " . $week );
	}
	print gui_header( 2, "מוצר " . get_product_name( $prod_id ) );
	if ( $user_id ) {
		print gui_header( 2, "לקוח " . get_customer_name( $user_id ) );
	}


	$sql = "SELECT delivery_id, product_name, round(quantity, 1), order_id";
	if ( ! $week ) {
		$sql .= ", date";
	}
	$sql .= " FROM im_delivery_lines dl JOIN im_delivery d " .
	        " WHERE dl.delivery_id = d.id AND prod_id = " . $prod_id . " AND delivery_id IN (SELECT id FROM im_delivery";

	$query = null;
	if ( $week ) {
		add_query( $query, "first_day_of_week(date) = '" . $week . "'" );
	}
	if ( $user_id ) {
		add_query( $query, "order_user(order_id) = " . $user_id );
	}

	if ( $query ) {
		$sql .= " " . $query;
	}
	$sql .= ")";

	print $sql;
	$result = sql_query( $sql );

	$lines = array();
	while ( $row = mysqli_fetch_row( $result ) ) {
		$line = array(
			gui_hyperlink( $row[0], "get-delivery.php?id=" . $row[0] ),
			$row[2],
			get_customer_name( order_get_customer_id( $row[3] ) )
		);
		if ( ! $week ) {
			array_push( $line, $row[4] );
		}
		array_push( $lines, $line );
	}

	print gui_table( $lines );
}

function print_weekly_report( $week ) {
	print gui_header( 1, "מציג תוצאות לשבוע המתחיל ביום " . $week );
// print date('Y-m-d', strtotime($week . " -1 week")) . "<br/>";
	if ( date( 'Y-m-d' ) > date( 'Y-m-d', strtotime( $week . "+1 week" ) ) ) {
		print gui_hyperlink( "שבוע הבא", "report.php?week=" . date( 'Y-m-d', strtotime( $week . " +1 week" ) ) ) . " ";
	}

	print gui_hyperlink( "שבוע קודם", "report.php?week=" . date( 'Y-m-d', strtotime( $week . " -1 week" ) ) );

	print "<br/>";

	$sql = "SELECT product_name, round(sum(quantity), 1), max(prod_id), product_name FROM im_delivery_lines " .
	       " WHERE delivery_id IN (SELECT id FROM im_delivery WHERE first_day_of_week(date) = '" . $week . "')" .
	       " GROUP BY 4";
	// print $sql;1
	$result = sql_query( $sql );

	$lines = array();
	while ( $row = mysqli_fetch_row( $result ) ) {
		$quantity = $row[1];
		if ( ! ( $quantity > 0 ) ) {
			continue;
		}
		$prod_id   = $row[2];
		$prod_name = $row[0];
		$suppliers = archive_get_supplier( $prod_id, $week );
		$q         = gui_hyperlink( $quantity, "report.php?prod_id=" . $prod_id . "&week=" . $week );
		array_push( $lines, array( $prod_id, $suppliers, $prod_name, $q ) );
	}

	sort( $lines );

	array_unshift( $lines, array( "מזהה מוצר", "ספקים", "שם מוצר", "כמות" ) );

	print gui_table( $lines );

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
//	print gui_header( 1, "סיכום" );
//	$total_sums = array( "סיכום", array( 0, sums ) );
//	print gui_table( array(
//		array( "סעיף", "סכום" ),
//		array( "תוצרת", $sums_in[2][0] ),
//		array( "דמי משלוח", $sums_in[3][0] ),
//		array( "גלם", $sums_supplies[2][0] ),
//		array( "שכר", $salary )
//	), "totals", true, true, $total_sums );
//
//	print gui_header( 2, "הכנסות" );
//	print $inputs;
//
//	print gui_header( 2, "הוצאות" );
//	print $outputs;
//
//	print gui_header( 2, "שכר" );
//	print $salary_text;
}

function archive_get_supplier( $prod_id, $week ) {
	$sql = "SELECT DISTINCT s.supplier
	FROM im_supplies s
	JOIN im_supplies_lines l
	WHERE l.supply_id = s.id
		AND first_day_of_week(date) = '" . $week . "'
			AND s.status = 5
		AND product_id = " . $prod_id;

//	print $sql; die(1);
	$result = sql_query( $sql );
	$s      = "";
	while ( $row = mysqli_fetch_row( $result ) ) {
		$s .= get_supplier_name( $row[0] ) . ", ";
	}
	$s = rtrim( $s, ", " );

	// var_dump($supps);

	return $s;
}