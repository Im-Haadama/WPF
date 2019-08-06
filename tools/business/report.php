<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 08/06/17
 * Time: 18:02
 */


if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );
}
require_once( ROOT_DIR . '/tools/im_tools.php' );
require_once( ROOT_DIR . '/niver/fund.php' );
require_once( ROOT_DIR . '/tools/multi-site/imMulti-site.php' );
//require_once( "../r-shop_manager.php" );
require_once( ROOT_DIR . "/niver/gui/sql_table.php" );
//require_once( "../people/people.php" );


print header_text();

if ( isset( $_GET["week"] ) ) {
	print_weekly_report( $_GET["week"] );
	die( 0 );
}

if ( isset( $_GET["project"] ) ) {
	print_project_report( $_GET["project"] );
	die( 0 );
}

print_weekly_report( date( "Y-m-d", strtotime( "last sunday" ) ) );

function print_project_report( $role, $project_id ) {
	print gui_header( 1, "מציג סיכום עלויות פרויקט " . get_project_name( $project_id ) );

	$month_sum = array();
	$lines     = print_transactions( $role, 0, 0, 0, null, $project_id, $month_sum );

	$summary = array();
	array_push( $summary, array( "חודש", "עלות" ) );
	foreach ( $month_sum as $month => $sum ) {
		array_push( $summary, array( $month, $sum ) );
		//	print "total for month " . $month . " " . $sum . "<br/>";
	}
	print gui_table_args( $summary );
	print $lines;

}

function print_weekly_report( $week ) {
	print gui_header( 1, "מציג תוצאות לשבוע המתחיל ביום " . $week );
// print date('Y-m-d', strtotime($week . " -1 week")) . "<br/>";
	if ( date( 'Y-m-d' ) > date( 'Y-m-d', strtotime( $week . "+1 week" ) ) ) {
		print gui_hyperlink( "שבוע הבא", "report.php?week=" . date( 'Y-m-d', strtotime( $week . " +1 week" ) ) ) . " ";
	}

	print gui_hyperlink( "שבוע קודם", "report.php?week=" . date( 'Y-m-d', strtotime( $week . " -1 week" ) ) );

	$sql = "SELECT ref AS 'תעודת משלוח', date AS תאריך, amount AS סכום, delivery_fee AS 'דמי משלוח', client_from_delivery(ref) AS לקוח,
		delivery_receipt(ref) AS קבלה
		FROM im_business_info WHERE " .
	       " is_active = 1 AND week = '" . $week . "' AND amount > 0 ORDER BY 1";

	$sums_in = array( 0, 0, array( 0, sum_numbers ), array( 0, sum_numbers ), 0 );
	$inputs  = table_content( "table", $sql, true, true, array( "../delivery/get-delivery.php?id=%s" ), $sums_in );

	$sql = "SELECT supply_from_business(id) as 'אספקה', id, ref as 'תעודת משלוח', date as תאריך, amount AS סכום, " .
	       "supplier_from_business(id) AS ספק, pay_date as 'תאריך תשלום' " .
	       " FROM im_business_info WHERE " .
	       " week = '" . $week . "' AND is_active = 1 AND amount < 0 " .
	       " and document_type = 5 " .
	       " ORDER BY 3 DESC";

	$sums_supplies = array( "", "", "", "", array( 0, sum_numbers ), "", "" );
	$outputs       = table_content( "table", $sql, true, true,
		array( "../supplies/supply-get.php?id=%s" ), $sums_supplies );

	$salary_text = ImMultiSite::sExecute( "people/report-trans.php?week=" . $week . "&project=3", 1 );
	$dom         = im_str_get_html( $salary_text );
	$row         = "";
	foreach ( $dom->find( 'tr' ) as $row ) {
		;
	}
	$salary_fruity = - (int) $row->find( 'td', 11 )->plaintext;
	$travel        = - (int) $row->find( 'td', 13 )->plaintext;
	$extra         = - (int) $row->find( 'td', 12 )->plaintext;

	$salary_text .= ImMultiSite::sExecute( "people/report-trans.php?week=" . $week . "&project=11", 1 );
	$dom         = im_str_get_html( $salary_text );
	$row         = "";
	foreach ( $dom->find( 'tr' ) as $row ) {
		;
	}
	$salary_delivery = - (int) $row->find( 'td', 11 )->plaintext;
	$travel          -= (int) $row->find( 'td', 13 )->plaintext;
	$extra           -= (int) $row->find( 'td', 12 )->plaintext;

	print gui_header( 1, "סיכום" );
	$total_sums = array( "סיכום", array( 0, sum_numbers ) );
	print gui_table( array(
		array( "סעיף", "סכום" ),
		array( "תוצרת פרוטי", $sums_in[2][0] ),
		array( "דמי משלוח פרוטי", $sums_in[3][0] ),
		array( "גלם", $sums_supplies[4][0] ),
		array( "שכר אריזה", $salary_fruity ),
		array( "שכר משלוחים", $salary_delivery ),
		array( "הוצ' נסיעה", $travel ),
		array( "הוצ עובדים נוספות", $extra)
	), "totals", true, true, $total_sums );

	print gui_header( 2, "הכנסות" );
	print $inputs;

	print gui_header( 2, "אספקות" );
	print $outputs;

	print gui_header( 2, "שכר" );
	print $salary_text;
}