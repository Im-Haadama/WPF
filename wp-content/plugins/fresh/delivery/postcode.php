<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 16/10/17
 * Time: 04:56
 */

require_once( "../im_tools.php" );
print header_text();
$emek_hefer1 = array(
	"בית חירות",
	"אביחיל",
	"בית הלוי",
	"בית ינאי",
	"בית יצחק-שער חפר",
	"ביתן אהרון",
	"בת חן",
	"גבעת שפירא",
	"הדר עם",
	"העוגן",
	"חבצלת השרון",
	"חופית",
	"כפר המים",
	"כפר ויתקין",
	"כפר חיים",
	"כפר ידידיה",
	"כפר מונאש",
	"מכמורת",
	"מעברות",
	"משמר השרון",
	"נעורים",
	"צוקי ים"
);
$emek_hefer2 = array(
	"אלישיב",
	"אלישיב",
	"בית אליעזר",
	"גאולי תימן",
	"גבעת חיים איחוד ",
	"גבעת חיים מאוחד",
	"חגלה",
	"חדרה",
	"חיבת ציון",
	"חרב לאת",
	"כפר הרואה ",
	"עין החורש"
);
$menashe     = array(
	"ברקאי",
	"גן שומרון",
	"להבות חביבה",
	"מאור",
	"מגל",
	"מענית",
	"מצר",
	"משמרות",
	"עין שמר",
	"שדה יצחק"
);
$emek_east   = array(
	"אומץ",
	"אחיטוב",
	"בארותיים",
	"בורגתה",
	"בת חפר",
	"גן יאשיה",
	"המעפיל",
	"חניאל",
	"יד חנה",
	"עולש"
);
$sharon      = array( "בני דרור", "בני ציון", "בצרה", "חרוצים", "חרות", "כפר הס", "משמרת", "עין ורד", "תל מונד" );

print get_postcode_array( $sharon );

//print get_postcode("כפר יונה");

function get_postcode_array( $array ) {
	$result = array();
	foreach ( $array as $city ) {
		$code = get_postcode( $city );
		array_push( $result, array( $code, $city ) );
	}
	sort( $result );
	foreach ( $result as $line ) {
		print $line[0] . " " . $line [1] . "<br/>";
	}
}
