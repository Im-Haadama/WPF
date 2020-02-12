<?php

$zones = array(
	"כפר יונה והסביבה" => array("בית יצחק-שער חפר", "בורגתא", "חניאל", "עולש", "בארותיים", "חניאל", "גנות הדר", "נורדיה", "ניצני עוז", "שער אפריים", "תנובות", "ינוב"),
	"נתניה והסביבה" => array("נתניה", "תל מונד", "קדימה-צורן", "אבן-יהודה", "עין ורד", "עין שריד", "כפר הס", "יעף", "כפר יעבץ", "עזריאל", "פורת", "חירות", "משמרת", "צור משה", "פרדסיה", "גאולים", "בני דרור", "חירות",
   "אודים", "געש", "רשפון", "כפר נטר", "הרסוף", "בית יהושע", "בני ציון", "בצרה", "חרוצים", "יקום", "שפיים", "תל יצחק",
   "יד-חנה", "בת-חפר", "בחן", "גן-יאשיה", "בית הלוי", "כפר מונש", "העוגן", "כפר-חיים", "הדר-עם", "מעברות", "משמר השרון", "אלישיב", "כפר הרואה", "גבעת חיים איחוד ומאוחד", "עין החורש", "אליכין", "חרב לאת", "אחיטוב", "שדה יצחק", "מאור", "גאולי תימן", "כפר ויתקין", "הדר עם", "כפר חיים", "כפר ידידיה", "צוקי ים", "חופית", "בית חירות", "ביתן אהרון", "בית ינאי", "מכמורת", "מבואות ים", "בת חן", "גבעת שפירא", "אביחיל", "חבצלת השרון", "שושנת העמקים",
   "ברקאי", "גן שמואל", "כפר גליקסון", "להבות חביבה", "מגל", "מענית", "מצר", " משמרות", "עין שמר", "רגבים", "גן השומרון", "כפר פינס", "מאור", "מי עמי", "עין עירון", "שדה יצחק", "תלמי אלעזר", "אילן", "קציר", "מייסר", "אום אל-קוטוף", "אל-עריאן"));

foreach ($zones as $name => $cities)
{
	print "zone name:" . $name . "<br/>";
	foreach ($cities as $city)
	{
		print $city  . " " . israelpost_get_city_postcode($city) . "<br/>";
	}
}
function israelpost_get_address_postcode( $city, $street, $house ) {
	$url = "http://www.israelpost.co.il/zip_data.nsf/SearchZip?OpenAgent&Location=" . urlencode( $city ) . "&street=" . $street .
	       "&house=" . $house;

	$ch = curl_init();

	$timeout = 5;
	curl_setopt( $ch, CURLOPT_URL, $url );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );
	$data = curl_exec( $ch );
	curl_close( $ch );

	$value = array();
	if ( preg_match( "/RES[0-9]*/", $data, $value ) ) {
		$result = substr( $value[0], 4 );

		if ( $result == "11" or $result == "12" or $result == "13" ) {
			return - 1;
		}

		return $result;
	}

	return - 2;
}

function israelpost_get_city_postcode( $city )
{
	$url = "http://www.israelpost.co.il/zip_data.nsf/SearchZip?OpenAgent&Location=" . urlencode( $city ) . "&POB=1";

	$data = file_get_contents( $url );

	$value = array();
	if ( preg_match( "/RES[0-9]*/", $data, $value ) ) {
		$result = substr( $value[0], 4 );

		if ( $result == "11" or $result == "12" or $result == "13" ) {
			return - 1;
		}

		return $result;
	}

	return - 2;
}

