<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 03/03/19
 * Time: 22:51
 */

if ( ! defined( "FRESH_INCLUDES" ) ) {
	define( 'FRESH_INCLUDES', dirname( dirname( dirname( __FILE__ ) ) ) );
}

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

$circles = array(
	"רצון פעיל",
	"כנות",
	"סליחה",
	"אהבה",
	"אחריות",
	"סבלנות",
	"איפשור",
	"חופש",
	"עוצמה",
	"תקווה",
	"נדיבות",
	"חוק המשיכה",
	"יחסי גומלין",
	"מימוש מועצם"
);

require_once( FRESH_INCLUDES . '/niver/fund.php' );

require_once( FRESH_INCLUDES . '/niver/gui/inputs.php' );

print header_text( false, true, true, "circle.js" );

$test = get_param( "test" );

if ( $test ) {
	print "<body onload='test(" . $test . ")'";
}
?>

    לוהאר!

    ברוך הבא לכלי העזר "מעגלי העוצמה"...

    <br/>

    התמקד פנימה

    <br/>

    כאשר את/ה מוכנ/ה לחצי על "התחל" ונצא למסע...

<?php

print "<br/>";

print "הדלק מערכים<br/>";

print gui_button( "start", "start(1)", "התחל" );

print gui_div( "output", "" );

print "</body>";

?>