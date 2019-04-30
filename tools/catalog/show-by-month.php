<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 29/04/19
 * Time: 19:39
 */

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );
}

require_once( ROOT_DIR . "/tools/im_tools.php" );

print header_text( true );
// todo: show availability info

$m = get_param( "month" );

if ( $m ) {
	$year = date( 'Y' );
	if ( $m >= date( 'n' ) ) {
		$year --;
	}
	print "מציג נתונים לחודש " . $m . "/" . $year . "<br/>";
}

if ( ! $m ) {
	$m = "all";
}
if ( $m == "all" ) {
	print "מדד הזמינות: 1 - זמינות גבוהה.
	מספר גבוהה יותר מייצג משקל ממוצע ליחידה (לגבי אבטיח, מלון)
	N/A - לא היה זמין.
	";
}

print  show_category_all( false, "", $fresh = true, $inv = false, "siton", $m );
