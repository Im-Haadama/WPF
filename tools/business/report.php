<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 08/06/17
 * Time: 18:02
 */
require_once( "../r-shop_manager.php" );
require_once( "../gui/sql_table.php" );
require_once( "../people/people.php" );

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

function print_project_report( $project_id ) {
	print gui_header( 1, "מציג סיכום עלויות פרויקט " . get_project_name( $project_id ) );

	$month_sum = array();
	$lines     = print_transactions( 0, 0, 0, null, $project_id, $month_sum );

	$summary = array();
	array_push( $summary, array( "חודש", "עלות" ) );
	foreach ( $month_sum as $month => $sum ) {
		array_push( $summary, array( $month, $sum ) );
		//	print "total for month " . $month . " " . $sum . "<br/>";
	}
	print gui_table( $summary );
	print $lines;

}

function print_weekly_report( $week ) {
	print gui_header( 1, "מציג תוצאות לשבוע המתחיל ביום " . $week );
// print date('Y-m-d', strtotime($week . " -1 week")) . "<br/>";
	if ( date( 'Y-m-d' ) > date( 'Y-m-d', strtotime( $week . "+1 week" ) ) ) {
		print gui_hyperlink( "שבוע הבא", "report.php?week=" . date( 'Y-m-d', strtotime( $week . " +1 week" ) ) ) . " ";
	}

	print gui_hyperlink( "שבוע קודם", "report.php?week=" . date( 'Y-m-d', strtotime( $week . " -1 week" ) ) );

	$sql = "SELECT id, date AS תאריך, amount AS סכום, delivery_fee AS 'דמי משלוח', client_from_delivery(ref) AS לקוח FROM im_business_info WHERE " .
	       " week = '" . $week . "' AND amount > 0 ORDER BY 3 DESC";

	$sums_in = array( 0, 0, 1, 1, 0 );
	$inputs  = table_content( $sql, true, true, null, $sums_in );

	$sql = "SELECT id, date, amount AS סכום, supplier_from_business(id) AS ספק FROM im_business_info WHERE " .
	       " week = '" . $week . "' AND is_active = 1 AND amount < 0 ORDER BY 3 DESC";

	$sums_supplies = array( 0, 0, 1, 0, 0 );
	$outputs       = table_content( $sql, true, true, null, $sums_supplies );

	$salary      = 0;
	$salary_text = print_transactions( 0, 0, 0, $week, 3, $salary );
	$salary      = - $salary;


	print gui_header( 1, "סיכום" );
	$total_sums = array( "סיכום", 1 );
	print gui_table( array(
		array( "סעיף", "סכום" ),
		array( "תוצרת", $sums_in[2] ),
		array( "דמי משלוח", $sums_in[3] ),
		array( "גלם", $sums_supplies[2] ),
		array( "שכר", $salary )
	), "totals", true, true, $total_sums );

	print gui_header( 2, "הכנסות" );
	print $inputs;

	print gui_header( 2, "הוצאות" );
	print $outputs;

	print gui_header( 2, "שכר" );
	print $salary_text;
}